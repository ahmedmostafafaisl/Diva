<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class DyService
{
    private $environment = 'test';

    private $baseUrl;

    private $tokenUrl;

    private $clientSecret;

    private $clientId;

    private $storeCustomer;

    private $getItems;

    private $salesOrder;

    private $customerPayment;

    private $getInvoiceDetails;

    public function __construct()
    {

        if ($this->environment == 'test') {
            $this->baseUrl = 'https://hamat-prod.operations.eu.dynamics.com';
            $this->tokenUrl = 'https://login.windows.net/015ce0d4-cd51-4914-9ada-bdaff52b5c3d/oauth2/token';
            $this->clientSecret = $clientSecret = env('DYNAMICS_CLIENT_SECRET');
            $this->clientId = $clientId = env('DYNAMICS_CLIENT_ID');
            $this->storeCustomer = 'https://hamat-prod.operations.eu.dynamics.com/api/services/TMK_CRMServGrp/TMK_CustomersService/CreateUpdateCustomer';
            $this->getItems = 'https://hamat-prod.operations.eu.dynamics.com/data/TMK_ItemDetailsEntity';
            $this->salesOrder = 'https://hamat-prod.operations.eu.dynamics.com/api/services/TMK_CRMServGrp/TMK_SalesOrderService/createSalesTransactions';
            $this->customerPayment = 'https://hamat-prod.operations.eu.dynamics.com/api/services/TMK_CRMServGrp/TMK_CustPaymService/CreateCustomerPayment';
            $this->getInvoiceDetails = 'https://hamat-prod.operations.eu.dynamics.com/api/services/TMK_CRMServGrp/TMK_SalesOrderService/GetInvoiceDetails';
        } else {
            $this->baseUrl = 'https://hamat-uat.sandbox.operations.eu.dynamics.com';
            $this->tokenUrl = 'https://login.windows.net/015ce0d4-cd51-4914-9ada-bdaff52b5c3d/oauth2/token';
            $this->clientSecret = $clientSecret = env('DYNAMICS_CLIENT_SECRET');
            $this->clientId = $clientId = env('DYNAMICS_CLIENT_ID');
            $this->storeCustomer = 'https://hamat-uat.sandbox.operations.eu.dynamics.com/api/services/TMK_CRMServGrp/TMK_CustomersService/CreateUpdateCustomer';
            $this->getItems = 'https://hamat-uat.sandbox.operations.eu.dynamics.com/data/TMK_ItemDetailsEntity';
            $this->salesOrder = 'https://hamat-uat.sandbox.operations.eu.dynamics.com/api/services/TMK_CRMServGrp/TMK_SalesOrderService/createSalesTransactions';
            $this->customerPayment = 'https://hamat-uat.sandbox.operations.eu.dynamics.com/api/services/TMK_CRMServGrp/TMK_CustPaymService/CreateCustomerPayment';
            $this->getInvoiceDetails = 'https://hamat-uat.sandbox.operations.eu.dynamics.com/api/services/TMK_CRMServGrp/TMK_SalesOrderService/GetInvoiceDetails';
        }
    }

    public function getToken()
    {
        $body = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'resource' => $this->baseUrl,
        ];

        try {
            $response = Http::asForm()->post($this->tokenUrl, $body);
            if ($response->successful()) {
                $res = $response->json();

                return $res['access_token'];
            } else {
                throw new \Exception('Token request failed: '.$response->body());
            }
        } catch (\Exception $e) {
            error_log($e->getMessage());

            return null;
        }
    }

    public function storeCustomer($customer_id, $customer_name, $customer_phone)
    {
        $token = $this->getToken();
        $body = [
            '_Contract' => [
                'AccountId' => $customer_id,
                'CustomerName' => $customer_name,
                'CustomerPhone' => $customer_phone,
                'CustomerGroup' => '001',
                'FinancialDimensions' => [
                    [
                        'DimensionName' => 'Branch',
                        'DimensionValue' => 'C_01_Riyadh',
                    ],
                ],
            ],
        ];
        // $response = Http::withToken($token)->post($this->storeCustomer, $body);
        $response = Http::withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->timeout(60)
            ->retry(3, 100)
            ->post($this->storeCustomer, $body);
        if ($response->successful()) {
            return $response->json();
        } else {
            return $response->body();
        }
    }

    public function getServicesAndPackages()
    {
        $token = $this->getToken();

        $response = Http::withToken($token)->get($this->getItems);

        return $response->json();
    }

    public function createSalesOrder($crmSalesId, $customerId, $itemId, $qty, $price, $discAmount, $paymentMethod = 'ECommerce')
    {
        $token = $this->getToken();

        $body = [
            '_Contract' => [
                'SalesOrderList' => [
                    [
                        'PostOrder' => true,
                        'CRM_SalesId' => $crmSalesId,
                        'PlatformNumber' => '365821',
                        'CustomerId' => $customerId,
                        'PaymentMethod' => $paymentMethod,
                        'SalesTakerNumber' => 'T-20126',
                        'FinancialDimensions' => [
                            [
                                'DimensionName' => 'Department',
                                'DimensionValue' => 'SAL',
                            ],
                        ],
                        'SalesLines' => [
                            [
                                'ItemId' => $itemId,
                                'Warehouse' => 'Main-Riyad',
                                'SerialNumber' => '',
                                'Qty' => $qty,
                                'Price' => $price,
                                'DiscAmount' => $discAmount,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->timeout(60)
            ->retry(3, 100)
            ->post($this->salesOrder, $body);

        if ($response->successful()) {
            return $response->json();
        } else {
            return $response->body();
        }
    }

    public function createPackageSalesOrder($crmSalesId, $customerId, $itemId, $qty, $price, $discAmount)
    {
        $token = $this->getToken();

        $body = [
            '_Contract' => [
                'SalesOrderList' => [
                    [
                        'PostOrder' => true,
                        'CRM_SalesId' => $crmSalesId,
                        'PlatformNumber' => '365821',
                        'CustomerId' => $customerId,
                        'PaymentMethod' => 'ECommerce',
                        'SalesTakerNumber' => 'T-20126',
                        'FinancialDimensions' => [
                            [
                                'DimensionName' => 'Department',
                                'DimensionValue' => 'SAL',
                            ],
                        ],
                        'SalesLines' => [
                            [
                                'ItemId' => $itemId,
                                'Warehouse' => 'Main-Riyad',
                                'SerialNumber' => '',
                                'Qty' => $qty,
                                'Price' => $price,
                                'DiscAmount' => $discAmount,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->timeout(60)
            ->retry(3, 100)
            ->post($this->salesOrder, $body);

        if ($response->successful()) {
            return $response->json();
        } else {
            return $response->body();
        }
    }

    public function CreateCustomerPayment($dy_invoice_id, $paymentMethod = 'ECommerce')
    {
        $token = $this->getToken();
        $details = $this->getInvoiceDetails($dy_invoice_id);
        $body = [
            '_Contract' => [
                'PaymentList' => [
                    [
                        'D365InvoiceId' => $details['D365_InvoiceID'],
                        'PaymentMethod' => $paymentMethod,
                        'Amount' => $details['InvoiceAmount'],
                    ],
                ],
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->timeout(60)
            ->retry(3, 100)
            ->post($this->customerPayment, $body);

        if ($response->successful()) {
            return $response->json();
        } else {
            return $response->body();
        }
    }

    public function getInvoiceDetails($dy_invoice_id)
    {
        $token = $this->getToken();

        $body = [
            '_id' => $dy_invoice_id,
        ];

        $response = Http::withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])
            ->timeout(60)
            ->retry(3, 100)
            ->post($this->getInvoiceDetails, $body);

        if ($response->successful()) {
            return $response->json();
        } else {
            return $response->body();
        }
    }
}
