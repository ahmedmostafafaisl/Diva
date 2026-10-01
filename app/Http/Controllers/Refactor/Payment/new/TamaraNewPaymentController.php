<?php

namespace App\Http\Controllers\Refactor\Payment\new;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\TamaraPayment;
use App\Repositories\Order\OrderRepository;
use App\Services\Payment\TamaraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TamaraNewPaymentController extends Controller
{
    public function __construct(private OrderRepository $orders) {}

    /**
     * ✅ SUCCESS:
     * - update status
     * - send order to Woo
     * - return response like Tabby success (same keys/messages)
     */
    public function newSuccess(Request $request, string $reference_id)
    {
        if (!$reference_id) {
            return response()->json(['message' => 'Missing reference_id'], 422);
        }

        // 1) get order by reference_id stored in orders.order_transaction
        $order = Order::where('order_transaction', $reference_id)->first();
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // 2) already paid (same as tabby)
        if ($order->payment_status === 'paid') {
            $woo = $this->orders->markPaidAndSyncWoo($order);
            return response()->json([
                'message' => 'Already paid',
                'order_id' => $order->id,
                'woo' => $woo,
            ]);
        }

        // 3) get tamara payment row
        $tamaraPayment = TamaraPayment::where('reference_id', $reference_id)->latest('id')->first();
        if (!$tamaraPayment || !$tamaraPayment->payment_id) {
            return response()->json(['message' => 'Tamara payment not found'], 404);
        }

        /** @var TamaraService $tamara */
        $tamara = app(TamaraService::class);

        // 4) verify status from tamara
        $verify = $tamara->getOrderStatus((string) $tamaraPayment->payment_id);

        if (!is_array($verify) || !isset($verify['status']) || !empty($verify['error'])) {
            return response()->json([
                'message' => 'Failed to verify Tamara payment',
                'data' => $verify
            ], 502);
        }

        $status = strtoupper((string)($verify['status'] ?? ''));

        // ✅ paid statuses (including FULLY_CAPTURED)
        $paidStatuses = ['CAPTURED', 'FULLY_CAPTURED', 'PAID', 'COMPLETED', 'SUCCESS'];
        $expiredStatuses = ['EXPIRED'];
        $failedStatuses = ['DECLINED', 'REJECTED', 'FAILED'];
        $canceledStatuses = ['CANCELLED', 'CANCELED'];

        // helper: finalize as paid (update + send woo + return tabby paid response)
        $finalizePaid = function () use ($order, $tamaraPayment) {
            $tamaraPayment->update(['status' => 'paid']);

            // markPaidAndSyncWoo should update order.payment_status='paid' + send Woo (once)
            $woo = $this->orders->markPaidAndSyncWoo($order);

            return response()->json([
                'message' => 'Payment paid',
                'order_id' => $order->id,
                'payment_status' => $order->fresh()->payment_status,
                'woo' => $woo,
            ]);
        };

        // 5) if already paid status from Tamara
        if (in_array($status, $paidStatuses, true)) {
            return $finalizePaid();
        }

        // 6) APPROVED => AUTHORISE => CAPTURE => recheck
        if ($status === 'APPROVED') {

            $auth = $tamara->authorizeOrder((string) $tamaraPayment->payment_id);
            $authStatus = strtoupper((string)($auth['status'] ?? ''));

            // update payment status locally
            $tamaraPayment->update(['status' => strtolower($authStatus ?: 'approved')]);

            if (in_array($authStatus, ['AUTHORISED', 'AUTHORIZED'], true)) {
                // capture (captureOrderNew returns success/data not status)
                $cap = $tamara->captureOrderNew($reference_id, (string)$tamaraPayment->payment_id);

                // recheck after capture
                $verify2 = $tamara->getOrderStatus((string) $tamaraPayment->payment_id);
                $status2 = strtoupper((string)($verify2['status'] ?? ''));

                $tamaraPayment->update(['status' => strtolower($status2 ?: 'pending')]);

                if (in_array($status2, $paidStatuses, true)) {
                    return $finalizePaid();
                }

                if (in_array($status2, $expiredStatuses, true)) {
                    $order->update(['payment_status' => 'expired']);
                    $tamaraPayment->update(['status' => 'expired']);
                    return response()->json(['message' => 'Payment expired', 'order_id' => $order->id]);
                }

                if (in_array($status2, $failedStatuses, true)) {
                    $order->update(['payment_status' => 'failed']);
                    $tamaraPayment->update(['status' => strtolower($status2)]);
                    return response()->json(['message' => 'Payment failed', 'order_id' => $order->id]);
                }

                if (in_array($status2, $canceledStatuses, true)) {
                    $order->update(['payment_status' => 'canceled']);
                    $tamaraPayment->update(['status' => 'canceled']);
                    return response()->json(['message' => 'Payment canceled', 'order_id' => $order->id]);
                }

                // pending (same as tabby)
                return response()->json([
                    'message' => 'Payment still pending',
                    'order_id' => $order->id,
                    'status' => $status2 ?: 'PENDING',
                ]);
            }

            // still pending (same as tabby)
            return response()->json([
                'message' => 'Payment still pending',
                'order_id' => $order->id,
                'status' => $authStatus ?: 'APPROVED',
            ]);
        }

        // 7) AUTHORISED => CAPTURE => recheck
        if (in_array($status, ['AUTHORISED', 'AUTHORIZED'], true)) {

            $tamaraPayment->update(['status' => 'authorised']);

            $cap = $tamara->captureOrderNew($reference_id, (string)$tamaraPayment->payment_id);

            $verify2 = $tamara->getOrderStatus((string) $tamaraPayment->payment_id);
            $status2 = strtoupper((string)($verify2['status'] ?? ''));

            $tamaraPayment->update(['status' => strtolower($status2 ?: 'pending')]);

            if (in_array($status2, $paidStatuses, true)) {
                return $finalizePaid();
            }

            if (in_array($status2, $expiredStatuses, true)) {
                $order->update(['payment_status' => 'expired']);
                $tamaraPayment->update(['status' => 'expired']);
                return response()->json(['message' => 'Payment expired', 'order_id' => $order->id]);
            }

            if (in_array($status2, $failedStatuses, true)) {
                $order->update(['payment_status' => 'failed']);
                $tamaraPayment->update(['status' => strtolower($status2)]);
                return response()->json(['message' => 'Payment failed', 'order_id' => $order->id]);
            }

            if (in_array($status2, $canceledStatuses, true)) {
                $order->update(['payment_status' => 'canceled']);
                $tamaraPayment->update(['status' => 'canceled']);
                return response()->json(['message' => 'Payment canceled', 'order_id' => $order->id]);
            }

            return response()->json([
                'message' => 'Payment still pending',
                'order_id' => $order->id,
                'status' => $status2 ?: 'PENDING',
            ]);
        }

        // 8) EXPIRED (same as tabby)
        if (in_array($status, $expiredStatuses, true)) {
            $order->update(['payment_status' => 'expired']);
            $tamaraPayment->update(['status' => 'expired']);
            return response()->json(['message' => 'Payment expired', 'order_id' => $order->id]);
        }

        // 9) FAILED (same as tabby)
        if (in_array($status, $failedStatuses, true)) {
            $order->update(['payment_status' => 'failed']);
            $tamaraPayment->update(['status' => strtolower($status)]);
            return response()->json(['message' => 'Payment failed', 'order_id' => $order->id]);
        }

        // 10) CANCELED
        if (in_array($status, $canceledStatuses, true)) {
            $order->update(['payment_status' => 'canceled']);
            $tamaraPayment->update(['status' => 'canceled']);
            return response()->json(['message' => 'Payment canceled', 'order_id' => $order->id]);
        }

        // 11) pending (same as tabby)
        $tamaraPayment->update(['status' => strtolower($status ?: 'pending')]);

        return response()->json([
            'message' => 'Payment still pending',
            'order_id' => $order->id,
            'status' => $status
        ]);
    }

    /**
     * ✅ NOTIFICATION:
     * make it same old function (no woo sending here)
     */
    public function newNotification(Request $request, string $reference_id)
    {
        try {
            // same idea as old:
            $ref = $request->reference_id ?? $reference_id;

            $payment = TamaraPayment::where('reference_id', $ref)->orderByDesc('id')->first();
            if (!$payment) {
                return response()->json(['status' => false, 'message' => 'Payment not found']);
            }

            // ✅ Mark payment as paid (same old behavior)
            $payment->update(['status' => 'paid']);

            // auth and capture process
            $orderId = (string) $payment->payment_id;

            try {
                $tamara = app(TamaraService::class);

                $statusResponse = $tamara->getOrderStatus($orderId);
                $st = strtolower((string)($statusResponse['status'] ?? ''));

                if ($st === 'approved') {
                    $authResponse = $tamara->authorizeOrder($orderId);
                    $authSt = strtolower((string)($authResponse['status'] ?? ''));

                    if ($authSt === 'authorised' || $authSt === 'authorized') {
                        $tamara->captureOrderNew($ref, $orderId);
                    }
                } elseif ($st === 'authorised' || $st === 'authorized') {
                    $tamara->captureOrderNew($ref, $orderId);
                }
            } catch (\Throwable $e) {
                Log::error('Tamara notification process failed: ' . $e->getMessage());
            }

            return response()->json(['status' => true, 'message' => 'Notification processed successfully']);
        } catch (\Throwable $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()]);
        }
    }
}
