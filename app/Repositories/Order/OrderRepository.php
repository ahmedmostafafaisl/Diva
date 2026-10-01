<?php

namespace App\Repositories\Order;

use App\Helper\ApiResponseHelper;
use App\Models\Address2;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Variation;
use App\Repositories\Interfaces\CartRepositoryInterface;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use App\Services\FirebaseNotificationService;
use App\Services\Payments\PaymentGateway;
use App\Services\WooOrderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderRepository implements OrderRepositoryInterface
{
    use ApiResponseHelper;

    protected $firebaseNotificationService;

    protected $wooService;

    protected $cartRepository;

    protected $paymentsGetway;

    public function __construct(
        CartRepositoryInterface $cartRepository,
        FirebaseNotificationService $firebaseNotificationService,
        WooOrderService $wooService,
        PaymentGateway $paymentsGetway
    ) {
        $this->wooService = $wooService;
        $this->cartRepository = $cartRepository;
        $this->firebaseNotificationService = $firebaseNotificationService;
        $this->paymentsGetway = $paymentsGetway;
    }

    public function all($status)
    {
        if ($status == 'current') {
            return Order::whereIn('status', ['pending', 'shipped'])->with('products')->get();
        }

        if ($status == 'previous') {
            return Order::whereIn('status', ['completed', 'canceled'])->with('products')->get();
        }

        return Order::with('products')->get();
    }

    public function find($id)
    {
        return Order::with('products')->findOrFail($id);
    }

    public function update($id, array $data)
    {
        $order = Order::findOrFail($id);
        $order->update($data);
        $user = $order->user;

        if (isset($data['products'])) {
            $order->products()->detach();

            foreach ($data['products'] as $product) {
                $order->products()->attach($product['id'], [
                    'quantity' => $product['quantity'] ?? 1,
                ]);
            }
        }

        if (isset($user) && ! empty($user->fcm_token)) {
            $title = 'تحديث طلبك';
            $body = 'تم تحديث طلبك';

            $notificationData = [
                'order_id' => $order->id,
                'order_status' => $order->status,
                'order_total' => $order->total_price,
                'order_date' => $order->created_at,
            ];

            $this->firebaseNotificationService->sendNotification(
                $user->fcm_token,
                $user->id,
                $title,
                $body,
                $notificationData
            );
        }

        return $order;
    }

    public function delete($id)
    {
        return Order::destroy($id);
    }

    public function getAuthUserOrders($status)
    {
        $user = auth()->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $phone = str_replace('+', '', $user->phone);
        $orderData = $this->wooService->getOrderByPhone($phone);

        if (empty($orderData) || ! is_array($orderData)) {
            return $this->setCode(200)->setData($orderData)->setMessage('success')->send();
        }

        $statusMap = [
            'current' => [
                'pending',
                'prepared',
                'shipped',
                'processing',
                'packaging-done',
                'on-hold',
                'vendor_processed',
                'partial-shipped',
            ],
            'previous' => [
                'completed',
                'canceled',
                'return-requested',
                'refunded',
                'return-approved',
                'return-cancelled',
                'cancelled',
                'failed',
                'checkout-draft',
            ],
        ];

        if (! array_key_exists($status, $statusMap)) {
            return $this->setCode(200)->setData([])->setMessage('success')->send();
        }

        $filteredOrders = collect($orderData)
            ->filter(function ($order) use ($statusMap, $status) {
                return in_array($order['status'], $statusMap[$status]);
            })
            ->values()
            ->all();

        return $this->setCode(200)->setData($filteredOrders)->setMessage('success')->send();
    }

    public function store(array $data)
    {
        if (! auth()->check()) {
            return $this->setCode(401)->setData([])->setMessage('Unauthorized')->send();
        }

        $user = auth()->user();

        $cart = Cart::where('user_id', $user->id)
            ->with([
                'products.product',
                'products.variation',
            ])
            ->first();

        if (! $cart) {
            return $this->setCode(404)->setData([])->setMessage('Cart not found')->send();
        }

        $products = $cart->products;

        if ($products->isEmpty()) {
            return $this->setCode(404)->setData([])->setMessage('No products in the cart')->send();
        }

        $address = Address2::where('user_id', $user->id)->where('default', 1)->first();

        if (! $address) {
            return $this->setCode(404)->setData([])->setMessage('Default address not found')->send();
        }

        $paymentMethod = $data['payment_method'];
        $currency = $data['currency'] ?? 'SAR';

        $totalPrice = 0.0;
        $totalQuantity = 0;
        $totalTax = 0.0;
        $totalDiscount = 0.0;

        foreach ($products as $cartItem) {
            $p = $cartItem->product;

            if (! $p) {
                continue;
            }

            $lineTotal = (float) ($cartItem->line_total ?? 0);
            $unitPrice = (float) ($cartItem->unit_price ?? 0);

            $effectiveQty = (int) ($cartItem->quantity ?? 0);

            if ($cartItem->variation_id) {
                $effectiveQty = max(
                    (int) ($cartItem->quantity ?? 0),
                    (int) ($cartItem->right_quantity ?? 0),
                    (int) ($cartItem->left_quantity ?? 0),
                    1
                );
            } else {
                $effectiveQty = max($effectiveQty, 1);
            }

            $totalQuantity += $effectiveQty;
            $totalPrice += $lineTotal;

            if ((bool) ($p->tax ?? false)) {
                $taxPart = $lineTotal - ($lineTotal / 1.15);
                $totalTax += $taxPart;
            }

            $regular = (float) ($p->regular_price ?? 0);

            if ($regular > 0 && $unitPrice > 0 && $regular > $unitPrice) {
                $totalDiscount += ($regular - $unitPrice);
            }
        }

        $order = null;

        DB::transaction(function () use (
            &$order,
            $user,
            $address,
            $products,
            $paymentMethod,
            $currency,
            $totalTax,
            $totalQuantity,
            $totalPrice,
            $totalDiscount,
            $data,
            $cart
        ) {
            $isApplePay = in_array($paymentMethod, ['apple_pay', 'moyasar'], true);

            $orderTransaction = null;

            if ($paymentMethod === 'tamara') {
                do {
                    $orderTransaction = strtoupper(Str::random(12));
                } while (Order::where('order_transaction', $orderTransaction)->exists());
            }

            $order = Order::create([
                'user_id' => $user->id,
                'address_id' => $address->id,
                'order_transaction' => $orderTransaction,
                'currency' => $currency,
                'tax' => $totalTax,
                'total_amount' => $totalQuantity,
                'total_price' => $totalPrice,
                'discount_total' => $totalDiscount,
                'billing_email' => $data['billing_email'] ?? $user->email,
                'shipment_note' => $data['shipment_note'] ?? null,
                'payment_method' => $paymentMethod,
                'payment_provider' => $paymentMethod,
                'status' => 'pending',
                'payment_status' => $isApplePay ? 'paid' : 'pending',
                'paid_at' => $isApplePay ? now() : null,
            ]);

            foreach ($products as $cartItem) {
                $p = $cartItem->product;

                if (! $p) {
                    continue;
                }

                $order->products()->attach($p->id, [
                    'variation_id' => $cartItem->variation_id,
                    'quantity' => (int) ($cartItem->quantity ?? 1),
                    'standard' => $cartItem->standard,
                    'right_standard' => $cartItem->right_standard,
                    'right_quantity' => $cartItem->right_quantity,
                    'right_price' => $cartItem->right_price,
                    'left_standard' => $cartItem->left_standard,
                    'left_quantity' => $cartItem->left_quantity,
                    'left_price' => $cartItem->left_price,
                    'unit_price' => (float) ($cartItem->unit_price ?? 0),
                    'line_total' => (float) ($cartItem->line_total ?? 0),
                    'price' => (float) ($cartItem->unit_price ?? 0),
                ]);
            }

            $cart->products()->delete();
        });

        if (in_array($paymentMethod, ['apple_pay', 'moyasar'], true)) {
            $woo = $this->sendToWooIfNeeded($order);

            return $this->setCode(200)->setData([
                'order_id' => $order->id,
                'payment_status' => $order->payment_status,
                'status' => $order->status,
                'woo' => $woo,
            ])->setMessage('Success')->send();
        }

        $checkout = $this->paymentsGetway->initCheckout($paymentMethod, $order, $user, $address, $products);

        if (! empty($checkout['extra']['error'])) {
            $order->update([
                'payment_status' => 'failed',
            ]);

            return $this->setCode(500)->setData([
                'order_id' => $order->id,
                'payment_status' => $order->payment_status,
                'error' => $checkout['extra'],
            ])->setMessage('Checkout failed')->send();
        }

        $order->update([
            'payment_id' => $checkout['payment_id'],
            'payment_url' => $checkout['payment_url'],
            'payment_provider' => $checkout['provider'],
            'payment_status' => 'pending',
        ]);

        return $this->setCode(200)->setData([
            'order_id' => $order->id,
            'payment_status' => $order->payment_status,
            'status' => $order->status,
            'payment_provider' => $order->payment_provider,
            'payment_id' => $order->payment_id,
            'payment_url' => $order->payment_url,
            'reference_id' => $order->order_transaction,
        ])->setMessage('Success')->send();
    }

    private function sendToWooIfNeeded(Order $order): array
    {

        if (! in_array($order->payment_status, ['paid'], true)) {
            return ['sent' => false, 'reason' => 'payment_not_paid'];
        }

        if (! empty($order->woo_order_id)) {
            return [
                'sent' => false,
                'reason' => 'already_sent',
                'woo_order_id' => $order->woo_order_id,
            ];
        }

        $order->load(['user', 'address', 'products']);

        $payload = [
            'user' => $order->user,
            'address' => $order->address,
            'user_phone' => preg_replace('/\D/', '', (string) $order->user?->phone),
            'payment_method' => $order->payment_method,
            'payment_id' => $order->payment_id,
            'currency' => $order->currency,
            'subtotal' => $order->total_price,
            'total_price' => $order->total_price,
            'discount_total' => $order->discount_total,
            'tax' => $order->tax,
            'shipping_tax' => 0,
            'shipment_note' => $order->shipment_note,
            'products' => $order->products->map(function ($p) {
                $wooVariationId = null;

                if (! empty($p->pivot->variation_id)) {
                    $variation = Variation::find($p->pivot->variation_id);
                    $wooVariationId = $variation?->sku ? (int) $variation->sku : null;
                }

                return [
                    'product_id' => $p->id, // لو product.id عندك = Woo product id
                    'variation_id' => $wooVariationId,
                    'quantity' => (int) ($p->pivot->quantity ?? 1),
                    'price' => (float) ($p->pivot->line_total ?? $p->pivot->price ?? 0),

                    'standard' => $p->pivot->standard,
                    'right_standard' => $p->pivot->right_standard,
                    'right_quantity' => $p->pivot->right_quantity,
                    'right_price' => $p->pivot->right_price,
                    'left_standard' => $p->pivot->left_standard,
                    'left_quantity' => $p->pivot->left_quantity,
                    'left_price' => $p->pivot->left_price,
                    'unit_price' => $p->pivot->unit_price,
                    'line_total' => $p->pivot->line_total,

                    // مهم للتاكس
                    'taxable' => (bool) ($p->tax ?? false),
                ];
            })->values()->all(),
        ];
        Log::info('Sending order to WooCommerce', ['order_id' => $order->id, 'payload' => $payload]);

        $wooResponse = $this->wooService->SendOrder($payload);

        $wooId = null;

        if (is_array($wooResponse) && isset($wooResponse[0]['id'])) {
            $wooId = (int) $wooResponse[0]['id'];
        }

        $order->update([
            'woo_order_id' => $wooId ?: $order->woo_order_id,
        ]);

        return [
            'sent' => true,
            'woo_order_id' => $wooId,
            'response' => $wooResponse,
        ];
    }

    public function markPaidAndSyncWoo(Order $order): array
    {
        $order->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);

        return $this->sendToWooIfNeeded($order);
    }
}
