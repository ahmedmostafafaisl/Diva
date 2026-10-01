<?php

namespace App\Services\Qoyod;

use Illuminate\Support\Facades\Http;

class QoyodService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = '7a02305d6713d220056204e8d'; // Store API key in config/services.php
        $this->baseUrl = 'https://www.qoyod.com/api/2.0/';
    }

    private function sendRequest($method, $endpoint, $data = [])
    {
        $response = Http::withHeaders([
            'API-KEY' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->$method($this->baseUrl . $endpoint, $data);

        return $response->json();
    }


    public function createAccount($data)
    {

        return $this->sendRequest('post', 'accounts', $data);
    }


    public function createInventory($data)
    {
        return $this->sendRequest('post', 'inventories', $data);
    }



    public function createInventoryAdjustment($data)
    {
        $url = "https://www.qoyod.com/api/2.0/inventory_adjustments";
        $apiKey = $this->apiKey;

        $data = [
            "inventory_adjustment" => [
                "inventory_id" => "1",
                "revenue_account_id" => "17",
                "expense_account_id" => "12",
                "date" => "2019-10-30",
                "description" => "We only have 5 pieces",
                "line_items" => [
                    [
                        "product_id" => $data['product_id'],
                        "actual_quantity" => "5000000",
                        "rate" => "10"
                    ]
                ]
            ]
        ];

        $headers = [
            "Content-Type: application/json",
            "API-KEY: $apiKey"
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return [
            "status_code" => $httpCode,
            "response" => json_decode($response, true)
        ];
    }




    public function createInvoice($data)
    {
        // dd($data);
        $invoice = [
            'invoice' => [
                'contact_id' => $data['user_id'],
                'reference' => $data['q_reference_id'],
                'description' => $data['description'] ?? '',
                'issue_date' => now()->format('Y-m-d'),
                'due_date' => now()->addMonth()->format('Y-m-d'),
                'status' => "Approved",
                'inventory_id' => 1,
                'line_items' => [
                    [
                        'product_id' => $data['service_id'],
                        'description' => "",
                        'quantity' => 1.0,
                        'unit_price' => $data['price'],
                        'discount' => $data['discount'] ?? 0,
                        'discount_type' => "amount",
                    ],
                ],
            ],
        ];

        $invoicedata = $this->sendRequest('post', 'invoices', $invoice);

        if (!isset($invoicedata['invoice'])) {
            return $invoicedata;
        }
        return $invoicedata['invoice'];
    }



    public function createInvoicePayment($data)
    {
        $payment = [
            'invoice_payment' => [
                'reference' => $data['reference'],
                'invoice_id' => $data['invoice_id'],
                'account_id' => $data['account_id'] ?? 10,
                'date'  => now()->format('Y-m-d'),
                'amount' => $data['amount'],
            ]
        ];
        $payment = $this->sendRequest('post', 'invoice_payments', $payment);
        if (!$payment || isset($payment['errors'])) {  // Check if the invoice creation failed
            return response()->json([
                'success' => false,
                'message' => $payment['errors'] ?? 'Invoice creation failed.'
            ], 400);
        }
        return $payment;
    }


    public function getInvoice($invoiceId)
    {
        $invoice =  $this->sendRequest('get', "invoices/{$invoiceId}");
        $pdfResponse = $this->getInvoicePdf($invoiceId);
        $pdfLink = $pdfResponse['pdf_file']  ?? null; // Adjust based on your API response structure
        $invoice['pdf_url'] = $pdfLink;
        return $invoice;
    }


    public function getInvoicePdf($invoiceId)
    {
        return $this->sendRequest('get', "invoices/{$invoiceId}/pdf");
    }

    /////////////////////////////////// customers //////////////////////////////////////////////////
    public function getCustomers()
    {
        return $this->sendRequest('get', "customers");
    }
    public function createCustomerIfNotExists($data)
    {
        $phone = $data['phone'];
        $existingCustomer = $this->findCustomerByPhone($phone);
        if ($existingCustomer) {
            // Return the existing customer's ID
            return $existingCustomer;
        }
        // Create a new customer if it doesn't exist
        $newCustomer = $this->createCustomer($data);
        // Return the newly created customer's ID
        return $newCustomer['contact'];
    }

    public function findCustomerByPhone($phone)
    {
        $customers = $this->getCustomers();
        foreach ($customers['customers'] as $customer) {
            if (isset($customer['phone_number']) && $customer['phone_number'] === $phone) {
                return $customer;
            }
        }
        return null;
    }

    public function createCustomer($data)
    {
        $user = [
            'contact' => [
                'name' => $data['username'] ?? $data['name'],
                // 'organization' => $user->username,
                'email' => $data['email'],
                'phone_number' => $data['phone'],
            ]
        ];
        return $this->sendRequest('post', 'customers', $user);
    }

    /////////////////////////////////// service //////////////////////////////////////////////////

    public function getAllServices()
    {
        return $this->sendRequest('get', 'products');
    }

    public function createServiceIfNotExists($data)
    {
        $number = $data['dy_item_number'];
        $existingService = $this->findServiceByItemNumber($number);
        if ($existingService) {
            $data['product_id'] = $existingService['id'];
            ($this->createInventoryAdjustment($data));
            return $existingService;
        }
        // Create a new Service if it doesn't exist
        $newService = $this->createProduct($data);
        // Return the newly created Service's ID
        $service = $newService['product'];

        $data['product_id'] = $service['id'];
        ($this->createInventoryAdjustment($data));
        return $service;
    }

    public function findServiceByItemNumber($number)
    {
        $services = $this->getAllServices();
        foreach ($services['products'] as $service) {
            if (isset($service['sku']) && $service['sku'] === $number) {
                return $service;
            }
        }
        return null;
    }

    public function createProduct($data)
    {
        // dd($data);
        $product = [
            'product' => [
                'sku' => $data['dy_item_number'],
                'barcode' => $data['barcode'] ?? '548576',
                'name_ar' => $data['name_ar'],
                'name_en' => $data['name_en'],
                'description' => $data['description'] ?? $data['name_ar'],
                'product_unit_type_id' => $data['product_unit_type_id'] ?? '7',
                'type' => $data['type'] ?? 'Service',
                'category_id' => $data['category_id'] ?? '1',
                'track_quantity' => $data['track_quantity'] ?? '1',
                'purchase_item' => $data['purchase_item'] ?? '1',
                'buying_price' => $data['buying_price'] ?? '10.0',
                'expense_account_id' => $data['expense_account_id'] ?? '12',
                'sale_item' => $data['sale_item'] ?? '1',
                'selling_price' => $data['price'],
                'sales_account_id' => $data['sales_account_id'] ?? '17',
                'tax_id' => $data['tax_id'] ?? '1',
                'is_buying_price_inclusive' => $data['is_buying_price_inclusive'] ?? '1',
                'is_selling_price_inclusive' => $data['is_selling_price_inclusive'] ?? '1',
                'is_bought' => $data['is_bought'] ?? '1',
            ]
        ];
        $service = $this->sendRequest('post', 'products', $product);

        return $service;
    }
}
