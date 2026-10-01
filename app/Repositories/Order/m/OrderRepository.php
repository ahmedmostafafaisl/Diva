<?php

namespace App\Repositories;

use App\Helper\ApiResponseHelper;
use App\Models\Address2;
use App\Models\Cart;
use App\Models\Order;
use App\Models\TabbyPayment;
use App\Models\TamaraPayment;
use App\Repositories\Interfaces\CartRepositoryInterface;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use App\Services\Payments\PaymentGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderRepository extends OrderRepositoryInterface
{
 use ApiResponseHelper;
    protected $firebaseNotificationService;

    protected $wooService;

    protected $cartRepository;

     public function __construct(CartRepositoryInterface $cartRepository, FirebaseNotificationService $firebaseNotificationService, WooOrderService $wooService,   private PaymentGateway $payments)
    {
        $this->wooService = $wooService;
        $this->cartRepository = $cartRepository;
        $this->firebaseNotificationService = $firebaseNotificationService;
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
                $order->products()->attach($product['id'], ['quantity' => $product['quantity']]);
            }
        }

        if (isset($user)) {
            if (!empty($user->fcm_token)) {
                $title = $request->title ?? ' تحديث طلبك   ';
                $body =  $request->body ?? ' تم تحديث طلبك';
                $data = [
                    'order_id' => $order->id,
                    'order_status' => $order->status,
                    'order_total' => $order->total_price,
                    'order_date' => $order->created_at,
                ];
                $response = $this->firebaseNotificationService->sendNotification($user->fcm_token,  $user->id, $title, $body, $data ?? []);
            }
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
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $phone = str_replace('+', '', $user->phone);

        $orderData = $this->wooService->getOrderByPhone($phone);
        // return $this->setCode(code: 200)->setData($orderData)->setMessage('success')->send();
        if (empty($orderData) || !is_array($orderData)) {
            return $this->setCode(code: 200)->setData($orderData)->setMessage('success')->send();
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

        // Make sure the status key exists
        if (!array_key_exists($status, $statusMap)) {
            return []; // or throw exception or return all if you want
        }

        // Filter the orders
        $filteredOrders = collect($orderData)->filter(function ($order) use ($statusMap, $status) {
            return in_array($order['status'], $statusMap[$status]);
        })->values()->all();
        return $this->setCode(code: 200)->setData($filteredOrders)->setMessage('success')->send();

        if ($status == 'current') {
            return Order::where('user_id', auth()->id())->whereIn('status', ['pending', 'shipped'])->with('products')->get();
        } elseif ($status == 'previous') {
            return Order::where('user_id', auth()->id())->whereIn('status', ['completed', 'canceled'])->with('products')->get();
        } else
            return Order::where('user_id', auth()->id())->get();
    }

    public function store(array $data)
    {
        if (!auth()->check()) {
            return $this->setCode(401)->setData([])->setMessage('Unauthorized')->send();
        }

        $user = auth()->user();

        $cart = Cart::where('user_id', $user->id)->with('products.product')->first();
        if (!$cart) {
            return $this->setCode(404)->setData([])->setMessage('Cart not found')->send();
        }

        $products = $cart->products;
        if ($products->isEmpty()) {
            return $this->setCode(404)->setData([])->setMessage('No products in the cart')->send();
        }

        $address = Address2::where('user_id', $user->id)->where('default', 1)->first();
        if (!$address) {
            return $this->setCode(404)->setData([])->setMessage('Default address not found')->send();
        }

        $paymentMethod = $data['payment_method'];
        $currency = $data['currency'] ?? 'SAR';

        // ====== totals ======
        $totalPrice = 0;
        $totalQuantity = 0;
        $totalTax = 0;
        $totalDiscount = 0;

        foreach ($products as $cartItem) {
            $p = $cartItem->product;
            $quantity = (int) $cartItem->quantity;

            $price = (float) ($p->price ?? 0);
            $regular = $p->regular_price ?? null;
            $sale = $p->sale_price ?? null;

            $totalQuantity += $quantity;
            $totalPrice += $price * $quantity;

            $productTax = 0.15 * $price * $quantity;
            $totalTax += $productTax;

            if (!is_null($regular) && !is_null($sale) && (float)$regular != (float)$sale) {
                $totalDiscount += ((float)$regular - (float)$sale) * $quantity;
            }
        }

        // ====== create order + attach products + delete cart (IMMEDIATELY) ======
        $order = null;

        DB::transaction(function () use (
            &$order, $user, $address, $products, $paymentMethod, $currency,
            $totalTax, $totalQuantity, $totalPrice, $totalDiscount, $data, $cart
        ) {
            $isApplePay = $paymentMethod === 'apple_pay';

            $orderTransaction = null;
            if ($paymentMethod === 'tamara') {
                // reference ثابت لتمارا
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
                'payment_provider' => $paymentMethod, // نفس القيمة

                'status' => 'pending',
                'payment_status' => $isApplePay ? 'paid' : 'pending',
                'paid_at' => $isApplePay ? now() : null,
            ]);

            // attach products pivot
            foreach ($products as $cartItem) {
                $p = $cartItem->product;
                $order->products()->attach($p->id, [
                    'quantity' => (int) $cartItem->quantity,
                    'standard' => $cartItem->standard,
                    'right_standard' => $cartItem->right_standard,
                    'right_quantity' => $cartItem->right_quantity,
                    'left_standard' => $cartItem->left_standard,
                    'left_quantity' => $cartItem->left_quantity,
                    'price' => (float) ($p->price ?? 0),
                ]);
            }

            // ✅ امسح الكارت مباشرة بعد إنشاء الأوردر
            $cart->products()->delete();
        });

        // ====== after order created ======
        // Apple Pay => send to Woo immediately
        if ($paymentMethod === 'apple_pay') {
            $woo = $this->sendToWooIfNeeded($order);
            return $this->setCode(200)->setData([
                'order_id' => $order->id,
                'payment_status' => $order->payment_status,
                'status' => $order->status,
                'woo' => $woo,
            ])->setMessage('Success')->send();
        }

        // Tabby/Tamara => init checkout inside create order
        $checkout = $this->payments->initCheckout($paymentMethod, $order, $user, $address, $products);

        if (!empty($checkout['extra']['error'])) {
            // فشل إنشاء Checkout => نخلي الدفع failed (أو pending حسب رغبتك)
            $order->update([
                'payment_status' => 'failed',
            ]);

            return $this->setCode(500)->setData([
                'order_id' => $order->id,
                'payment_status' => $order->payment_status,
                'error' => $checkout['extra'],
            ])->setMessage('Checkout failed')->send();
        }

        // update order with payment_id + url
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
            'reference_id' => $order->order_transaction, // لتمارا
        ])->setMessage('Success')->send();
    }

    private function sendToWooIfNeeded(Order $order): array
    {
        if (!in_array($order->payment_status, ['paid'], true)) {
            return ['sent' => false, 'reason' => 'payment_not_paid'];
        }
        if (!empty($order->woo_order_id)) {
            return ['sent' => false, 'reason' => 'already_sent', 'woo_order_id' => $order->woo_order_id];
        }

        // build data for Woo from order
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
            'products' => $order->products->map(function ($p) {
                return [
                    'product_id' => $p->id,
                    'quantity' => (int) $p->pivot->quantity,
                    'price' => (float) $p->pivot->price,
                    'variation_id' => $p->variation_id ?? null,
                ];
            }),
        ];

        // ⚠️ لازم تعدّل WooService عندك: set_paid يكون true فقط هنا
        $wooResponse = $this->wooService->SendOrder($payload, true);

        // حاول تستخرج woo order id (حسب response عندك)
        $wooId = null;
        if (is_array($wooResponse) && isset($wooResponse[0]['id'])) {
            $wooId = (int) $wooResponse[0]['id'];
        }

        $order->update([
            'woo_order_id' => $wooId ?: $order->woo_order_id,
        ]);

        return ['sent' => true, 'woo_order_id' => $wooId, 'response' => $wooResponse];
    }

    // ✅ استخدمها بعد نجاح tabby/tamara
    public function markPaidAndSyncWoo(Order $order): array
    {
        $order->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);

        return $this->sendToWooIfNeeded($order);
    }
}
