<?php

namespace App\Http\Controllers\Refactor\Payment;

use App\Models\Address2;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\TamaraPayment;
use App\Helper\ApiResponseHelper;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Payment\TamaraService;

class TamaraPaymentController extends Controller
{
    use ApiResponseHelper;

    // new integration functions
    public function createCheckout(Request $request)
    {
        // return $request->order_id;
        $user = $request->user();
        if (!auth()->check()) {
            return $this->setCode(code: 401)->setData([])->setMessage('Unauthorized')->send();
        }
        $address = Address2::where('user_id',  $user->id)
            ->where('status', 'active')->where('default', true)
            ->get();
        if ($address->isEmpty()) {
            return $this->setCode(code: 404)->setData([])->setMessage('No default address found')->send();
        }
        $data = $request->all();
        $data['user'] = $user;
        $data['address'] = $address;

        // create payment
        do {
            $reference_id = strtoupper(Str::random(12));
        } while (TamaraPayment::where('reference_id', $reference_id)->exists());

        $data['reference_id'] = $reference_id;
        // save tamara payment with pending status
        DB::transaction(function () use ($request, $user, &$tamara_payment, $reference_id) {

            $tamara_payment = new TamaraPayment();
            $tamara_payment->user_id = $user->id;
            $tamara_payment->amount = $request->amount;
            $tamara_payment->discount = $request->discount ?? 0;
            $tamara_payment->reference_id = $reference_id;
            $tamara_payment->save();

            foreach ($request->items as $item) {
                $tamara_payment->items()->create([
                    'item_id'         => $item['id'] ?? null,
                    'name'            => $item['name'],
                    'description'     => $item['description'] ?? null,
                    'quantity'        => $item['quantity'],
                    'price'           => $item['price'],
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'category'        => $item['category'] ?? null,
                ]);
            }
        });

        $tamara = new TamaraService();
        return    $response = $tamara->checkout($data, $tamara_payment);
    }
    public function newSuccess(Request $request)
    {
        $url = $request->path();
        // integration/tamara/success/reference_id=CTXQNGSUHBJW/sales_order_id=SO-001197956
        preg_match('/reference_id=([^\/]+)/', $url, $match);
        $referenceId1 = $match[1] ?? null;
        $referenceId =  $referenceId1 ?? $request->query('reference_id');
        $orderId = $request->query('orderId');
        try {
            $reference_id = $request->reference_id ?? null;
            $payment = TamaraPayment::where('reference_id', $reference_id)->orderByDesc('id')->first();


            if (!$payment) {
                return response()->json(['status' => false, 'message' => 'Payment not found']);
            }

            // ✅ Mark payment as paid
            $payment->update(['status' => 'paid']);

            try {
                // Call Tamara API for this order
                $statusResponse = app(TamaraService::class)->getOrderStatus($orderId);
                if (isset($statusResponse['status'])) {
                    if ($statusResponse['status'] === 'approved') {
                        $authResponse = app(TamaraService::class)->authorizeOrder($orderId);
                        // $captureResponse = app(TamaraService::class)->captureOrderNew($referenceId, $orderId);

                        if (isset($authResponse['status']) && $authResponse['status'] === 'authorised') {
                            $captureResponse = app(TamaraService::class)->captureOrderNew($referenceId, $orderId);
                        }
                    } elseif ($statusResponse['status'] === 'authorised') {
                        $captureResponse = app(TamaraService::class)->captureOrderNew($referenceId, $orderId);
                    }

                    // Mark success regardless of status flow

                }
            } catch (\Throwable $e) {
                \Log::error('getAppointmentBySalesOrder failed: ' . $e->getMessage());
            }
            // ✅ Calculate tax breakdown
            $totalAmount = $payment->amount;
            $priceWithoutTax = round($totalAmount / 1.15, 2);
            $taxAmount = round($totalAmount - $priceWithoutTax, 2);
            return view('Payment.result', [
                'status' => 'paid',
                'payment_type' => 'tamara',
                'payment' => $payment,
                'phone' => $payment->phone ?? $payment->reference_id,
                'priceWithoutTax' => $priceWithoutTax,
                'taxAmount' => $taxAmount,
            ]);
        } catch (\Throwable $e) {
            // Log::error('Tamara newSuccess error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => $e->getMessage()]);
        }
    }


    public function newFailure(Request $request)
    {
        // Get ALL TabbyPayment records for this appointment
        $payment = TamaraPayment::where('reference_id', $request->reference_id)->orderByDesc('id')->first();

        if ($payment) {
            $payment->status = 'failed';
            $payment->save();
        }
        $totalAmount = $payment->amount;
        $priceWithoutTax = round($totalAmount / 1.15, 2);
        $taxAmount = round($totalAmount - $priceWithoutTax, 2);

        return view('Payment.result', [
            'status' => 'failed',
            'payment_type' => 'tamara',
            'payment' => $payment,
            'phone' => $payment->phone ?? $payment->reference_id,
            'priceWithoutTax' => $priceWithoutTax,
            'taxAmount' => $taxAmount,
        ]);
    }


    public function newCancel(Request $request)
    {
        // Get ALL TabbyPayment records for this appointment
        $payment = TamaraPayment::where('reference_id', $request->reference_id)->orderByDesc('id')->first();
        if ($payment) {
            $payment->status = 'failed';
            $payment->save();
        }
        $totalAmount = $payment->amount;
        $priceWithoutTax = round($totalAmount / 1.15, 2);
        $taxAmount = round($totalAmount - $priceWithoutTax, 2);

        return view('Payment.result', [
            'status' => 'canceled',
            'payment_type' => 'tamara',
            'payment' => $payment,
            'phone' => $payment->phone ?? $payment->reference_id,
            'priceWithoutTax' => $priceWithoutTax,
            'taxAmount' => $taxAmount,
        ]);
    }



    public function webhook(Request $request)
    {
        $payload = $request->all();
        // Log::info('Tamara webhook received', $payload);

        $referenceId = $payload['order_reference_id'] ?? null;
        $status = $payload['status'] ?? null;

        if (!$referenceId || !$status) {
            return response()->json(['error' => 'Missing data'], 400);
        }

        $payment = TamaraPayment::where('reference_id', $referenceId)->first();

        if ($payment) {
            $payment->status = strtolower($status);
            $payment->save();

            // Optionally update appointment status here
        }

        return response()->json(['message' => 'Webhook processed successfully']);
    }


    public function newNotification(Request $request)
    {
        $url = $request->path();
        preg_match('/reference_id=([^\/]+)/', $url, $match);
        $referenceId1 = $match[1] ?? null;

        $referenceId =  $referenceId1 ?? $request->query('reference_id');
        $parts = explode("sales_order_id=", $url);
        $sales_order_id = $parts[1] ?? null;

        try {
            $payment = TamaraPayment::where('reference_id', $request->reference_id)->orderByDesc('id')->first();


            if (!$payment) {
                return response()->json(['status' => false, 'message' => 'Payment not found']);
            }
            // ✅ Mark payment as paid
            $payment->update(['status' => 'paid']);
            // auth and capture process
            $orderId = $payment->payment_id;
            try {
                // Call Tamara API for this order
                $statusResponse = app(TamaraService::class)->getOrderStatus($orderId);
                if (isset($statusResponse['status'])) {
                    if ($statusResponse['status'] === 'approved') {
                        $authResponse = app(TamaraService::class)->authorizeOrder($orderId);
                        // $captureResponse = app(TamaraService::class)->captureOrderNew($referenceId, $orderId);

                        if (isset($authResponse['status']) && $authResponse['status'] === 'authorised') {
                            $captureResponse = app(TamaraService::class)->captureOrderNew($referenceId, $orderId);
                        }
                    } elseif ($statusResponse['status'] === 'authorised') {
                        $captureResponse = app(TamaraService::class)->captureOrderNew($referenceId, $orderId);
                    }

                    // Mark success regardless of status flow

                }
            } catch (\Throwable $e) {
                \Log::error('getAppointmentBySalesOrder failed: ' . $e->getMessage());
            }

            return response()->json(['status' => true, 'message' => 'Notification processed successfully']);
        } catch (\Throwable $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()]);
        }
    }
}
