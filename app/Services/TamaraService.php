<?php

namespace App\Services;

use GuzzleHttp\Client;

class TamaraService
{
    protected $apiUrl;
    protected $apiKey;
    protected $client;

    public function __construct()
    {
        // $this->apiUrl = env('TAMARA_API_URL');
        // $this->apiKey = env('TAMARA_API_KEY');
        $this->apiUrl = "https://api-sandbox.tamara.co/ ";
        $this->apiKey = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhY2NvdW50SWQiOiI0YmJhMDViNC04OWMwLTQ5OTgtOTI2OS1lNzE3ZGUzNjRlYzQiLCJ0eXBlIjoibWVyY2hhbnQiLCJzYWx0IjoiOTg2Yzg3NjU4YzY5ZjU0ZjE1MjQ1MjQ5NTY1OWJlMWYiLCJyb2xlcyI6WyJST0xFX01FUkNIQU5UIl0sImlhdCI6MTY5MDQ2MTk5NywiaXNzIjoiVGFtYXJhIn0.Pb4zdaesWEKCVliGjtd21OyujdkurLkzIyhEkDY7w1zMUcVC6WSM4O7WZNhX4WA6E18cWA9IQlV-0lQF2NOz35O4X1DrtSWDtIfUfW8gKN71SviHIAIdICbJbiDXixV_VwOFYYuU2whOkITktjjow9FGPE11w7m33FHYn5HU1rCYTcizY3l2XRX9JK1qva8xrQ0hJnB-RvzVZMpH4mZWdRcX_LaPwKO2rP9I9engEZOJKGIDdPANaIF-JxV_tfbFmzcmXXU92bkHT2rbhNHjjO-EKeUz_nxW5ks1a86am10GSCUr6jaMuCHLiy_nBhNf--ZF9CGEOa1sgZAyrQ7R0w";
        $this->client = new Client([
            'base_uri' => $this->apiUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
        ]);
    }


    public function pre_checkout($phone, $amount)
    {
        try {
            // check payment types
            $response = $this->client->get("/checkout/payment-types?country=SA&phone={$phone}&currency=SAR&order_value={$amount}");
            $types = json_decode($response->getBody(), true);
            if ($types == []) {
                return response()->json([
                    'status' => 'rejected',
                    'data' => $types,
                ], 400);
            }
            // check payment options
            $response = $this->client->post('/checkout/payment-options-pre-check', [
                'json' => [
                    "country" => "SA",
                    "order_value" => [
                        "amount" => 1,
                        "currency" => "SAR"
                    ],
                    "phone_number" => $phone,
                    "is_vip" => "false"
                ]
            ]);

            $options = json_decode($response->getBody(), true);

            if (isset($options["error"])) {
                return response()->json([
                    'status' => 'rejected',
                    'data' => $options,
                ], 400);
            } elseif ($options["has_available_payment_options"] == false) {
                return response()->json([
                    'status' => 'rejected',
                    'data' => $options,
                ], 400);
            }
            // check id verification
            // $response = $this->client->get("/merchants/customer/id-verification-status?phone_number={$phone}&country_code=SA");
            // $verification = json_decode($response->getBody(), true);

            // if ($verification['is_id_verified'] == false) {
            //     return response()->json([
            //         'status' => 'rejected',
            //         'data' => $verification,
            //     ], 400);
            // }
            return  "true";
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }
    public function createOrder($orderData)
    {
        try {
            $response = $this->client->post('/checkout', [
                'json' => $orderData,
            ]);
            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }


    public function authorizeOrder($order_id)
    { {
            try {
                $response = $this->client->post('orders/' . $order_id . '/authorise');

                return json_decode($response->getBody(), true);
            } catch (\Exception $e) {
                return [
                    'error' => true,
                    'message' => $e->getMessage(),
                ];
            }
        }
    }

    public function captureOrder($orderData)
    { {
            try {
                $response = $this->client->post('/payments/capture', [
                    'json' => $orderData,
                ]);

                return json_decode($response->getBody(), true);
            } catch (\Exception $e) {
                return [
                    'error' => true,
                    'message' => $e->getMessage(),
                ];
            }
        }
    }
    public function getOrderStatus($orderId)
    {
        try {
            $response = $this->client->get("/orders/{$orderId}");
            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }
}
