<?php

namespace App\Http\Controllers;

use App\Models\Address2;
use App\Models\TabbyPayment;
use Illuminate\Http\Request;
use App\Services\TabbyService;
use App\Helper\ApiResponseHelper;

class TabbyPaymentController extends Controller
{
    use ApiResponseHelper;
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



        $tabby = new TabbyService();
        $response = $tabby->checkout($data);


        // return $response;

        if (
            isset($response['status']) &&
            $response['status'] === 'created' &&
            isset($response['configuration']['available_products']['installments'][0]['web_url'])
        ) {
            $webUrl = $response['configuration']['available_products']['installments'][0]['web_url'];
            //save tabby info
            $tabby_payment = new TabbyPayment();
            $tabby_payment->user_id =  $user->id;
            $tabby_payment->amount =  $data['amount'];
            $tabby_payment->session_id =  $response['id'];
            $tabby_payment->session_url =  $webUrl;
            $tabby_payment->order_id = $data['order_id'];
            $tabby_payment->save();
            return response()->json(['web_url' => $webUrl], 200);
        }

        return $response;
    }
    // status


    public function success(Request $request)
    {
        $payment = TabbyPayment::where('order_id', $request->order_id)->orderByDesc('created_at')->first();
        $payment->payment_id = $request->payment_id;
        $payment->save();

        $tabby = new TabbyService();
        $tabbyPayment =$tabby->retrieveTabbyPayment($request->payment_id);
       
        if ($tabbyPayment['status'] === 'AUTHORIZED') {
             $payment = $tabby->capturePaymentRequest($request->payment_id, $request->order_id, $payment->amount);
        }
        $payment->update([
            'status' => 'successful',
        ]);
        // Handle successful payment
        return response()->json(['message' => 'Payment successful']);
    }

    public function cancel()
    {
        // Handle canceled payment
        return response()->json(['message' => 'Payment canceled']);
    }

    public function failure()
    {
        // Handle failed payment
        return response()->json(['message' => 'Payment failed']);
    }


}
