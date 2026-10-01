<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\User;
use App\Models\Address2;
use App\Models\TabbyPayment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class TabbyGateway
{
    private string $baseUrl;
    private string $secret;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.tabby.base_url', 'https://api.tabby.ai/api/v2/'), '/') . '/';
        $this->secret  = (string) config('services.tabby.secret_key');
    }

    public function checkout(Order $order, User $user, Address2 $address, Collection $cartProducts): array
    {
        $items = $cartProducts->map(function ($cartItem) {
            $p = $cartItem->product;
            $qty = (int) $cartItem->quantity;
            $unit = (float) ($p->price ?? 0);

            return [
                "title" => $p->name ?? "Product",
                "description" => $p->short_description ?? ($p->description ?? "Product"),
                "quantity" => $qty,
                "unit_price" => number_format($unit, 2, '.', ''),
                "discount_amount" => "0.00",
                "reference_id" => (string) ($p->id ?? ''),
                "category" => "E-Commerce",
                "is_refundable" => true,
            ];
        })->toArray();

        $payload = [
            "payment" => [
                "amount" => number_format((float)$order->total_price, 2, '.', ''),
                "currency" => $order->currency ?? "SAR",
                "description" => "Order #{$order->id}",
                "buyer" => [
                    "phone" => preg_replace('/\D/', '', (string) $user->phone),
                    "email" => $user->email,
                    "name" => $user->username ?? ($user->first_name ?? 'Customer'),
                    "dob" => $user->birth_date ?? "1996-08-24",
                ],
                "shipping_address" => [
                    "city" => $address->city ?? "Riyadh",
                    "address" => trim(($address->country ?? "SA") . " " . ($address->street ?? "")),
                    "zip" => $address->postcode ?? "1234",
                ],
                "order" => [
                    "tax_amount" => number_format((float)$order->tax, 2, '.', ''),
                    "shipping_amount" => "0.00",
                    "discount_amount" => number_format((float)$order->discount_total, 2, '.', ''),
                    "reference_id" => (string) $order->id,
                    "items" => $items,
                ],
            ],
            "lang" => "ar",
            "merchant_code" => "SA",
            "merchant_urls" => [
                "success" => route('new.tabby.success', ['order_id' => $order->id]),
                "cancel"  => route('new.tabby.cancel', ['order_id' => $order->id]),
                "failure" => route('new.tabby.failure', ['order_id' => $order->id]),
            ],
        ];

        $res = Http::baseUrl($this->baseUrl)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->secret,
                'Content-Type'  => 'application/json',
            ])->post('checkout', $payload);

        if (!$res->successful()) {
            return [
                'payment_id' => null,
                'payment_url' => null,
                'provider' => 'tabby',
                'extra' => ['error' => true, 'response' => $res->json()],
            ];
        }

        $json = $res->json();

        $paymentId = $json['payment']['id'] ?? null;
        $sessionId = $json['id'] ?? ($json['payment']['id'] ?? null);
        $webUrl    = $json['configuration']['available_products']['installments'][0]['web_url'] ?? null;
        $status    = $json['payment']['status'] ?? ($json['status'] ?? 'created');

        TabbyPayment::create([
            'reference_id' => (string) $order->id,
            'order_id'     => (string) $order->id,
            'payment_id'   => $paymentId,
            'session_id'   => $sessionId,
            'session_url'  => $webUrl,
            'status'       => $status,
            'user_id'      => $user->id,
        ]);

        return [
            'payment_id' => $paymentId,
            'payment_url' => $webUrl,
            'provider' => 'tabby',
            'extra' => $json,
        ];
    }

    public function retrievePaymentStatus(string $paymentId): array
    {
        $res = Http::baseUrl($this->baseUrl)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->secret,
                'Content-Type'  => 'application/json',
            ])->get("payments/{$paymentId}");

        return [
            'ok' => $res->successful(),
            'data' => $res->json(),
        ];
    }
}
