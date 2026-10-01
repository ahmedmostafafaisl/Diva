<?php

namespace App\Services;

use App\Models\TabbyPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TabbyService
{
    private $environment = 'test';
    private $tabbyBaseUrl = 'https://api.tabby.ai/api/v2/';
    private $merchantUrlsBase = "https://prod-api.naqiwash.com/api/tabby/";

    private $tabbySecretKey;
    private $tabbyPublicKey;



    public function __construct()
    {
        if ($this->environment == 'production') {
            // Live keys
            $this->tabbySecretKey = 'sk_0193afc6-2af1-5a98-c325-f2c02de66154';
            $this->tabbyPublicKey = 'pk_0193afc6-2af1-5a98-c325-f2bfefff9953';


        } else {
              // Test keys
            $this->tabbySecretKey = 'sk_test_01965838-358e-3ca0-7761-95af40bee90c';
            $this->tabbyPublicKey = 'pk_test_01965838-358e-3ca0-7761-95aeb9cb06df';
        }
    }
    // new payment

    // 1- create checkout



    public function checkout($data)
    {

        $now = Carbon::now();

        $http = Http::baseUrl($this->tabbyBaseUrl)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->tabbySecretKey,
                'Content-Type' => 'application/json'
            ]);

        // return $data['user']->phone;
        $response = $http->post("checkout",   [
            "payment" => [
                "amount" => $data['amount'],
                "currency" => "SAR",
                "description" => "Centrial Payment",
                "buyer" => [
                    "phone" => "500000001" ?? str_replace("+966", "", $data['user']->phone),
                    "email" =>  "card.success@tabby.ai" ?? $data['user']->email,
                    "name" => $data['user']->username ?? "Centerial Mall",
                    "dob" => $data['user']->birth_date ?? "1996-08-24",
                ],
                "shipping_address" =>  [
                    "city" => $data['address']['city'] ?? "Riyadh",
                    "address" => $data['address']['country'] ?? "Saudi Riyadh",
                    "zip" => $data['zip'] ?? "1234"
                ],
                "order" => [
                    "tax_amount" => $data['tax_amount'] ?? "0.00",
                    "shipping_amount" => $data['shipping_amount'] ?? "0.00",
                    "discount_amount" => $data['discount_amount'] ?? "0.00",
                    "updated_at" => $now,
                    "reference_id" => $data['order_id'],
                    "items" => collect($data['items'])->map(function ($item) use ($data) {
                        return [
                            "title" => $item['name'] ?? "product 1",
                            "description" => $item['description'] ?? "product 1",
                            "quantity" => (int) $item['quantity'],
                            "unit_price" => (string) $item['price'],
                            "discount_amount" => (string) $item['discount_amount'] ?? "0.00",
                            "reference_id" => (string) $item['id'],
                            "category" => "E-Commerce",
                            "is_refundable" => true,
                        ];
                    })->toArray(),
                ],

                "order_history" => null,
                "meta" => [
                    "order_id" => null,
                    "customer" => null
                ],
                "attachment" => null
            ],
            "lang" => "ar",
            "merchant_code" => "SA",
           "merchant_urls" => [
    "success" => route('tabby.success', [
        'order_id' => $data['order_id'],
    ]),
    "cancel" => route('tabby.cancel', [
        'order_id' => $data['order_id'],
    ]),
    "failure" => route('tabby.failure', [
        'order_id' => $data['order_id'],
    ]),
],

            "token" => null
        ]);

      if (!$response->successful()) {
        return $response->json();
    }

    $result = $response->json();

     TabbyPayment::create([
        // 'reference_id' => $result['payment']['order']['reference_id'] ?? null,
        'order_id'     => $data['order_id'],
        'payment_id'   => $result['payment']['id'] ?? null,
        'session_id'   => $result['payment']['id'] ?? null,
        'session_url'  => $result['configuration']['available_products']['installments'][0]['web_url'] ?? null,
        'status'       => $result['payment']['status'] ?? 'created',
        'user_id'      => $data['user']->id ?? null,
    ]);

    return $result;
    }

    public function capturePaymentRequest($payment_id, $reference_id, $amount)
    {
        $http = Http::withToken($this->tabbySecretKey)
            ->baseUrl($this->tabbyBaseUrl)

            ->withHeaders(['Content-Type' => 'application/json']);

        $response = $http->post("payments/$payment_id/captures", [
            'amount' => $amount,
            'currency' => 'SAR',
            "tax_amount" => "0.00",
            "shipping_amount" => "0.00",
            "discount_amount" => "0.00",
            'reference_id' => $reference_id,
        ]);

        return json_decode($response, true);
    }




    public function createSession($data)
    {
        $body = $this->getConfig($data);

        Log::info(json_encode($body));
        $http = Http::withToken($this->tabbyPublicKey)->baseUrl(url: $this->tabbyBaseUrl)->withHeaders(['Content-Type' => 'application/json']);
        $response = $http->post('checkout', data: $body);
        $response = json_decode($response->getBody()->getContents(), true);
        return $response;
    }

    public function getConfig($data)
    {
        $now = Carbon::now();

        return [
            "payment" => [
                "amount" => $data['amount'],
                "currency" => "SAR",
                "description" => "Centrial Payment",
                "buyer" => [
                    "phone" =>   $data['user']->phone,
                    "email" =>   $data['user']->email ?? "card.success@tabby.ai",
                    "name" => $data['user']->username ?? "Centerial Mall",
                    "dob" => $data['user']->birth_date ?? "1996-08-24",
                ],
                "shipping_address" =>  [
                    "city" => "Riyadh",
                    "address" => "Saudi Riyadh",
                    "zip" => "1234"
                ],
                "order" => [
                    "tax_amount" => "0.00",
                    "shipping_amount" => "0.00",
                    "discount_amount" => "0.00",
                    "updated_at" => $now,
                    "reference_id" => $data['reference_id'],
                    "items" => [
                        [
                            "title" => $data['lang'] == "ar" ? $data['item']->name_ar : $data['item']->name_en,
                            "description" => $data['lang'] == "ar" ? $data['item']->name_ar : $data['item']->name_en,
                            "quantity" => (int)$data['qty'],
                            "unit_price" => (string)$data['item']->price,
                            "discount_amount" => "0.00",
                            "reference_id" => (string)$data['item']->id,
                            "category" => "Car Services",
                        ],
                    ],
                ],

                "order_history" => null,
                "meta" => [
                    "order_id" => null,
                    "customer" => null
                ],
                "attachment" => null
            ],
            "lang" => "ar",
            "merchant_code" => "SA",
            "merchant_urls" => [
                "success" => "https://new.xn--shopperl-i1a.com/api/payment/success",
                "cancel" => "https://new.xn--shopperl-i1a.com/api/payment/cancel",
                "failure" => "https://new.xn--shopperl-i1a.com/api/payment/failure"
            ],
            "token" => null
        ];
    }


    public function retrieveTabbySession($id)
    {
        $http = Http::withToken($this->tabbySecretKey)->baseUrl($this->tabbyBaseUrl);
        $response = $http->get(url: "checkout/$id");
        return json_decode($response->getBody()->getContents(), true);
    }

    public function retrieveTabbyPayment($id)
    {
        $http = Http::withToken($this->tabbySecretKey)->baseUrl($this->tabbyBaseUrl);
        $response = $http->get("payments/$id");
        return json_decode($response->getBody()->getContents(), true);
    }
}
