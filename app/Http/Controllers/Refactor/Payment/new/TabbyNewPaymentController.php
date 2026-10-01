<?php

namespace App\Http\Controllers\Refactor\Payment\new;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\TabbyPayment;
use App\Repositories\Order\OrderRepository;
use App\Services\Payments\TabbyGateway;
use Illuminate\Http\Request;

class TabbyNewPaymentController extends Controller
{
    public function __construct(
        private TabbyGateway $tabby,
        private OrderRepository $orders
    ) {}

    public function success(Request $request)
    {
        $orderId = $request->query('order_id');
        if (!$orderId) {
            return response()->json(['message' => 'Missing order_id'], 422);
        }

        $order = Order::find($orderId);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // لو already paid
        if ($order->payment_status === 'paid') {
            $woo = $this->orders->markPaidAndSyncWoo($order);
            return response()->json(['message' => 'Already paid', 'order_id' => $order->id, 'woo' => $woo]);
        }

        $tabbyPayment = TabbyPayment::where('order_id', (string)$order->id)->latest('id')->first();
        if (!$tabbyPayment || !$tabbyPayment->payment_id) {
            return response()->json(['message' => 'Tabby payment not found'], 404);
        }

        $verify = $this->tabby->retrievePaymentStatus($tabbyPayment->payment_id);
        if (!$verify['ok']) {
            return response()->json(['message' => 'Failed to verify Tabby payment', 'data' => $verify['data']], 502);
        }

        $status = strtoupper((string)($verify['data']['status'] ?? ''));

        // أمثلة شائعة: CLOSED (مدفوع/مقفول), EXPIRED (انتهى), REJECTED (فشل) :contentReference[oaicite:3]{index=3}
        if (in_array($status, ['CLOSED', 'CAPTURED', 'AUTHORIZED'], true)) {
            $tabbyPayment->status = $status;
            $tabbyPayment->save();

            $woo = $this->orders->markPaidAndSyncWoo($order);

            return response()->json([
                'message' => 'Payment paid',
                'order_id' => $order->id,
                'payment_status' => $order->fresh()->payment_status,
                'woo' => $woo,
            ]);
        }

        if (in_array($status, ['EXPIRED'], true)) {
            $order->update(['payment_status' => 'expired']);
            $tabbyPayment->update(['status' => $status]);
            return response()->json(['message' => 'Payment expired', 'order_id' => $order->id]);
        }

        if (in_array($status, ['REJECTED', 'DECLINED', 'FAILED'], true)) {
            $order->update(['payment_status' => 'failed']);
            $tabbyPayment->update(['status' => $status]);
            return response()->json(['message' => 'Payment failed', 'order_id' => $order->id]);
        }

        // لو لسه pending
        $tabbyPayment->update(['status' => $status ?: 'pending']);
        return response()->json(['message' => 'Payment still pending', 'order_id' => $order->id, 'status' => $status]);
    }

    public function cancel(Request $request)
    {
        $orderId = $request->query('order_id');
        if ($orderId && ($order = Order::find($orderId))) {
            $order->update(['payment_status' => 'canceled']);
        }
        return response()->json(['message' => 'Canceled']);
    }

    public function failure(Request $request)
    {
        $orderId = $request->query('order_id');
        if ($orderId && ($order = Order::find($orderId))) {
            $order->update(['payment_status' => 'failed']);
        }
        return response()->json(['message' => 'Failed']);
    }
}
