<?php

namespace App\Services;

use App\Http\Resources\Refactor\Product\SinglePostResource;
use App\Models\Address2;
use App\Models\FollowRequest;
use App\Models\Product;
use App\Models\Shipping;
use App\Models\User;
use App\Models\UserFriend;
use App\Models\Zone;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WooOrderService
{
    private $baseUrl;

    private $beautyBaseUrl;

    private $consumer_key;

    private $consumer_secret;

    private $order;

    private $customers;

    private $zones;

    private $posts;

    private $singlePost;

    private $comments;

    public function __construct()
    {
        $this->baseUrl = 'https://diva.sa/wp-json/wc/v3/';
        $this->beautyBaseUrl = 'https://diva.sa/wp-json/procontent/v1/';
        $this->consumer_key = 'ck_3ac0df7ec455715d7173bef920c7a779ac907c51';
        $this->consumer_secret = 'cs_03ea5e697c1d25a77c5f578cf8a88593ba3c1d4e';
        $this->order = 'orders';
        $this->customers = 'customers';
        $this->zones = 'shipping/zones';
        $this->posts = 'get-posts/';
        $this->singlePost = 'get-post/';
        $this->comments = 'comments';
    }

    private function logError(string $functionName, string $message): void
    {
        $logPath = storage_path("logs/dyservice/{$functionName}.log");

        if (! file_exists(dirname($logPath))) {
            mkdir(dirname($logPath), 0777, true);
        }

        $date = now()->format('Y-m-d H:i:s');
        $line = "[{$date}] {$message}\n";

        file_put_contents($logPath, $line, FILE_APPEND);
    }

    private function sendRequest($method, $endpoint, $data = [])
    {
        $functionName = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];

        try {
            $client = Http::retry(3, 2000)->withOptions([
                'verify' => false,
                'timeout' => 500,
                'connect_timeout' => 300,
            ])->withBasicAuth($this->consumer_key, $this->consumer_secret)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ]);
            // ✅ Build final URL once
            $url = rtrim($this->baseUrl, '/').'/'.ltrim($endpoint, '/');
            // 🔍 Debug the final URL (instead of returning it)
            \Log::info('sendRequest URL', ['url' => $url, 'method' => $method, 'data' => $data]);
            if (strtolower($method) === 'get') {
                $response = $client->get($url, $data);
            } elseif (strtolower($method) === 'post') {
                $response = $client->post($url, $data);
            } elseif (strtolower($method) === 'put') {
                $response = $client->put($url, $data);
            } else {
                throw new \InvalidArgumentException("Unsupported method [$method]");
            }

            // 🔍 Debug response body
            \Log::info('sendRequest Response', ['body' => $response->body()]);

            return $response->json();
        } catch (\Throwable $e) {
            $this->logError($functionName, $e->getMessage());

            return null;
        }
    }

    private function beautySendRequest($method, $endpoint, $data = [])
    {
        $functionName = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];

        try {
            $client = Http::retry(3, 2000)->withOptions([
                'verify' => false,
                'timeout' => 500,
                'connect_timeout' => 300,
            ])
                // ->withBasicAuth($this->consumer_key, $this->consumer_secret)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ]);
            // ✅ Build final URL once
            $url = rtrim($this->beautyBaseUrl, '/').'/'.ltrim($endpoint, '/');

            if (strtolower($method) === 'get') {
                $response = $client->get($url, $data);
            } elseif (strtolower($method) === 'post') {
                $response = $client->post($url, $data);
            } elseif (strtolower($method) === 'put') {
                $response = $client->put($url, $data);
            } else {
                throw new \InvalidArgumentException("Unsupported method [$method]");
            }

            return $response->json();
        } catch (\Throwable $e) {
            $this->logError($functionName, $e->getMessage());

            return null;
        }
    }

    public function getOrderByPhone($phone)
    {

        $orders = $this->sendRequest('get', $this->order, [
            'search' => $phone,
        ]);

        if (empty($orders)) {
            return [];
        }

        $result = [];

        foreach ($orders as $order) {
            // Check if order is completed and date_modified is within last 3 days
            $refundStatus = false;
            if ($order['status'] == 'completed') {
                $modifiedDate = new \DateTime($order['date_modified']);
                $threeDaysAgo = new \DateTime('-3 days');
                if ($modifiedDate >= $threeDaysAgo) {
                    $refundStatus = true;
                }
            }

            $result[] = [
                'id' => $order['id'],
                'user' => [
                    'id' => $order['customer_id'],
                    'username' => $order['billing']['first_name'].' '.$order['billing']['last_name'],
                    'phone' => '+'.$order['billing']['phone'],
                    'email' => $order['billing']['email'],
                    'first_name' => $order['billing']['first_name'],
                    'last_name' => $order['billing']['last_name'],
                    'gender' => 'female',
                    'birth_date' => '2015-12-06', // placeholder
                ],
                'address' => [
                    'id' => 2,
                    'address_1' => $order['billing']['address_1'],
                    'address_2' => $order['billing']['address_2'],
                    'city' => $order['billing']['city'],
                    'state' => $order['billing']['state'],
                    'postcode' => $order['billing']['postcode'],
                    'country' => $order['billing']['country'],
                ],
                'currency' => $order['currency'],
                'tax' => $order['total_tax'],
                'total_amount' => count($order['line_items']),
                'total_price' => $order['total'],
                'subtotal' => $order['total'] + $order['discount_total'] ?? null,
                'discount_total' => $order['discount_total'],
                'total_tax' => $order['total_tax'],
                'billing_email' => $order['billing']['email'],
                'payment_method' => $order['payment_method'],
                'shipment_note' => $order['customer_note'],
                'status' => $order['status'],
                'payment_status' => 'paid',
                'Shipping_company' => 'JT&T',
                'Tracking_number' => collect($order['meta_data'])
                    ->firstWhere('key', 'jtawb')['value'] ?? null,
                'refund_status' => $order['status'] == 'completed' ? $refundStatus : null,
                'products' => array_map(function ($item) {
                    return [
                        'id' => $item['product_id'],
                        'item_id' => $item['id'],
                        'name' => strip_tags($item['name']),
                        'price' => $item['total'] + ($item['total'] * 0.15),
                        'quantity' => $item['quantity'],
                        'description' => $item['short_description'],
                        'image' => str_replace('3.73.173.183', 'centerialmall.com', $item['image']['src']) ?? null,
                    ];
                }, $order['line_items']),
                'created_at' => $order['date_created'],
                'updated_at' => $order['date_modified'],
            ];
        }

        return $result;
    }

    public function SendOrder($data)
    {
        $custom = $this->getUserByPhone(
            $data['user']['email'],
            $data['user_phone'],
            $data['user'],
            $data['address']
        );

        $customId = $custom['id'];
        $taxRate = 0.15;

        $lineItems = [];
        $netSubtotal = 0.0;
        $taxTotal = 0.0;

        foreach ($data['products'] as $product) {
            $productId = is_array($product)
                ? ($product['product_id'] ?? $product['id'] ?? null)
                : ($product->id ?? null);

            $variationId = is_array($product)
                ? ($product['variation_id'] ?? null)
                : ($product->pivot->variation_id ?? null);

            $quantity = (int) (
                is_array($product)
                    ? ($product['quantity'] ?? 1)
                    : ($product->pivot->quantity ?? 1)
            );

            $price = (float) (
                is_array($product)
                    ? ($product['price'] ?? 0)
                    : ($product->pivot->price ?? 0)
            );

            $rightStandard = is_array($product) ? ($product['right_standard'] ?? null) : ($product->pivot->right_standard ?? null);
            $rightQuantity = is_array($product) ? ($product['right_quantity'] ?? null) : ($product->pivot->right_quantity ?? null);
            $rightPrice = is_array($product) ? ($product['right_price'] ?? null) : ($product->pivot->right_price ?? null);

            $leftStandard = is_array($product) ? ($product['left_standard'] ?? null) : ($product->pivot->left_standard ?? null);
            $leftQuantity = is_array($product) ? ($product['left_quantity'] ?? null) : ($product->pivot->left_quantity ?? null);
            $leftPrice = is_array($product) ? ($product['left_price'] ?? null) : ($product->pivot->left_price ?? null);

            $isTaxable = is_array($product)
                ? (bool) ($product['taxable'] ?? false)
                : (bool) ($product->tax ?? false);

            // في سيستمك price هنا هو line total الفعلي للسطر
            $lineGross = $price;

            $lineItem = [
                'product_id' => $productId,
                'quantity' => $quantity,
            ];

            if (! empty($variationId)) {
                $lineItem['variation_id'] = (int) $variationId;
            }

            $metaData = [];

            if (! is_null($rightStandard)) {
                $metaData[] = ['key' => 'right_standard', 'value' => $rightStandard];
            }
            if (! is_null($rightQuantity)) {
                $metaData[] = ['key' => 'right_quantity', 'value' => $rightQuantity];
            }
            if (! is_null($rightPrice)) {
                $metaData[] = ['key' => 'right_price', 'value' => $rightPrice];
            }
            if (! is_null($leftStandard)) {
                $metaData[] = ['key' => 'left_standard', 'value' => $leftStandard];
            }
            if (! is_null($leftQuantity)) {
                $metaData[] = ['key' => 'left_quantity', 'value' => $leftQuantity];
            }
            if (! is_null($leftPrice)) {
                $metaData[] = ['key' => 'left_price', 'value' => $leftPrice];
            }

            if (! empty($metaData)) {
                $lineItem['meta_data'] = $metaData;
            }

            if ($isTaxable) {
                // gross includes tax
                $lineNet = round($lineGross / (1 + $taxRate), 2);
                $lineTax = round($lineGross - $lineNet, 2);

                $netSubtotal += $lineNet;
                $taxTotal += $lineTax;

                $lineItem['subtotal'] = number_format($lineNet, 2, '.', '');
                $lineItem['total'] = number_format($lineNet, 2, '.', '');
                $lineItem['subtotal_tax'] = number_format($lineTax, 2, '.', '');
                $lineItem['total_tax'] = number_format($lineTax, 2, '.', '');
            } else {
                $netSubtotal += $lineGross;

                $lineItem['subtotal'] = number_format($lineGross, 2, '.', '');
                $lineItem['total'] = number_format($lineGross, 2, '.', '');
                $lineItem['subtotal_tax'] = '0.00';
                $lineItem['total_tax'] = '0.00';
            }

            $lineItems[] = $lineItem;
        }

        $payload = [
            'payment_method' => $data['payment_method'] ?? 'bacs',
            'payment_method_title' => $data['payment_method'] ?? 'Direct Bank Transfer',
            'transaction_id' => $data['payment_id'] ?? '',
            'set_paid' => true,
            'currency' => $data['currency'] ?? 'SAR',

            'subtotal' => number_format($netSubtotal, 2, '.', ''),
            'total' => number_format($netSubtotal, 2, '.', ''),
            'discount_total' => 0,

            'customer_id' => $customId,
            'customer_note' => $data['shipment_note'] ?? 'Order from app',
            'shipping_tax' => '0.00',

            'billing' => [
                'first_name' => $data['user']['first_name'] ?? ' ',
                'last_name' => $data['user']['last_name'] ?? ' ',
                'address_1' => $data['address']['street'] ?? ' ',
                'address_2' => $data['address']['location_note'] ?? '',
                'city' => $data['address']['city'] ?? '',
                'state' => $data['address']['state'] ?? '',
                'country' => 'SA',
                'email' => $data['user']['email'] ?? '',
                'phone' => $data['user_phone'] ?? '',
            ],

            'shipping' => [
                'first_name' => $data['user']['first_name'] ?? ' ',
                'last_name' => $data['user']['last_name'] ?? ' ',
                'address_1' => $data['address']['street'] ?? ' ',
                'address_2' => $data['address']['location_note'] ?? '',
                'city' => $data['address']['city'] ?? '',
                'state' => $data['address']['state'] ?? '',
                'country' => 'SA',
            ],

            'line_items' => $lineItems,

            'tax_lines' => $taxTotal > 0 ? [
                [
                    'rate_code' => 'SA-ضريبة القيمة المضافة-1',
                    'rate_id' => 1,
                    'label' => 'ضريبة القيمة المضافة',
                    'rate_percent' => 15,
                    'tax_total' => number_format($taxTotal, 2, '.', ''),
                    'shipping_tax_total' => '0.00',
                ],
            ] : [],
        ];

        Log::info('Prepared order payload for WooCommerce', ['payload' => $payload]);

        $order = $this->sendRequest('post', $this->order, $payload);

        $result[] = [
            'id' => $order['id'],
            'user' => [
                'id' => $order['customer_id'],
                'username' => $order['billing']['first_name'].' '.$order['billing']['last_name'],
                'phone' => $order['billing']['phone'],
                'email' => $order['billing']['email'],
                'first_name' => $order['billing']['first_name'],
                'last_name' => $order['billing']['last_name'],
                'gender' => 'female',
                'birth_date' => '2015-12-06',
            ],
            'address' => [
                'id' => 2,
                'address_1' => $order['billing']['address_1'],
                'address_2' => $order['billing']['address_2'],
                'city' => $order['billing']['city'],
                'state' => $order['billing']['state'],
                'postcode' => $order['billing']['postcode'],
                'country' => $order['billing']['country'],
            ],
            'currency' => $order['currency'],
            'tax' => $order['total_tax'],
            'total_amount' => count($order['line_items']),
            'total_price' => $order['total'],
            'subtotal' => (($order['total'] ?? 0) + ($order['discount_total'] ?? 0)),
            'discount_total' => $order['discount_total'],
            'total_tax' => $order['total_tax'],
            'billing_email' => $order['billing']['email'],
            'payment_method' => $order['payment_method'],
            'shipment_note' => $order['customer_note'],
            'status' => $order['status'],
            'payment_status' => 'paid',
            'Shipping_company' => 'JT&T',
            'Tracking_number' => collect($order['meta_data'])->firstWhere('key', 'jtawb')['value'] ?? null,
            'products' => array_map(function ($item) {
                return [
                    'id' => $item['product_id'],
                    'item_id' => $item['id'],
                    'name' => strip_tags($item['name']),
                    'price' => $item['total'],
                    'quantity' => $item['quantity'],
                    'description' => $item['short_description'] ?? null,
                    'image' => isset($item['image']['src'])
                        ? str_replace('3.73.173.183', 'centerialmall.com', $item['image']['src'])
                        : null,
                ];
            }, $order['line_items']),
            'created_at' => $order['date_created'],
            'updated_at' => $order['date_modified'],
        ];

        return $result;
    }

    // still working on
    public function refundMultipleProducts($orderId, array $items, $amount, $reason)
    {

        $url = "orders/{$orderId}/refunds";
        $response = Http::withOptions([
            'verify' => false,
        ])->withBasicAuth($this->consumer_key, $this->consumer_secret)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->post($this->baseUrl.$url, [
                'amount' => $amount,
                'reason' => $reason ?? 'Multiple items refund',
                'line_items' => $items,
                'refund_payment' => false,
                'api_refund' => false,
            ]);

        if ($response->successful()) {
            return $response->json();
        }
        throw new \Exception('Refund failed: '.$response->body());
    }

    public function fetchAndStoreAllShippings()
    {
        $zones = Zone::all();
        foreach ($zones as $zone) {
            $zoneId = $zone->id;
            $this->fetchAndStoreShippings($zoneId);
        }

        return true;
    }

    public function fetchAndStoreShippings(int $zoneId)
    {
        // $apiUrl = 'https://3.73.173.183/wp-json/wc/v3/shipping/zones';
        // $consumerKey = 'ck_3ac0df7ec455715d7173bef920c7a779ac907c51';
        // $consumerSecret = 'cs_03ea5e697c1d25a77c5f578cf8a88593ba3c1d4e';

        // $response = Http::withOptions([
        //     'verify' => false
        // ])->withBasicAuth($consumerKey, $consumerSecret)
        //     ->get("{$apiUrl}/{$zoneId}/methods");

        $url = $this->baseUrl.'/'.$this->zones.'/'.$zoneId.'/methods';

        $shippings = $this->sendRequest('get', $url);

        if ($shippings) {
            // Loop through shipping methods and store them
            foreach ($shippings as $shipping) {
                Shipping::updateOrCreate(
                    ['instance_id' => $shipping['instance_id']],
                    [
                        'title' => $shipping['title'] ?? '',
                        'method_title' => $shipping['method_title'] ?? '',
                        'method_description' => $shipping['method_description'] ?? '',
                        'order' => $shipping['order'] ?? null,
                        'enabled' => $shipping['enabled'] ?? false,
                        'cost' => $shipping['settings']['cost']['value'] ?? 0,
                        'tax' => $shipping['settings']['tax_status']['value'] ?? 0,
                    ]
                );
            }

            return true;
        }

        throw new \Exception("Failed to fetch shipping methods for zone {$zoneId}: ".$response->body());
    }

    // user data
    public function userProfile($data)
    {
        $data['phone'] = str_replace('+', '', $data->phone);

        return $this->checkCustomer($data);
    }

    // check customer data  on crm
    public function checkCustomer($data)
    {
        $customers = $this->sendRequest('get', $this->customers, [
            'search' => $data['phone'],
        ]);

        if (! (empty($customers))) {
            $firstCustomer = collect($customers)->first();

            return [
                'id' => $firstCustomer['id'],
                'username' => $firstCustomer['username'],
                'phone' => $firstCustomer['billing']['phone'] ?? null,
                'email' => $firstCustomer['email'],
                'first_name' => $firstCustomer['first_name'],
                'last_name' => $firstCustomer['last_name'],
                'gender' => $firstCustomer['gender'] ?? null,
                'birth_date' => $firstCustomer['birth_date'] ?? null,
            ];
        }

        return $customers;
    }

    public function createOrUpdateCustomer($data)
    {
        $phone = isset($data['phone']) ? str_replace('+', '', $data['phone']) : null;
        $payload = [
            'email' => $data['email'] ?? null,
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'gender' => $data['gender'] ?? null,
            'password' => $data['password'] ?? Str::random(10),
            'billing' => [
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $phone,
                'address_1' => $data['address_1'] ?? '',
                'address_2' => $data['address_2'] ?? '',
                'city' => $data['city'] ?? '',
                'state' => $data['state'] ?? '',
                'country' => $data['country'] ?? '',
            ],
            'shipping' => [
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'address_1' => $data['address_1'] ?? '',
                'address_2' => $data['address_2'] ?? '',
                'city' => $data['city'] ?? '',
                'state' => $data['state'] ?? '',
                'country' => $data['country'] ?? '',
            ],
        ];

        $customers = $this->sendRequest('get', $this->customers, [
            'search' => $phone,
        ]);

        if (! (empty($customers))) {
            $firstCustomer = collect($customers)->first();
            // $url = $this->baseUrl . $this->customers . '/' . $firstCustomer['id'];
            $woocommerceUrl = $this->baseUrl.$this->customers.'/'.$firstCustomer['id'];
            $response = Http::withOptions([
                'verify' => false,
            ])->withBasicAuth($this->consumer_key, $this->consumer_secret)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])->put($woocommerceUrl, $payload);

            if (! $response->successful()) {
                return $response;
            }
            $customers = $response->json();
            $firstCustomer = collect($customers)->first();

            return [
                'id' => $firstCustomer['id'],
                'username' => $firstCustomer['username'],
                'phone' => $firstCustomer['billing']['phone'] ?? null,
                'email' => $firstCustomer['email'],
                'first_name' => $firstCustomer['first_name'],
                'last_name' => $firstCustomer['last_name'],
                'gender' => $firstCustomer['gender'] ?? null,
                'birth_date' => $firstCustomer['birth_date'] ?? null,
            ];
        }

        // Clean null values from entire payload
        $cleanedPayload = $this->removeNullValues($payload);
        $response = Http::withOptions([
            'verify' => false,
        ])->withBasicAuth($this->consumer_key, $this->consumer_secret)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl.$this->customers, $cleanedPayload);

        if (! $response->successful() || isset($response['data']['status']) && $response['data']['status'] == 400) {
            return $response = [
                'code' => $response['code'],
                'status' => $response['data']['status'],
            ];
        }

        $customers = $response->json();

        return [
            'id' => $customers['id'],
            'username' => $customers['username'],
            'phone' => $customers['billing']['phone'] ?? null,
            'email' => $customers['email'],
            'first_name' => $customers['first_name'],
            'last_name' => $customers['last_name'],
            'gender' => $customers['gender'] ?? null,
            'birth_date' => $customers['birth_date'] ?? null,
        ];
    }

    private function removeNullValues(array $array): array
    {
        return array_filter($array, function ($value) {
            if (is_array($value)) {
                return ! empty($this->removeNullValues($value));
            }

            return ! is_null($value);
        });
    }

    public function getUserByPhone($email, $phone, $user, $address)
    {
        // 1. search by  phone
        $customers = $this->sendRequest('get', $this->customers, [
            'search' => $phone,
        ]);
        if (! empty($customers)) {
            return $customers[0];
        }
        // 2. If no phone match, search by email
        $customers = $this->sendRequest('get', $this->customers, [
            'search' => $email,
        ]);

        if (! empty($customers)) {
            return $customers[0];
        }

        // 3. Get all customers and search manually by phone
        $customers = $this->sendRequest('get', $this->customers);

        if (! $customers->successful()) {
            \Log::error('WooCommerce API error (phone search)', ['customers' => $customers->body()]);

            return null;
        }

        $customer = collect($customers)->first(function ($cust) use ($email) {
            return isset($cust['billing']['email']) && $cust['billing']['email'] === $email;
        });

        if ($customer) {
            return $customer;
        }

        // 3. If no customer found, create new one
        $payload = [
            'email' => $user['email'] ?? $email,
            'first_name' => $user['first_name'] ?? null,
            'last_name' => $user['last_name'] ?? null,
            'gender' => $user['gender'] ?? null,
            'password' => $user['password'] ?? Str::random(10),
            'billing' => [
                'first_name' => $user['first_name'] ?? null,
                'last_name' => $user['last_name'] ?? null,
                'email' => $user['email'] ?? $email,
                'phone' => $phone,
                'address_1' => $address['address_1'] ?? '',
                'address_2' => $address['address_2'] ?? '',
                'city' => $address['city'] ?? '',
                'state' => $address['state'] ?? '',
                'country' => $address['country'] ?? '',
            ],
            'shipping' => [
                'first_name' => $user['first_name'] ?? null,
                'last_name' => $user['last_name'] ?? null,
                'address_1' => $address['address_1'] ?? '',
                'address_2' => $address['address_2'] ?? '',
                'city' => $address['city'] ?? '',
                'state' => $address['state'] ?? '',
                'country' => $address['country'] ?? '',
            ],
        ];

        return $customers = $this->sendRequest('post', $this->customers, $payload);
    }

    // new Get All Posts
    public function getAllPosts()
    {
        $posts = $this->beautySendRequest('get', $this->posts);

        return $transformedPosts = collect($posts)->map(function ($post) {
            // Extract product IDs safely
            $productIds = collect($post['products'])
                ->filter() // removes null or empty strings
                ->flatMap(function ($item) {
                    return explode(',', $item);
                })
                ->map(fn ($id) => (int) trim($id))
                ->unique()
                ->toArray();

            // Fetch products from the database
            $products = Product::whereIn('id', $productIds)->get();

            // Replace products field with actual product data
            $post['products'] = SinglePostResource::collection($products);

            $follow = false;
            // Check if user is authenticated and liked the post
            if (auth()->check()) {

                $inf = $post['user']['user_id'];
                $followersInfluencers = $this->followersInfluencers();

                foreach ($followersInfluencers as $item) {
                    if (! empty($item)) {
                        foreach ($item as $influencer) {

                            if ($influencer = $inf) {
                                $follow = true;
                            }
                        }
                    }
                }
            }

            $post['follow'] = $follow;
            $post['user']['follow'] = $follow;
            if (isset($post['file_urls']) && is_array($post['file_urls'])) {
                $post['file_urls'] = array_map(function ($url) {
                    return str_replace('3.73.173.183', 'centerialmall.com', $url);
                }, $post['file_urls']);
            }

            return $post;
        });
    }

    // Get Single Post
    public function getSinglePost($id)
    {
        $response = Http::withOptions([
            'verify' => false,
        ])->get('https://diva.sa/wp-json/procontent/v1/single-post/', [
            'content_id' => $id,
        ]);

        $post = $response->json();

        if (! isset($post['content'])) {
            return response()->json(['message' => 'Content not found'], 404);
        }

        // Extract and map products
        $productIds = collect($post['content'][0]['products'])
            ->filter()
            ->flatMap(fn ($item) => explode(',', $item))
            ->map(fn ($id) => (int) trim($id))
            ->unique()
            ->toArray();

        $products = Product::whereIn('id', $productIds)->get();

        $liked = false;
        $saved = false;
        $is_following = false;
        // Check if user is authenticated and liked the post
        if (auth()->check()) {

            $likedPosts = $this->likedContents();

            if (
                isset($likedPosts['contents']) &&
                collect($likedPosts['contents'])->pluck('content_id')->contains((string) $id)
            ) {
                $liked = true;
            }

            $savedPosts = $this->savedContents();

            if (
                isset($savedPosts['contents']) &&
                collect($savedPosts['contents'])->pluck('content_id')->contains((string) $id)
            ) {
                $saved = true;
            }
        }

        // Final response structure
        $post['content'][0]['products'] = SinglePostResource::collection($products);
        $post['content'][0]['Liked'] = $liked;
        $post['content'][0]['Saved'] = $saved;
        $post['products'] = $products;

        return response()->json($post);
    }

    private function followStatus($inf, $user)
    {
        $response = Http::withOptions([
            'verify' => false,
        ])->get('https://diva.sa/wp-json/procontent/v1/follow-status/', [
            'user_id' => $user,
            'sec_user_id' => (int) $inf,
        ]);

        if (! $response->successful()) {
            return [];
        }

        return $follow = $response->json();
    }
    // Get All Comments

    public function getAllComments($id, $page = 1, $perPage = 10)
    {
        $response = Http::withOptions([
            'verify' => false,
        ])->get('https://diva.sa/wp-json/procontent/v1/comments', [
            'consumer_key' => $this->consumer_key,
            'consumer_secret' => $this->consumer_secret,
            'content_id' => $id,
            'page' => $page,
            'per_page' => $perPage,
        ]);

        if (! $response->successful()) {
            return response()->json([
                'status' => 'success',
                'comments' => [],
            ]);
        }

        return $response->json() ?? [];
    }

    // Get influencer Followers  And Following
    // stop on here
    public function getInfluencerFollowersAndFollowing($id)
    {
        $response = Http::withOptions([
            'verify' => false,
        ])->get('https://diva.sa/wp-json/procontent/v1/follower-count/', [
            'consumer_key' => $this->consumer_key,
            'consumer_secret' => $this->consumer_secret,
            'user_id' => $id,
        ]);

        if (! $response->successful()) {
            return [];
        }

        $response1 = Http::withOptions([
            'verify' => false,
        ])->get('https://diva.sa/wp-json/procontent/v1/following-count/', [
            'consumer_key' => $this->consumer_key,
            'consumer_secret' => $this->consumer_secret,
            'user_id' => $id,
        ]);

        if (! $response1->successful()) {
            return [];
        }
        $followings = $response1->json() ?? [];
        $followers = $response->json() ?? [];

        return response()->json([
            'followings' => $followings,
            'followers' => $followers,
        ]);
    }

    // Get All Stores
    public function getAllStores()
    {
        $response = Http::withOptions([
            'verify' => false,
        ])->get('https://diva.sa/wp-json/wp-story/v1/box/27820', [
            'consumer_key' => $this->consumer_key,
            'consumer_secret' => $this->consumer_secret,
        ]);

        if (! $response->successful()) {
            return null;
        }
        $data = $response->json();

        return $transformed = collect($data['circles'] ?? [])->map(function ($circle) {
            return [
                'authorName' => $circle['authorName'] ?? null,
                'coverImage' => $circle['coverImage'] ?? null,
                'coverAuthorImage' => $circle['coverAuthorImage'] ?? null,
                'authorImage' => $circle['authorImage'] ?? null,
                'coverName' => $circle['coverName'] ?? null,
                'type' => $circle['type'] ?? null,
                'items' => collect($circle['items'] ?? [])->map(function ($item) {
                    return [
                        'type' => $item['type'] ?? null,
                        'src' => $item['src'] ?? null,
                        'product' => $item['button']['link'] ?? null,
                    ];
                })->toArray(),
            ];
        });
    }

    // Like Post
    public function likePost($id)
    {
        $user = auth()->user();
        $data = $user;
        $data['phone'] = str_replace('+', '', $user->phone);
        $address = Address2::where('user_id', $user->id)
            ->where('status', 'active')->where('default', true)
            ->first();
        $address = $address ?? [];

        $customer = $this->getUserByPhone($user->email, $user->phone, $user, $address);

        return $response = Http::withOptions([
            'verify' => false,
        ])->post('https://diva.sa/wp-json/procontent/v1/like-toggle/', [
            'user_id' => $customer['id'],
            'content_id' => $id,
        ]);

        if (! $response->successful()) {
            return null;
        }
        $data = $response->json();
    }

    // Save Post
    public function savePost($id)
    {
        $user = auth()->user();
        $data = $user;
        $data['phone'] = str_replace('+', '', $user->phone);
        $address = Address2::where('user_id', $user->id)
            ->where('status', 'active')->where('default', true)
            ->first();
        $address = $address ?? [];

        $customer = $this->getUserByPhone($user->email, $user->phone, $user, $address);

        $response = Http::withOptions([
            'verify' => false,
        ])->post('https://diva.sa/wp-json/procontent/v1/save-toggle/', [
            'user_id' => $customer['id'],
            'content_id' => $id,
        ]);

        if (! $response->successful()) {
            return null;
        }

        return $data = $response->json();
    }

    // Save Comment
    public function saveComment($id, $comment)
    {
        $user = auth()->user();
        $data = $user;
        $data['phone'] = str_replace('+', '', $user->phone);
        $address = Address2::where('user_id', $user->id)
            ->where('status', 'active')->where('default', true)
            ->first();
        $address = $address ?? [];

        $customer = $this->getUserByPhone($user->email, $user->phone, $user, $address);

        $response = Http::withOptions([
            'verify' => false,
        ])->post('https://diva.sa/wp-json/procontent/v1/submit-comment/', [
            'user_id' => $customer['id'],
            'content_id' => $id,
            'comment_text' => $comment,
        ]);

        if (! $response->successful()) {
            return null;
        }

        return $data = $response->json();
    }

    // nested Comment
    public function nestedComment($content_id, $parent_id, $comment)
    {
        $user = auth()->user();
        $data = $user;
        $data['phone'] = str_replace('+', '', $user->phone);
        $address = Address2::where('user_id', $user->id)
            ->where('status', 'active')->where('default', true)
            ->first();
        $address = $address ?? [];

        $customer = $this->getUserByPhone($user->email, $user->phone, $user, $address);

        $response = Http::withOptions([
            'verify' => false,
        ])->post('https://diva.sa/wp-json/procontent/v1/submit-comment/', [
            'user_id' => $customer['id'],
            'content_id' => $content_id,
            'parent_id' => $parent_id,
            'comment_text' => $comment,
        ]);

        if (! $response->successful()) {
            return null;
        }

        return $data = $response->json();
    }

    // Follow User
    public function followUser($id)
    {
        $user = auth()->user();
        $data = $user;
        $data['phone'] = str_replace('+', '', $user->phone);
        $address = Address2::where('user_id', $user->id)
            ->where('status', 'active')->where('default', true)
            ->first();
        $address = $address ?? [];

        $customer = $this->getUserByPhone($user->email, $user->phone, $user, $address);

        $response = Http::withOptions([
            'verify' => false,
        ])->post('https://diva.sa/wp-json/procontent/v1/follow_unfollow_user/', [
            'user_id' => $customer['id'],
            'followed_user_id' => $id,
        ]);

        if ($response->json() == 'Cannot follow yourself') {
            return response()->json([
                'status' => 404,
                'success' => false,
                'message' => 'Cannot follow yourself',

            ], 404);
        }

        return $data = $response->json();
    }

    // User Contents
    public function userContents($id)
    {
        $response = Http::withOptions([
            'verify' => false,
        ])->get('https://diva.sa/wp-json/procontent/v1/user-contents', [
            'user_id' => $id ?? '86',
        ]);

        if (! $response->successful()) {
            return $response;
        }

        $contents = $response->json() ?? [];

        return $transformedContents = collect($contents)->map(function ($post) {
            // Extract product IDs safely
            $productIds = collect($post['product_ids'] ?? [])
                ->filter() // removes null or empty strings
                ->flatMap(function ($item) {
                    return explode(',', $item);
                })
                ->map(fn ($id) => (int) trim($id))
                ->unique()
                ->toArray();

            // Fetch products from the database
            $products = Product::whereIn('id', $productIds)->get();

            // Replace products field with actual product data
            $post['product_ids'] = SinglePostResource::collection($products);

            // if (isset($post['file_urls']) && is_array($post['file_urls'])) {
            //     $post['file_urls'] = array_map(function ($url) {
            //         return str_replace('3.73.173.183', 'diva.sa', $url);
            //     }, $post['file_urls']);
            // }
            return $post;
        });
    }

    // User Products
    public function userProducts($id)
    {
        $response = Http::withOptions([
            'verify' => false,
        ])->get('https://diva.sa/wp-json/procontent/v1/user-products', [
            'user_id' => $id ?? '86',
        ]);

        if (! $response->successful()) {
            return $response;
        }

        return $products = $response->json() ?? [];

        return $transformedProducts = collect($products)->map(function ($product) {

            if (isset($product['link'])) {
                $product['link'] = str_replace('3.73.173.183', 'diva.sa', $product['link']);
            }
            if (isset($product['image'])) {
                $product['image'] = str_replace('3.73.173.183', 'diva.sa', $product['image']);
            }

            return $product;
        });
    }

    //  Liked Contents
    public function likedContents()
    {
        $user = auth()->user();
        $data = $user;
        $data['phone'] = str_replace('+', '', $user->phone);
        $address = Address2::where('user_id', $user->id)
            ->where('status', 'active')->where('default', true)
            ->first();
        $address = $address ?? [];

        $customer = $this->getUserByPhone($user->email, $user->phone, $user, $address);

        $response = Http::withOptions([
            'verify' => false,
        ])->get('https://diva.sa/wp-json/procontent/v1/liked-content/', [
            'user_id' => $customer['id'],
        ]);

        $data = $response->json();

        // if (isset($data['contents']) && is_array($data['contents'])) {
        //     $data['contents'] = array_map(function ($item) {
        //         if (isset($item['file_url'])) {
        //             $item['file_url'] = str_replace('3.73.173.183', 'diva.sa', $item['file_url']);
        //         }
        //         return $item;
        //     }, $data['contents']);
        // }

        return $data;
    }

    //  Saved Contents
    public function savedContents()
    {
        $user = auth()->user();
        $data = $user;
        $data['phone'] = str_replace('+', '', $user->phone);
        $address = Address2::where('user_id', $user->id)
            ->where('status', 'active')->where('default', true)
            ->first();
        $address = $address ?? [];

        $customer = $this->getUserByPhone($user->email, $user->phone, $user, $address);

        $response = Http::withOptions([
            'verify' => false,
        ])->get('https://diva.sa/wp-json/procontent/v1/saved-content/', [
            'user_id' => $customer['id'],
        ]);

        $data = $response->json();

        // if (isset($data['contents']) && is_array($data['contents'])) {
        //     $data['contents'] = array_map(function ($item) {
        //         if (isset($item['file_url'])) {
        //             $item['file_url'] = str_replace('3.73.173.183', 'diva.sa', $item['file_url']);
        //         }
        //         return $item;
        //     }, $data['contents']);
        // }

        return $data;
    }

    // followersInfluencers
    public function followersInfluencers()
    {
        $user = auth()->user();
        $data = $user;
        $data['phone'] = str_replace('+', '', $user->phone);
        $address = Address2::where('user_id', $user->id)
            ->where('status', 'active')->where('default', true)
            ->first();
        $address = $address ?? [];

        $customer = $this->getUserByPhone($user->email, $user->phone, $user, $address);

        $response = Http::withOptions([
            'verify' => false,
        ])->get('https://diva.sa/wp-json/procontent/v1/following-influencers/', [
            'user_id' => $customer['id'],
        ]);

        $data = $response->json();

        return $data['influencers'];
    }

    // all influencres
    public function allInfluencers()
    {

        $response = Http::withOptions([
            'verify' => false,
        ])->get('https://diva.sa/wp-json/procontent/v1/all-influencers');

        $authId = Auth::id();

        $users = User::when($authId, fn ($q) => $q->where('id', '!=', $authId))->where('type', 'customer')->get();
        if (Auth::check()) {
            $followedIds = FollowRequest::where('status', 'pending')
                ->where('follower_id', $authId)->pluck('followed_id')->toArray();
            $friendIds = UserFriend::where('user_id', $authId)->pluck('friend_id')->toArray();

            $users = $users->map(function ($user) use ($followedIds, $friendIds) {
                return [
                    'id' => $user->id,
                    'name' => $user->username,
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'is_private' => $user->is_private,
                    'profile_photo' => null,
                    'follow_request' => in_array($user->id, $followedIds),
                    'friend' => in_array($user->id, $friendIds),
                ];
            })->toArray();
        } else {
            // Not authenticated — always return false for both
            $users = $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->username,
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'is_private' => $user->is_private,
                    'profile_photo' => null,
                    'follow_request' => false,
                    'friend' => false,
                ];
            })->toArray();
        }
        // 2. Decode the external response
        $responseData = $response->json();
        $remoteInfluencers = $responseData['influencers'] ?? [];
        $remoteInfluencers = collect($remoteInfluencers)->map(function ($influencer) {
            $influencer['type'] = 'influencer';

            return $influencer;
        })->toArray();

        // 3. Merge local users with external influencers
        $allInfluencers = array_merge($remoteInfluencers, $users);

        // 4. Update the influencers list
        $responseData['influencers'] = $allInfluencers;

        // 5. Return the updated response
        return response()->json($responseData);
    }

    // followedInfluencers

    public function followedInfluencersPosts()
    {
        $user = auth()->user();
        $id = $user->dashboard_id ?? null;
        if ($id == null) {
            $data = $user;
            $data['phone'] = str_replace('+', '', $user->phone);
            $address = Address2::where('user_id', $user->id)
                ->where('status', 'active')->where('default', true)
                ->first();
            $address = $address ?? [];

            $customer = $this->getUserByPhone($user->email, $user->phone, $user, $address);
            $id = $customer['id'];
        }

        $response = Http::withOptions([
            'verify' => false,
        ])->get('https://diva.sa/wp-json/procontent/v1/all-follower-post/', [
            'user_id' => $id,
        ]);

        $posts = $response->json();

        return $transformedPosts = collect($posts)->map(function ($post) {
            // Extract product IDs safely
            $productIds = collect($post['products'])
                ->filter() // removes null or empty strings
                ->flatMap(function ($item) {
                    return explode(',', $item);
                })
                ->map(fn ($id) => (int) trim($id))
                ->unique()
                ->toArray();

            // Fetch products from the database
            $products = Product::whereIn('id', $productIds)->get();

            // Replace products field with actual product data
            $post['products'] = SinglePostResource::collection($products);

            $follow = false;
            // Check if user is authenticated and liked the post
            if (auth()->check()) {

                $inf = $post['user']['user_id'];
                $followersInfluencers = $this->followersInfluencers();

                foreach ($followersInfluencers as $item) {
                    if (! empty($item)) {
                        foreach ($item as $influencer) {

                            if ($influencer = $inf) {
                                $follow = true;
                            }
                        }
                    }
                }
            }

            $post['follow'] = $follow;
            $post['user']['follow'] = $follow;

            // if (isset($post['file_urls']) && is_array($post['file_urls'])) {
            //     $post['file_urls'] = array_map(function ($url) {
            //         return str_replace('3.73.173.183', 'centerialmall.com', $url);
            //     }, $post['file_urls']);
            // }

            // $post['follow'] = $follow;
            return $post;
        });
    }

    // Get All Collections

    public function getAllCollections()
    {
        $user = auth()->user();
        $id = $user->dashboard_id ?? null;
        if ($id == null) {
            $data = $user;
            $data['phone'] = str_replace('+', '', $user->phone);
            $address = Address2::where('user_id', $user->id)
                ->where('status', 'active')->where('default', true)
                ->first();
            $address = $address ?? [];

            $customer = $this->getUserByPhone($user->email, $user->phone, $user, $address);
            $id = $customer['id'];
        }

        $response = Http::withOptions([
            'verify' => false,
        ])->get('https://diva.sa/wp-json/procontent/v1/collections/', [
            'user_id' => $id,
        ]);

        return $collections = $response->json();
    }

    // Influencers
    // Get Influencer Posts
    public function getInfluencerPosts()
    {
        $user = auth()->user();
        $data = $user;
        $data['phone'] = str_replace('+', '', $user->phone);
        $address = Address2::where('user_id', $user->id)
            ->where('status', 'active')->where('default', true)
            ->first();
        $address = $address ?? [];

        $customer = $this->getUserByPhone($user->email, $user->phone, $user, $address);

        $response = Http::withOptions([
            'verify' => false,
        ])->get('https://diva.sa/wp-json/procontent/v1/user-contents/', [
            'user_id' => $customer['id'],
        ]);

        return $posts = $response->json();
    }

    // Get Influencer Products
    public function getInfluencerProducts()
    {
        $user = auth()->user();
        $data = $user;
        $data['phone'] = str_replace('+', '', $user->phone);
        $address = Address2::where('user_id', $user->id)
            ->where('status', 'active')->where('default', true)
            ->first();
        $address = $address ?? [];

        $customer = $this->getUserByPhone($user->email, $user->phone, $user, $address);

        $response = Http::withOptions([
            'verify' => false,
        ])->get('https://diva.sa/wp-json/procontent/v1/user-products/', [
            'user_id' => $customer['id'],
        ]);

        return $products = $response->json();
    }

    // Get Influencer Banner
    public function getInfluencerProfile()
    {
        $user = auth()->user();
        $data = $user;
        $data['phone'] = str_replace('+', '', $user->phone);
        $address = Address2::where('user_id', $user->id)
            ->where('status', 'active')->where('default', true)
            ->first();
        $address = $address ?? [];

        $customer = $this->getUserByPhone($user->email, $user->phone, $user, $address);

        $response = Http::withOptions([
            'verify' => false,
        ])->get('https://diva.sa/wp-json/procontent/v1/profile/', [
            'user_id' => $customer['id'],
        ]);

        $profile = $response->json();

        // if (isset($profile['profile_photo']) && !empty($profile['profile_photo'])) {
        //     $profile['profile_photo'] = str_replace('3.73.173.183', 'centerialmall.com', $profile['profile_photo']);
        // }
        return $profile;
    }

    // Create Post
    public function createPost($data)
    {
        // return ($data['description']);
        $user = auth()->user();
        // $data = $user;
        $data['phone'] = str_replace('+', '', $user->phone);
        $address = Address2::where('user_id', $user->id)
            ->where('status', 'active')->where('default', true)
            ->first();
        $address = $address ?? [];

        $customer = $this->getUserByPhone($user->email, $user->phone, $user, $address);

        $multipartData = [
            [
                'name' => 'user_id',
                'contents' => 86 ?? $customer['id'],
            ],
            [
                'name' => 'description',
                'contents' => $data['description'] ?? '',
            ],
            [
                'name' => 'product_ids',
                'contents' => $data['product_ids'] ?? '',
            ],

        ];

        foreach ($data['media'] ?? [] as $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                $multipartData[] = [
                    'name' => 'content_media[]',
                    'contents' => fopen($file->getPathname(), 'r'),
                    'filename' => $file->getClientOriginalName(),
                ];
            }
        }
        // return $multipartData;
        $response = Http::withOptions([
            'verify' => false,
        ])->asMultipart()->post('https://diva.sa/wp-json/procontent/v1/create-post/', $multipartData);

        if ($response->json() == 'Cannot follow yourself') {
            return response()->json([
                'status' => 404,
                'success' => false,
                'message' => 'Cannot follow yourself',

            ], 404);
        }
        $data = $response->json();

        // if (isset($data['media_urls']) && is_array($data['media_urls'])) {
        //     $data['media_urls'] = array_map(function ($url) {
        //         return str_replace('3.73.173.183', 'centerialmall.com', $url);
        //     }, $data['media_urls']);
        // }

        return $data;
    }
    // send post to admin

    public function sendPostToCenterial(array $data)
    {
        $multipartData = [
            [
                'name' => 'user_id',
                'contents' => $data['user_id'],
            ],
            [
                'name' => 'description',
                'contents' => $data['description'],
            ],
            [
                'name' => 'product_ids',
                'contents' => $data['product_ids'],
            ],
        ];

        // Append media files
        foreach ($data['media'] ?? [] as $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                $multipartData[] = [
                    'name' => 'content_media[]',
                    'contents' => fopen($file->getPathname(), 'r'),
                    'filename' => $file->getClientOriginalName(),
                ];
            }
        }

        // Send POST request
        $response = Http::withHeaders([
            'verify' => false,
        ])->asMultipart()->post('https://diva.sa/wp-json/procontent/v1/create-post/', $multipartData);

        $data = $response->json();
        // if (isset($data['media_urls']) && is_array($data['media_urls'])) {
        //     $data['media_urls'] = array_map(function ($url) {
        //         return str_replace('3.73.173.183', 'centerialmall.com', $url);
        //     }, $data['media_urls']);
        // }

        return $data;
    }

    // delete Post
    public function deletePost($id)
    {
        $response = Http::withOptions([
            'verify' => false,
        ])->delete('https://diva.sa/wp-json/procontent/v1/delete_content/', [
            'content_id' => $id,
        ]);

        return $post = $response->json();
    }

    // Create Banner
    public function createBanner($data)
    {
        $user = auth()->user();
        $data['phone'] = str_replace('+', '', $user->phone);
        $address = Address2::where('user_id', $user->id)
            ->where('status', 'active')->where('default', true)
            ->first();
        $address = $address ?? [];

        $customer = $this->getUserByPhone($user->email, $user->phone, $user, $address);

        $multipartData = [
            [
                'name' => 'user_id',
                'contents' => $customer['id'],
            ],

        ];

        $multipartData[] = [
            'name' => 'banner',
            'contents' => fopen($data['image']->getPathname(), 'r'),
            'filename' => $data['image']->getClientOriginalName(),
        ];

        $response = Http::withOptions([
            'verify' => false,
        ])->asMultipart()->post('https://diva.sa/wp-json/procontent/v1/add_user_banner', $multipartData);

        return $data = $response->json();
    }

    // delete Banner
    public function deleteBanner()
    {

        $user = auth()->user();
        $data['phone'] = str_replace('+', '', $user->phone);
        $address = Address2::where('user_id', $user->id)
            ->where('status', 'active')->where('default', true)
            ->first();
        $address = $address ?? [];

        $customer = $this->getUserByPhone($user->email, $user->phone, $user, $address);
        $response = Http::withOptions([
            'verify' => false,
        ])->delete('https://diva.sa/wp-json/procontent/v1/remove_user_banner', [
            'user_id' => $customer['id'],
        ]);

        return $post = $response->json();
    }

    //  update profile picture
    public function updateProfilePicture($data)
    {
        $user = auth()->user();
        $data['phone'] = str_replace('+', '', $user->phone);
        $address = Address2::where('user_id', $user->id)
            ->where('status', 'active')->where('default', true)
            ->first();
        $address = $address ?? [];

        $customer = $this->getUserByPhone($user->email, $user->phone, $user, $address);

        $multipartData = [
            [
                'name' => 'user_id',
                'contents' => $customer['id'],
            ],

        ];

        $multipartData[] = [
            'name' => 'avatar',
            'contents' => fopen($data['image']->getPathname(), 'r'),
            'filename' => $data['image']->getClientOriginalName(),
        ];

        $response = Http::withOptions([
            'verify' => false,
        ])->asMultipart()->post('https://diva.sa/wp-json/procontent/v1/profile_image', $multipartData);

        return $data = $response->json();
    }

    // delete profile picture
    public function deleteProfilePicture()
    {

        $user = auth()->user();
        $data['phone'] = str_replace('+', '', $user->phone);
        $address = Address2::where('user_id', $user->id)
            ->where('status', 'active')->where('default', true)
            ->first();
        $address = $address ?? [];

        $customer = $this->getUserByPhone($user->email, $user->phone, $user, $address);
        $response = Http::withOptions([
            'verify' => false,
        ])->delete('https://diva.sa/wp-json/procontent/v1/dell_profile_image', [
            'user_id' => $customer['id'],
        ]);

        return $avatar = $response->json();
    }

    // user stories
    // get user stories
    public function createUserStory($data)
    {
        $user = auth()->user();
        $data['phone'] = str_replace('+', '', $user->phone);
        $address = Address2::where('user_id', $user->id)
            ->where('status', 'active')->where('default', true)
            ->first();
        $address = $address ?? [];

        $customer = $this->getUserByPhone($user->email, $user->phone, $user, $address);

        $multipartData = [
            [
                'name' => 'user_id',
                'contents' => $customer['id'],
            ],

        ];

        $multipartData[] = [
            'name' => 'file',
            'contents' => fopen($data['image']->getPathname(), 'r'),
            'filename' => $data['image']->getClientOriginalName(),
        ];

        $response = Http::withOptions([
            'verify' => false,
        ])->asMultipart()->post('https://diva.sa/wp-json/wpstory/v1/public-stories', $multipartData);

        return $data = $response->json();
    }

    public function getUserStories()
    {
        $user = auth()->user();
        $data = $user;
        $data['phone'] = str_replace('+', '', $user->phone);
        $address = Address2::where('user_id', $user->id)
            ->where('status', 'active')->where('default', true)
            ->first();
        $address = $address ?? [];

        $customer = $this->getUserByPhone($user->email, $user->phone, $user, $address);
        $id = $customer['id'] ?? 11; // Default to 86 if id is not set
        $response = Http::withOptions([
            'verify' => false,
        ])->get("https://diva.sa/wp-json/wpstory/v1/public-stories/{$id}");

        return $profile = $response->json();
    }

    public function deleteUserStory($id)
    {
        // $user = auth()->user();
        // $data = $user;
        // $data['phone'] = str_replace('+', '', $user->phone);
        // $address = Address2::where('user_id', $user->id)
        //     ->where('status', 'active')->where('default', true)
        //     ->first();
        // $address = $address ?? [];

        // $customer = $this->getUserByPhone($user->email, $user->phone, $user, $address);

        $response = Http::withOptions([
            'verify' => false,
        ])->delete("https://diva.sa/wpstory/v1/public-stories/{$id}");

        return $profile = $response->json();
    }
}
