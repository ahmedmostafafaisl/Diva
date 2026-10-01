<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\User;
use App\Models\Address2;
use App\Models\TamaraPayment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TamaraGateway
{
    public function __construct(private TamaraHttpClient $client) {}

    public function checkout(Order $order, User $user, Address2 $address, Collection $cartProducts): array
    {
        // reference_id ثابت وتم ربطه بالأوردر في create order
        $referenceId = $order->order_transaction ?: strtoupper(Str::random(12));

        // أنشئ سجل tamara_payment + items (pending)
        $tamaraPayment = DB::transaction(function () use ($order, $user, $referenceId, $cartProducts) {
            $payment = TamaraPayment::create([
                'user_id' => $user->id,
                'amount' => (float) $order->total_price,
                'discount' => (float) $order->discount_total,
                'reference_id' => $referenceId,
                'status' => 'created',
                'phone_number' => preg_replace('/\D/', '', (string) $user->phone),
            ]);

            foreach ($cartProducts as $cartItem) {
                $p = $cartItem->product;
                $payment->items()->create([
                    'item_id' => (string) ($p->id ?? ''),
                    'name' => $p->name ?? 'Item',
                    'description' => $p->short_description ?? null,
                    'quantity' => (int) $cartItem->quantity,
                    'price' => (float) ($p->price ?? 0),
                    'discount_amount' => 0,
                    'category' => 'E-Commerce',
                ]);
            }

            return $payment;
        });

        // Payload للـ Tamara checkout (مبسّط مع بياناتك الحالية)
        $payload = [
            "total_amount" => ["amount" => round((float)$order->total_price, 2), "currency" => $order->currency ?? "SAR"],
            "shipping_amount" => ["amount" => 0, "currency" => $order->currency ?? "SAR"],
            "tax_amount" => ["amount" => 0, "currency" => $order->currency ?? "SAR"],
            "order_reference_id" => $referenceId,
            "order_number" => $referenceId,
            "discount" => [
                "amount" => ["amount" => round((float)($order->discount_total ?? 0), 2), "currency" => $order->currency ?? "SAR"],
                "name" => "Discount"
            ],
            "items" => $tamaraPayment->items->map(function ($it) use ($referenceId, $order) {
                return [
                    "name" => $it->name,
                    "type" => "Physical",
                    "reference_id" => (string) $it->item_id,
                    "sku" => "SKU-" . $it->item_id,
                    "quantity" => (int) $it->quantity,
                    "discount_amount" => ["amount" => round((float)$it->discount_amount, 2), "currency" => $order->currency ?? "SAR"],
                    "tax_amount" => ["amount" => 0, "currency" => $order->currency ?? "SAR"],
                    "unit_price" => ["amount" => round((float)$it->price, 2), "currency" => $order->currency ?? "SAR"],
                    "total_amount" => ["amount" => round(((float)$it->price) * ((int)$it->quantity), 2), "currency" => $order->currency ?? "SAR"],
                ];
            })->toArray(),
            "consumer" => [
                "email" => $user->email ?? "customer@example.com",
                "first_name" => $user->first_name ?? "Customer",
                "last_name" => $user->last_name ?? "User",
                "phone_number" => preg_replace('/\D/', '', (string) $user->phone),
            ],
            "country_code" => "SA",
            "description" => "Order #{$order->id}",
            "merchant_url" => [
                "success" => route('new.tamara.success', ['reference_id' => $referenceId]),
                "cancel"  => route('new.tamara.cancel',  ['reference_id' => $referenceId]),
                "failure" => route('new.tamara.failure', ['reference_id' => $referenceId]),
                "notification" => route('new.tamara.notification', ['reference_id' => $referenceId]),
            ],
            "payment_type" => "PAY_BY_INSTALMENTS",
            "instalments" => 3,
            "billing_address" => [
                "city" => $address->city ?? "Riyadh",
                "country_code" => "SA",
                "first_name" => $user->first_name ?? "Customer",
                "last_name" => $user->last_name ?? "User",
                "line1" => $address->street ?? "Street",
                "line2" => $address->location_note ?? "",
                "phone_number" => preg_replace('/\D/', '', (string) $user->phone),
                "region" => $address->state ?? "Region",
            ],
            "shipping_address" => [
                "city" => $address->city ?? "Riyadh",
                "country_code" => "SA",
                "first_name" => $user->first_name ?? "Customer",
                "last_name" => $user->last_name ?? "User",
                "line1" => $address->street ?? "Street",
                "line2" => $address->location_note ?? "",
                "phone_number" => preg_replace('/\D/', '', (string) $user->phone),
                "region" => $address->state ?? "Region",
            ],
            "platform" => "Diva E-Commerce",
            "is_mobile" => true,
            "locale" => "ar_SA",
        ];

        $resp = $this->client->postCheckout($payload);

        if (!($resp['ok'] ?? false)) {
            return [
                'payment_id' => null,
                'payment_url' => null,
                'provider' => 'tamara',
                'extra' => ['error' => true, 'response' => $resp],
            ];
        }

        $data = $resp['data'] ?? [];
        $paymentId = $data['order_id'] ?? null;
        $checkoutUrl = $data['checkout_url'] ?? null;

        $tamaraPayment->update([
            'payment_id' => $paymentId,
            'checkout_url' => $checkoutUrl,
            'status' => $data['status'] ?? 'created',
        ]);

        return [
            'payment_id' => $paymentId,
            'payment_url' => $checkoutUrl,
            'provider' => 'tamara',
            'extra' => $data,
        ];
    }
}
