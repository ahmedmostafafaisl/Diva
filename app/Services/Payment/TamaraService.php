<?php

namespace App\Services\Payment;

use Carbon\Carbon;
use GuzzleHttp\Client;
use App\Models\Appointment;
use Illuminate\Support\Str;
use App\Models\DyPaymentLink;
use App\Models\TamaraPayment;
use Illuminate\Support\Facades\Log;
use App\Models\DirectAppointmentPayment;

class TamaraService
{
    private $environment = 'test'; // 'production' or 'test'
    protected $apiUrl;
    protected $apiKey;
    protected $client;

    public function __construct()
    {
        if ($this->environment == 'production') {
            //live keys
            $this->apiUrl = "https://api.tamara.co/";
            $this->apiKey = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhY2NvdW50SWQiOiI0YTE0MTRmNi00YzIxLTRjYTEtYWQ5Ny1hNjI0YzJlYTc4MGYiLCJ0eXBlIjoibWVyY2hhbnQiLCJzYWx0IjoiY2Q4ODJkMGJlYWNlZGQ5NjJhZTBkODA0YjJmNDY1ZDkiLCJpYXQiOjE2Nzc2NDI5OTIsImlzcyI6IlRhbWFyYSBQUCJ9.nDy-pqpIx8Cc9iUaK9tzu89-JRdQJDRcWF7nXAaHfwRj8VNK2zHh07Rba0VGdVCczYBQq4PzAju05X-yDef-uUGvFgI9pLNpauItON4ci51qtIllRP5Pntv0lMXDZXngkvtT8wXRWOxiIwRav-7k4PQnKSQyCImCkUhBWQ5i_f8UnLa2BwXJvsCPRBJjd2d2fP4PHcUh3i7KOoEJoTlLfUG7MjW4GGdPY4lTZB9RHLXYY4f1G02aGMWuhzItksLlch5yMg2tdvQoFPTw7BtZZ1f5s81ESQydE-Yw71Q4sE15mU22KBOdczfP1rQ-9Gf70TFAmbzma7JU7SDr3hxMxQ";
        } else {
            // sandbox keys
            $this->apiUrl = "https://api-sandbox.tamara.co/";
            $this->apiKey = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhY2NvdW50SWQiOiJkMzJlMWNiNC00OGEwLTQ5MjYtYTViNC01ZjVmMzBmMmQ5YjkiLCJ0eXBlIjoibWVyY2hhbnQiLCJzYWx0IjoiM2JkMjIxMjcyMTBkMmZjN2MzNmY1ZDY1MWRiNWVjNmEiLCJyb2xlcyI6WyJST0xFX01FUkNIQU5UIl0sImlhdCI6MTc0NTIyNzQ2OSwiaXNzIjoiVGFtYXJhIn0.ciXBnhy7YqBEMO-9D2niAmFqR0L1aQipvY3xX22NilBu_n2sZnx47yxORpvxMwtIeESNREiyjSbcod3icg32gb67Y8V2U2qxo0Yqv4MZV3b8UoG9RBopP1bD7GVttAJ1uoFyiH_gkGz74JIbhswIq0LoxB9DA20JYZ5K4vbFpsrZXkq8kLvD0bxGG0htHW6sqtdU1VNQ0kbjyVt6EM6yyOE8XedBicdqHTKrZXwOpabKNZvtr85O-Frcw4yeS-rVQfeDM7BdNaeRcMxqsAHb6KA0N631_YiuOgGwyxSUfHVkGestxr7NykMhMag7mY8f-3em3BQe3GdTrHrlFnpu2Q";
        }

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

            return  "true";
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }



    public function checkout($data, $payment)
    {
        // return $data;

        try {
            $orderData = [
                "total_amount" => [
                    "amount" => round($data['amount'], 2),
                    "currency" => "SAR"
                ],
                "shipping_amount" => [
                    "amount" => 0,
                    "currency" => "SAR"
                ],
                "tax_amount" => [
                    "amount" => 0,
                    "currency" => "SAR"
                ],
                "order_reference_id" => $data['reference_id'],
                "order_number" => $data['reference_id'],
                "discount" => [
                    "amount" => [
                        "amount" => round($payment['discount'] ?? 0, 2),
                        "currency" => "SAR"
                    ],
                    "name" => "Appointment Discount"
                ],
                "items" => [
                    [
                        "name" =>  "Item",
                        "type" => "Digital",
                        "reference_id" => (string) $data['reference_id'],
                        "sku" => "SKU-" . $data['reference_id'],
                        "quantity" =>  1,
                        "discount_amount" => [
                            "amount" => 0.00,
                            "currency" => "SAR"
                        ],
                        "tax_amount" => [
                            "amount" => 0,
                            "currency" => "SAR"
                        ],
                        "unit_price" => [
                            "amount" => round($data['amount'] ?? 0, 2),
                            "currency" => "SAR"
                        ],
                        "total_amount" => [
                            "amount" => round(($data['amount'] ?? 0) * 1, 2),
                            "currency" => "SAR"
                        ]
                    ]
                ],


                "consumer" => [
                    "email" =>  "customer@example.com",
                    "first_name" =>  "Customer",
                    "last_name" =>  "User",
                    "phone_number" => $phone ?? preg_replace('/\D/', '', "500000000")
                ],
                "country_code" => "SA",
                "description" => "Order #" . "500000000",
                "merchant_url" => [
                    "success" => route('new.tamara.success', [
                        'reference_id'   => $data['reference_id'],

                    ]),
                    "cancel" => route('new.tamara.cancel', [
                        'reference_id'   => $data['reference_id'],
                    ]),
                    "failure" => route('new.tamara.failure', [
                        'reference_id'   => $data['reference_id'],

                    ]),
                    "notification" => route('new.tamara.notification', [
                        'reference_id'   => $data['reference_id'],

                    ]),
                ],
                "payment_type" => "PAY_BY_INSTALMENTS",
                "instalments" => 3,
                "billing_address" => [
                    "city" => $data['city'] ?? "Riyadh",
                    "country_code" => "SA",
                    "first_name" => "Customer",
                    "last_name" => "User",
                    "line1" =>    "Street Line 1",
                    "line2" => "Building Info",
                    "phone_number" => preg_replace('/\D/', '', "500000000"),
                    "region" => "Region"
                ],
                "shipping_address" => [
                    "city" => "Riyadh",
                    "country_code" => "SA",
                    "first_name" => "Customer",
                    "last_name" => "User",
                    "line1" => "Street Line 1",
                    "line2" => "Building Info",
                    "phone_number" => preg_replace('/\D/', '', "500000000"),
                    "region" => "Region"
                ],
                "platform" => "Naqi",
                "is_mobile" => false,
                "locale" => "en_US"
            ];
            // Send order to Tamara API
            $response = $this->client->post('/checkout', [
                'json' => $orderData,
            ]);

            $responseData = json_decode($response->getBody(), true);
            // dd($responseData);
            $payment->payment_id = $responseData['order_id'];
            $payment->checkout_url = $responseData['checkout_url'];
            $payment->save();
            if (!isset($responseData['checkout_url'])) {
                return [
                    'error' => true,
                    'message' => 'Missing checkout_url in Tamara response',
                    'response' => $responseData
                ];
            }
            // dd($payment);
            return [
                'checkout_url' => $responseData['checkout_url'],
                'reference_id' =>  $payment->reference_id
            ];
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }



public function captureOrderNew(string $referenceId, string $orderId): array
{
    try {
        $payment = TamaraPayment::with('items')->where('reference_id', $referenceId)->firstOrFail();

        // items
        $items = $payment->items->map(function ($item) {
            $qty = max(1, (int) ($item->quantity ?? 1));
            $unit = (float) ($item->price ?? 0);

            return [
                "name" => $item->name ?? "Item",
                "type" => "Physical",
                "reference_id" => (string) ($item->item_id ?? $item->id),
                "sku" => "SKU-" . ((string) ($item->item_id ?? $item->id)),
                "quantity" => $qty,
                "discount_amount" => [
                    "amount" => round((float)($item->discount_amount ?? 0), 2),
                    "currency" => "SAR"
                ],
                "tax_amount" => [
                    "amount" => 0,
                    "currency" => "SAR"
                ],
                "unit_price" => [
                    "amount" => round($unit, 2),
                    "currency" => "SAR"
                ],
                "total_amount" => [
                    "amount" => round($unit * $qty, 2),
                    "currency" => "SAR"
                ]
            ];
        })->toArray();

        $payload = [
            "order_id" => $orderId,
            "total_amount" => [
                "amount" => round((float) $payment->amount, 2), // ✅ amount مش price
                "currency" => "SAR"
            ],
            "items" => $items,
            "discount_amount" => [
                "amount" => round((float) ($payment->discount ?? 0), 2),
                "currency" => "SAR"
            ],
            "shipping_amount" => [
                "amount" => 0,
                "currency" => "SAR"
            ],
            "tax_amount" => [
                "amount" => 0,
                "currency" => "SAR"
            ],
            "shipping_info" => [
                "shipped_at" => Carbon::now()->toIso8601String(),
                "shipping_company" => "  Delivery",
                "tracking_number" => "TRK" . $payment->reference_id,
                "tracking_url" => "https://tracking.example.com?id=" . $payment->reference_id
            ]
        ];

        $response = $this->client->post('/payments/capture', [
            'json' => $payload,
            'http_errors' => false
        ]);

        $result = json_decode($response->getBody(), true);

        return [
            'success' => $response->getStatusCode() >= 200 && $response->getStatusCode() < 300,
            'message' => 'Capture called',
            'data' => $result
        ];
    } catch (\Exception $e) {
        Log::error("❌ Error capturing payment: " . $e->getMessage());
        return [
            'error' => true,
            'message' => $e->getMessage(),
        ];
    }
}

   public function authorizeOrder(string $orderId): array
{
    try {
        $response = $this->client->post("orders/{$orderId}/authorise");
        return json_decode($response->getBody(), true);
    } catch (\Exception $e) {
        return [
            'error' => true,
            'message' => $e->getMessage(),
        ];
    }
}

public function getOrderStatus(string $orderId): array
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
