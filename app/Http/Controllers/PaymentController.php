<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Order;
use App\Models\Coupon;
use GuzzleHttp\Client;
use App\Models\Address;
use App\Models\Package;
use App\Models\Service;
use App\Models\Address2;
use App\Models\CouponLog;
use App\Models\CouponUser;
use App\Models\Appointment;
use App\Services\DyService;
use Illuminate\Support\Str;
use App\Models\Notification;
use App\Models\Subscription;
use App\Models\TabbyPayment;
use Illuminate\Http\Request;
use App\Models\TamaraPayment;
use App\Models\AppointmentLog;
use App\Services\TabbyService;
use App\Models\TechDailyRecord;
use App\Services\TamaraService;
use App\Helper\ApiResponseHelper;
use App\Models\AppointmentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Services\NotificationService;
use Kreait\Firebase\Messaging\CloudMessage;
use App\Services\FirebaseNotificationService;
use Kreait\Laravel\Firebase\Facades\Firebase;
use App\Models\Cart;

class PaymentController extends Controller
{
    use ApiResponseHelper;



    protected $apiUrl;
    protected $apiKey;
    protected $tamaraService;

    protected $notification;
    protected $firebaseNotificationService;

    protected $notificationService;

    public function __construct(NotificationService $notificationService, TamaraService $tamaraService, FirebaseNotificationService $firebaseNotificationService)
    {
        $this->tamaraService = $tamaraService;
        $this->apiUrl = env('TAMARA_API_URL');
        $this->apiKey = env('TAMARA_API_KEY');
        $this->notification = Firebase::messaging();
        $this->firebaseNotificationService = $firebaseNotificationService;
        //for tech
        $this->notificationService = $notificationService;
    }

    public function  createSessionFromApp(Request $request)
    {
        // dd($request->all());
        $tabby = new TabbyService();
        $response = $tabby->retrieveTabbySession($request->session_id);
        if ($response['status'] == 'rejected') {
            return response()->json([
                'status' => 'rejected',
            ], 400);
        }
        $request->validate([
            'amount' => 'required|string',
            'session_id' => 'required|string',
            'payment_type' => 'required|string',
        ]);
        // Check if user is authenticated
        if (!auth()->check()) {
            return $this->setCode(code: 401)->setData([])->setMessage('Unauthorized')->send();
        }
        $user = $request->user();
        $tabby_payment = new TabbyPayment();
        $tabby_payment->user_id =  $user->id;
        $tabby_payment->session_id =  $request->session_id;
        $tabby_payment->amount =  $request->amount;
        $tabby_payment->save();


        $user = auth()->user();
        $user_id = auth()->id();
        $cart = Cart::where('user_id', $user_id)->with('products')->first();

        // Check if the user has a cart
        if (!$cart) {
            return $this->setCode(code: 401)->setData([])->setMessage('Cart not found')->send();
        }

        $products = $cart->products;

        // Check if the cart has products
        if ($products->isEmpty()) {
            return $this->setCode(code: 401)->setData([])->setMessage('No products in the cart')->send();
        }

        $address = Address2::where('user_id', auth()->id())
            ->where('default', 1)
            ->first();

        if (!$address) {
            return $this->setCode(code: 401)->setData([])->setMessage('Default address not found')->send();
        }
        $data['address_id'] = $address->id;
        $data['currency'] =   $data['currency'] ?? 'SAR';
        //  // Calculate total price and total quantity
        $totalPrice = 0;
        $totalQuantity = 0;

        foreach ($products as $product) {
            $totalQuantity += $product->quantity;
            $totalPrice += $product->quantity * $product->product->price;
        }

        // Add total price & total quantity to the order data
        $data['total_price'] = $totalPrice;
        $data['total_amount'] = $totalQuantity;
        $data['user_id'] = $user->id;
        $data['payment_method'] = "Tabby";
        // Create order
        $order = Order::create($data);
        // Attach products to order
        foreach ($products as $product) {
            $order->products()->attach($product->product_id, [
                'quantity' => $product->quantity,
                'standard' => $product->standard,
                'right_standard' => $product->right_standard,
                'right_quantity' => $product->right_quantity,
                'left_standard' => $product->left_standard,
                'left_quantity' => $product->left_quantity,
                'price' => $product->product->price,
            ]);
        }
        if (!empty($user->fcm_token)) {
            $title = $request->title ?? ' طلب جديد  ';
            $body =  $request->body ?? '  لديك طلب جديد';
            $data = [
                'order_id' => $order->id,
                'order_status' => $order->status,
                'order_total' => $order->total_price,
                'order_date' => $order->created_at,
            ];
            $response = $this->firebaseNotificationService->sendNotification($user->fcm_token,  $user->id, $title, $body, $data ?? []);
        }

        return response()->json([
            'status' => 'created',
            'session_id' => $tabby_payment->session_id,
            'reference_id' => $tabby_payment->reference_id,
            'order_id' => $order->id,
        ], 201);
    }
    public function getPaymentStatus(Request $request)
    {

        $tabby = new TabbyService();
        $sessionPayment = $tabby->retrieveTabbySession($request->session_id);
        $payment_id = $sessionPayment['payment']['id'];

        $tabby_payment = TabbyPayment::where('reference_id', $request->reference_id)->first();
        $tabby_payment->update([
            'payment_id' => $payment_id,
        ]);

        $tabby = new TabbyService();
        $retrievePayment = $tabby->retrieveTabbyPayment($payment_id);

        if ($retrievePayment['status'] == "AUTHORIZED") {
            $tabby_payment->update([
                'status' => $retrievePayment['status'],
            ]);
            $payment = $tabby->capturePaymentRequest($payment_id, $request->reference_id, $tabby_payment->amount);
            $tabby_payment->update([
                'status' => $payment['status'],
            ]);
        } else {
            $payment = false;
            $tabby_payment->update([
                'status' => $retrievePayment['status'],
            ]);
        }

        // Return a response to acknowledge receipt of the webhook
        return response()->json($tabby_payment);
    }

    public function createPaymentSession(Request $request)
    {
        $request->validate([
            'amount' => 'required|string',
            'lang' => 'required|string',
            'product_type' => 'required|string:in:package,service',
            'item_id' => 'required|integer',
            'qty' => 'required|integer',

        ]);
        $data = $request->all();
        $data['user'] = $request->user();
        $data['item'] = $request->product_type == 'package' ? Package::find($request->item_id) : Service::find($request->item_id);

        $tabby_payment = new TabbyPayment();
        $tabby_payment->user_id =  $data['user']->id;
        $tabby_payment->amount =  $data['amount'];
        $tabby_payment->save();
        $data['reference_id'] = $tabby_payment->reference_id;
        $tabby = new TabbyService();
        $payment = $tabby->createSession($data);
        return $payment;
    }

    public function handleTabbySuccessWebhook(Request $request)
    {
        Log::info('Tabby Webhook:', $request);
        // $tabby_payment = TabbyPayment::where('reference_id', $id)->first();
        // $tabby_payment->update([
        //     'payment_id' => $request->payment_id,
        // ]);
        // $tabby = new TabbyService();
        // $retrievePayment = $tabby->retrieveTabbyPayment($request->payment_id);
        // if ($retrievePayment['status'] == "AUTHORIZED") {
        //     $tabby_payment->update([
        //         'status' => $retrievePayment['status'],
        //     ]);
        //     $payment = $tabby->capturePaymentRequest($request->payment_id, $id, $tabby_payment->amount);
        //     $tabby_payment->update([
        //         'status' => $payment['status'],
        //     ]);
        // } else {
        //     $payment = false;
        //     $tabby_payment->update([
        //         'status' => $retrievePayment['status'],
        //     ]);
        // }
        // if ($lang == 'en') {
        //     $message = 'You aborted the payment. Please retry or choose another payment method.';
        // } else {
        //     $message = 'لقد ألغيت الدفعة. فضلاً حاول مجددًا أو اختر طريقة دفع أخرى.';
        // }

        // $data = ['status' => 'success', 'capture_data' => $payment, 'retrievePayment' => $retrievePayment, 'message' => $message];
        // // Return a response to acknowledge receipt of the webhook
        // return response($data);
    }

    public function handleTabbyCancelWebhook(Request $request, $id, $lang)
    {
        $payload = $request->all();
        // Log the payload for debugging
        Log::info('Tabby Webhook:', $payload);
        $tabby_payment = TabbyPayment::where('reference_id', $id)->first();
        $tabby_payment->update([
            'payment_id' => $request->payment_id,
        ]);
        $tabby = new TabbyService();
        $retrievePayment = $tabby->retrieveTabbyPayment($request->payment_id);
        if ($retrievePayment['status'] == "AUTHORIZED") {
            $tabby_payment->update([
                'status' => $retrievePayment['status'],
            ]);
            $payment = $tabby->capturePaymentRequest($request->payment_id, $id, $tabby_payment->amount);
            $tabby_payment->update([
                'status' => $payment['status'],
            ]);
        } else {
            $payment = false;
            $tabby_payment->update([
                'status' => $retrievePayment['status'],
            ]);
        }
        if ($lang == 'en') {
            $message = 'You aborted the payment. Please retry or choose another payment method.';
        } else {
            $message = 'لقد ألغيت الدفعة. فضلاً حاول مجددًا أو اختر طريقة دفع أخرى.';
        }
        // Return a response to acknowledge receipt of the webhook
        return response()->json(['status' => $message]);
    }

    public function handleTabbyFailureWebhook(Request $request, $id, $lang)
    {
        $payload = $request->all();
        // Log the payload for debugging
        Log::info('Tabby Webhook:', $payload);
        $tabby_payment = TabbyPayment::where('reference_id', $id)->first();
        $tabby_payment->update([
            'payment_id' => $request->payment_id,
        ]);
        $tabby = new TabbyService();
        $retrievePayment = $tabby->retrieveTabbyPayment($request->payment_id);
        if ($retrievePayment['status'] == "AUTHORIZED") {
            $tabby_payment->update([
                'status' => $retrievePayment['status'],
            ]);
            $payment = $tabby->capturePaymentRequest($request->payment_id, $id, $tabby_payment->amount);
            $tabby_payment->update([
                'status' => $payment['status'],
            ]);
        } else {
            $payment = false;
            $tabby_payment->update([
                'status' => $retrievePayment['status'],
            ]);
        }
        // Return a response to acknowledge receipt of the webhook
        if ($lang == 'en') {
            $message = 'Sorry, Tabby is unable to approve this purchase. Please use an alternative payment method for your order';
        } else {
            $message = 'نأسف، تابي غير قادرة على الموافقة على هذه العملية. الرجاء استخدام طريقة دفع أخرى.';
        }
        return response()->json(['status' => $message]);
    }

    public function createPayment(Request $request)
    {
        $client = new Client();
        $response = $client->request('POST', 'https://api-sandbox.tamara.co/checkout', [
            'body' => json_encode([
                "total_amount" => [
                    "amount" => 300.00,
                    "currency" => "SAR"
                ],
                "shipping_amount" => [
                    "amount" => 0.00,
                    "currency" => "SAR"
                ],
                "tax_amount" => [
                    "amount" => 0.00,
                    "currency" => "SAR"
                ],
                "order_reference_id" => "1231234123-abda-fdfe--afd31241",
                "items" => [
                    [
                        "name" => "Lego City 8601",
                        "type" => "Digital",
                        "reference_id" => "123",
                        "sku" => "SA-12436",
                        "quantity" => 1,
                        "total_amount" => [
                            "amount" => 100.00,
                            "currency" => "SAR"
                        ]
                    ]
                ],
                "consumer" => [
                    "email" => "customer@email.com",
                    "first_name" => "Mona",
                    "last_name" => "Lisa",
                    "phone_number" => "566027755"
                ],
                "country_code" => "SA",
                "description" => "lorem ipsum dolor",
                "merchant_url" => [
                    "cancel" => "https://bms-api.naqiwash.com/api/tabby/cancel",
                    "failure" => "https://bms-api.naqiwash.com/api/tabby/fail",
                    "success" =>  "http://ec2-3-76-56-175.eu-central-1.compute.amazonaws.com:8000/api/tabby/success",
                    "notification" => "http://ec2-3-76-56-175.eu-central-1.compute.amazonaws.com:8000/api/payment/notification"
                ],
                "payment_type" => "PAY_BY_INSTALMENTS",
                "instalments" => 4,
                "billing_address" => [
                    "city" => "Riyadh",
                    "country_code" => "SA",
                    "first_name" => "Mona",
                    "last_name" => "Lisa",
                    "line1" => "3764 Al Urubah Rd",
                    "line2" => "string",
                    "phone_number" => "532298658",
                    "region" => "As Sulimaniyah"
                ],
                "shipping_address" => [
                    "city" => "Riyadh",
                    "country_code" => "SA",
                    "first_name" => "Mona",
                    "last_name" => "Lisa",
                    "line1" => "3764 Al Urubah Rd",
                    "line2" => "string",
                    "phone_number" => "532298658",
                    "region" => "As Sulimaniyah"
                ],
                "platform" => "platform name here",
                "is_mobile" => true,
                "locale" => "ar_SA"
            ]),
            'headers' => [
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'Authorization' => 'Bearer' . $this->apiKey,
            ]
        ]);
        $paymentData = json_decode($response->getBody(), true);
        $paymentUrl = $paymentData['checkout_url']; // Adjust according to Tamara's response structure

        return response()->json(['payment_url' => $paymentData]);
    }




    public function retrieveTabbySession($id)
    {
        $tabby = new TabbyService();
        $payment = $tabby->retrieveTabbySession($id);
        // Return a response to acknowledge receipt of the webhook
        return  $payment;
    }
    //////////////////  tamara///////////////


    // Pre Checkout Payment

    public function pre_checkout($phone, $amount)
    {
        return  $response = $this->tamaraService->pre_checkout($phone, $amount);
    }
    // pay with tamara and create temp appointment or subscription
    public function  createOrderFromApp(Request $request)
    {
        $request->validate([
            'item_type' =>  'required|in:package,service',
            'item_id' => 'required|numeric',
            'total_amount' => 'required|integer',

        ]);
        $user = auth('api')->user();
        // $check = $this->pre_checkout($user->phone, $request->total_amount);
        // if ($check != "true") {
        //     return  $check;
        // }

        $address  = Address::find($request->address_id);

        $order_reference_id = uniqid('nq') . '-' . Str::random(4) . '-' . Str::random(4) . '-' . Str::random(4) . '-' . Str::random(8);
        $order_number = 'n' . mt_rand(100000, 999999);

        // check  item type
        if ($request->item_type == 'service') {
            $request->validate([
                'requested_date' => 'required|string',
                'start_time' => 'required|string',
                'payment_type' => 'required|string',
                'end_time' => 'required|string',
                'address_id' => 'required|integer',
                'tech_id' => 'required|integer',
                'vehicle_id' => 'required|integer',
                'car_count' => 'required|integer',
                'coupon_id' => 'nullable|integer',
            ]);
            $item = Service::find($request->item_id);
            if (!$item) {
                return response()->json(['error' => 'Service not found'], 404);
            }
            $appointment_duration = (float) $item->duration * 1;
            $total_price = $item->price * 1;
            $user = auth('api')->user();

            // coupon in payment
            $orderData = [
                'total_amount' => [
                    'amount' => $request->total_amount,  // Example amount
                    'currency' => 'SAR'
                ],
                'shipping_amount' => [
                    'amount' => 0,
                    'currency' => 'SAR'
                ],
                'tax_amount' => [
                    'amount' => 0,
                    'currency' => 'SAR'
                ],
                'order_reference_id' => $order_reference_id,
                'order_number' => $order_number,
                'discount' => [
                    'name' => 'coupon',
                    'amount' => [
                        'amount' => 0,
                        'currency' => 'SAR'
                    ]
                ],
                'items' => [
                    [
                        'name' => $item->name_en,
                        'type' => $request->item_type,
                        'reference_id' => $item->id,
                        'sku' => $item->dy_item_number,
                        'quantity' => 1,
                        'discount_amount' => [
                            'amount' => 0,
                            'currency' => 'SAR'
                        ],
                        'tax_amount' => [
                            'amount' => 0,
                            'currency' => 'SAR'
                        ],
                        'unit_price' => [
                            'amount' => $request->total_amount,
                            'currency' => 'SAR'
                        ],
                        'total_amount' => [
                            'amount' => $request->total_amount,
                            'currency' => 'SAR'
                        ]
                    ]
                ],
                'consumer' => [
                    "email" => "user@naqiwash.com",  // Set email dynamically from the request
                    'first_name' => $user->username,
                    'last_name' => $user->username,
                    'phone_number' => $user->phone
                ],
                'country_code' => 'SA',
                'description' => $item->description_en,
                'merchant_url' => [
                    'cancel' => "http://ec2-3-76-56-175.eu-central-1.compute.amazonaws.com:8000/api/tabby/cancel",
                    'failure' => "http://ec2-3-76-56-175.eu-central-1.compute.amazonaws.com:8000/api/tabby/failure",
                    'success' => 'http://ec2-3-76-56-175.eu-central-1.compute.amazonaws.com:8000/api/tabby/success',
                    'notification' => 'http://ec2-3-76-56-175.eu-central-1.compute.amazonaws.com:8000/api/payment/notification'
                ],
                'payment_type' => 'PAY_BY_INSTALMENTS',
                'instalments' => 4,
                'billing_address' => [
                    'city' => $address->city->name,
                    'country_code' => 'SA',
                    'first_name' => $user->username,
                    'last_name' => $user->username,
                    'line1' => $address->district->name,
                    'line2' => $address->district->name,
                    'phone_number' => $user->phone,
                    'region' => $address->city->name
                ],
                'shipping_address' => [
                    'city' => $address->city->name,
                    'country_code' => 'SA',
                    'first_name' => $user->username,
                    'last_name' => $user->username,
                    'line1' => $address->district->name,
                    'line2' => $address->district->name,
                    'phone_number' => $user->phone,
                    'region' => $address->city->name
                ],
                'platform' => 'Naqi Wash',
                "is_mobile" => true,
                'locale' => 'EN_SA',

            ];
            // get order_id or payment info for appointment
            $response = $this->tamaraService->createOrder($orderData);

            if (isset($response['order_id'])) {
                $tabby = TamaraPayment::create([
                    'order_id' => $response['order_id'],
                    'checkout_id' => $response['checkout_id'],
                    'item_id' => $request->item_id,
                    'item_type' => $request->item_type,
                    'amount' => $total_price,
                ]);

                // creat primary appointment
                $appointment = new Appointment();
                $appointment->appointment_date = $request->requested_date;
                $appointment->payment_id = $request->payment_id;
                $appointment->payment_type = $request->payment_type;
                $appointment->appointment_duration = $appointment_duration;
                $appointment->start_time = $request->start_time;
                $appointment->end_time = $request->end_time;
                $appointment->tax = NULL;
                $appointment->subtotal = $total_price;
                $appointment->car_count = 1;
                $appointment->tech_id = $request->tech_id;
                $appointment->city_id = $address->city_id;
                $appointment->vehicle_id = $request->vehicle_id;
                $appointment->payment_status = 'pending';
                $appointment->payment_info = $response['order_id'];
                $appointment->source = 'app';
                // car_brand
                if ($request->has('car_brand')) {
                    $appointment->car_brand_id = $request->car_brand;
                }
                // car_model
                if ($request->has('car_model')) {
                    $appointment->car_model_id = $request->car_model;
                }

                $appointment->district_id = $address->district_id;
                $appointment->address_id = $address->id;
                $appointment->customer_id = $user->id;


                // coupon
                if ($request->has('coupon_id')) {
                    $appointment->coupon_id = $request->coupon_id;

                    $coupon = Coupon::find($request->coupon_id);
                    if ($coupon->discount_type === 'percentage') {
                        $discount = ($coupon->discount_amount / 100) * $total_price;
                        $appointment->discount = $discount;
                        $total_price -= $discount;
                    } elseif ($coupon->discount_type === 'fixed_amount') {
                        $appointment->discount = $coupon->discount_amount;
                        $total_price -= $coupon->discount_amount;
                    }
                    $total_price = max($total_price, 0);
                } else {
                    $appointment->discount = 0;
                }
                $appointment->total_price = $total_price;
                $appointment->save();

                // Appointment Logs
                $appointment_log = new AppointmentLog();
                $appointment_log->appointment_id = $appointment->id;
                $appointment_log->action_by = $user->id;
                $appointment_log->action_type = "create";
                $appointment_log->action = "create appointment";
                $appointment_log->description = "create new appointment with service from  application";
                $appointment_log->action_date = now();
                $appointment_log->save();
                // end log

                // $message = 'تم حجز موعد جديد بتاريخ' . $request->requested_date;
                // $title = 'موعد جديد';
                // $userId = $request->user()->id;

                // $fcmToken = $request->user()->fcm_token;
                // $message1 = CloudMessage::fromArray([
                //     'token' => $fcmToken,
                //     'notification' => [
                //         'title' => $title,
                //         'body' => $message,
                //         "sound" => "default",
                //         "channel_id" => "basic_channel",
                //         "priority" => "high",
                //         "apns-priority" => "10"
                //     ],
                // ]);

                // if ($fcmToken != null) {
                //     $this->notification->send($message1);
                //     Notification::create([
                //         'notifiable_id' => $userId,
                //         'title' => $title,
                //         'body' => $message,
                //     ]);
                // }

                // notify tech for appointment
                // $tech = User::where("id", $appointment->tech_id)->first();
                // $recipientToken = $tech->fcm_token;
                // $body = 'تم حجز موعد جديد بتاريخ' . $appointment->appointment_date;
                // $title = 'موعد جديد';
                // if ($recipientToken != null) {
                //     $result = $this->notificationService->sendNotification($title, $body, $recipientToken, $tech->id, $appointment->id);
                // }


                if ($request->has('coupon_id')) {
                    $couponUser = new CouponUser();
                    $couponUser->coupon_id = $request->coupon_id;
                    $couponUser->user_id = $user->id;
                    $couponUser->is_used = true;
                    $couponUser->save();

                    $user = $request->user();
                    // coupon Logs
                    $couponLog = new CouponLog();
                    $couponLog->coupon_id = $coupon->id;
                    $couponLog->action = "use coupon";
                    $couponLog->action_by = $user->id;
                    $couponLog->action_type = "use";
                    $couponLog->description = "coupon used by : " . $user->username;
                    $couponLog->action_date = now();
                    $couponLog->save();
                    // end log
                }

                $sre = new AppointmentService();
                $sre->appointment_id = $appointment->id;
                $sre->service_id = $item->id;
                $sre->service_price = $item->price;
                $sre->save();



                $record = TechDailyRecord::where('date', $request->requested_date)->where('user_id', $request->tech_id)->first();
                if ($record != null) {
                    $record->record = $record->record + 1;
                    $record->update();
                } else {
                    $record = new TechDailyRecord();
                    $record->user_id = $request->tech_id;
                    $record->date = $request->requested_date;
                    $record->record = 1;
                    $record->save();
                }

                return response()->json([
                    'status' => 'created',
                    'order_id' => $response['order_id'],
                    'checkout_url' => $response['checkout_url'],
                    'appointment_id' => $appointment->id,
                ], 201);
            }
        } else {
            $request->validate([
                'payment_type' => 'required|string',
            ]);
            $item = Package::find($request->item_id);
            if (!$item) {
                return response()->json(['error' => 'Package not found'], 404);
            }

            $customer_id = $request->user()->id;
            $user = $request->user();
            $expiresAt = Carbon::now()->addDays($item->days_count);

            $orderData = [
                'total_amount' => [
                    'amount' => $request->total_amount,  // Example amount
                    'currency' => 'SAR'
                ],
                'shipping_amount' => [
                    'amount' => 0,
                    'currency' => 'SAR'
                ],
                'tax_amount' => [
                    'amount' => 0,
                    'currency' => 'SAR'
                ],
                'order_reference_id' => $order_reference_id,
                'order_number' => $order_number,
                'discount' => [
                    'name' => 'Voucher A',
                    'amount' => [
                        'amount' => 0,
                        'currency' => 'SAR'
                    ]
                ],
                'items' => [
                    [
                        'name' => $item->name_en,
                        'type' => $request->item_type,
                        'reference_id' => $item->id,
                        'sku' => $item->dy_item_number,
                        'quantity' => 1,
                        'discount_amount' => [
                            'amount' => 0,
                            'currency' => 'SAR'
                        ],
                        'tax_amount' => [
                            'amount' => 0,
                            'currency' => 'SAR'
                        ],
                        'unit_price' => [
                            'amount' => $request->total_amount,
                            'currency' => 'SAR'
                        ],
                        'total_amount' => [
                            'amount' => $request->total_amount,
                            'currency' => 'SAR'
                        ]
                    ]
                ],
                'consumer' => [
                    "email" => "user@naqiwash.com",  // Set email dynamically from the request
                    'first_name' => $user->username,
                    'last_name' => $user->username,
                    'phone_number' => $user->phone
                ],
                'country_code' => 'SA',
                'description' => $item->name_ar,
                'merchant_url' => [
                    'cancel' => "http://ec2-3-76-56-175.eu-central-1.compute.amazonaws.com:8000/api/tabby/cancel",
                    'failure' => "http://ec2-3-76-56-175.eu-central-1.compute.amazonaws.com:8000/api/tabby/failure",
                    'success' => 'http://ec2-3-76-56-175.eu-central-1.compute.amazonaws.com:8000/api/tabby/success',
                    'notification' => 'http://ec2-3-76-56-175.eu-central-1.compute.amazonaws.com:8000/api/payment/notification'
                ],
                'payment_type' => 'PAY_BY_INSTALMENTS',
                'instalments' => 4,
                'billing_address' => [
                    'city' => $address->city->name,
                    'country_code' => 'SA',
                    'first_name' => $user->username,
                    'last_name' => $user->username,
                    'line1' => $address->district->name,
                    'line2' => $address->district->name,
                    'phone_number' => $user->phone,
                    'region' => $address->city->name
                ],
                'shipping_address' => [
                    'city' => $address->city->name,
                    'country_code' => 'SA',
                    'first_name' => $user->username,
                    'last_name' => $user->username,
                    'line1' => $address->district->name,
                    'line2' => $address->district->name,
                    'phone_number' => $user->phone,
                    'region' => $address->city->name
                ],
                'platform' => 'Naqi Wash',
                "is_mobile" => true,
                'locale' => 'EN_SA',

            ];
            // get order_id or payment info for appointment
            $response = $this->tamaraService->createOrder($orderData);

            if (isset($response['order_id'])) {
                $tamara = TamaraPayment::create([
                    'order_id' => $response['order_id'],
                    'checkout_id' => $response['checkout_id'],
                    'item_id' => $request->item_id,
                    'item_type' => $request->item_type,
                    'amount' => $request->total_amount,
                ]);


                $subscription = new Subscription();
                $subscription->customer_id = $customer_id;
                $subscription->package_id = $item->id;
                $subscription->price = $item->price;
                $subscription->expires_at = $expiresAt;
                $subscription->payment_type = $request->payment_type;
                $subscription->remaining_count = $item->count;
                $subscription->payment_status = 'waiting';
                $subscription->payment_info = $response['order_id'];
                $subscription->save();
                return response()->json([
                    'status' => 'created',
                    'order_id' => $response['order_id'],
                    'checkout_url' => $response['checkout_url'],
                    'subscription_id' => $subscription->id,
                ], 201);
            }
        }
    }

    public function authorizeOrder(Request $request)
    {
        $request->validate([
            'item_type' =>  'required|in:subscription,appointment',
            'item_id' => 'required|numeric',
            'order_id' => 'required',
        ]);

        $response = $this->tamaraService->authorizeOrder($request->order_id);
        $response = $this->captureOrder($request->order_id);
        if ($response['status'] == 'fully_captured') {
            if ($request->item_type == 'appointment') {
                $item = Appointment::find($request->item_id);
                // return $Appointment;
            } else {
                $item = Subscription::find($request->item_id);
                // return $Subscription;
            }
            $tamara = TamaraPayment::where('order_id', $response['order_id'])->first();
            $tamara->update(['status' => 'success']);
            $item->update(['payment_id' => $request->order_id, 'payment_status' => 'paid']);
            return response()->json([
                'status' => 'paid successfuly',
                'data' => $item,
            ], 201);
        }
    }


    public function  captureOrder($order_id)
    {

        $order = TamaraPayment::where('order_id', $order_id)->first();

        if ($order->item_type == 'service') {
            $item = Service::find($order->item_id);
            $name = $item->name_ar;
            // return $service;
        } else {
            $item = Package::find($order->item_id);
            $name = $item->name_ar;
            // return $package;
        }
        $orderData = [
            'order_id' => $order_id,  // Set order_id dynamically from the request
            'total_amount' => [
                'amount' => $order->amount, // Example amount
                'currency' => 'SAR'
            ],

            'items' => [
                [
                    'name' => $name,
                    'type' => $order->item_type,
                    'reference_id' => $item->id,
                    'sku' => $item->dy_item_number,
                    'quantity' => 1,
                    'discount_amount' => [
                        'amount' => 0,
                        'currency' => 'SAR'
                    ],
                    'tax_amount' => [
                        'amount' => 0,
                        'currency' => 'SAR'
                    ],
                    'unit_price' => [
                        'amount' =>  $order->amount,
                        'currency' => 'SAR'
                    ],
                    'total_amount' => [
                        'amount' => $order->amount,
                        'currency' => 'SAR'
                    ]
                ]
            ],
            'discount_amount' => [
                'amount' => 0,
                'currency' => 'SAR'
            ],
            'shipping_amount' => [
                'amount' => 0,
                'currency' => 'SAR'
            ],
            'tax_amount' => [
                'amount' => 0,
                'currency' => 'SAR'
            ]
        ];

        return   $response = $this->tamaraService->captureOrder($orderData);
    }

    public function handleNotification(Request $request)
    {
        // Handle payment notifications here
        $data = $request->all();
        Log::info(json_encode($data));
        // Process the notification
        // Update the order status in your database, etc.
    }

    public function checkMerchantStatus()
    {
        $client = new \GuzzleHttp\Client([
            'base_uri' => env('TAMARA_API_URL'),
            'headers' => [
                'Authorization' => 'Bearer ' . env('TAMARA_API_KEY'),
                'Content-Type' => 'application/json',
            ],
        ]);

        try {
            $response = $client->get('/merchants/me');
            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }


    public function non_invoice(Request $request)
    {

        $appointments = Appointment::whereNull('dy_sales_id')
            ->whereNull('dy_invoice_id')
            ->where('payment_type', '!=', 'pay_with_package')
            ->where('payment_status', 'paid')
            ->whereNotIn('status', [
                'cancelled_by_cs',
                'cancelled_by_tech',
                'unpaid_status',
                'cancelled_by_customer'
            ])
            ->limit(10)
            ->get();
        foreach ($appointments as $appointment) {
            $service = Service::find($appointment->services()->first()->service_id);
            $customer = User::find($appointment->customer_id);
            $payment_method =  $appointment->payment_type;

            if ($payment_method == 'tabby') {
                $payment_method = 'Tabby';
            }
            if ($payment_method == 'online_payment') {
                $payment_method = 'Moyassar';
            }
            if ($payment_method == 'cash') {
                $payment_method = 'Cash';
            }
            if ($payment_method == 'apply_pay') {
                $payment_method = 'Moyassar';
            }
            if ($payment_method == 'tamara') {
                $payment_method = 'Tamara';
            }

            $total = (int)$appointment->total / (int)$appointment->car_count;

            if ($appointment->dy_invoice_id == null) {
                $dy = new DyService();
                $response = $dy->createSalesOrder(
                    $appointment->appointment_num,
                    $customer->dy_id,
                    $service->dy_item_number,
                    (int)$appointment->car_count,
                    (int)$total,
                    (int)$appointment->discount ?? 0,
                    $payment_method
                );
                Log::info(['RESPONSE' =>  $response]);
                if ($response['SalesOrderList'][0]['ResponseStatus'] == true) {
                    $app = Appointment::find($appointment->id);
                    $app->update(['dy_sales_id' => $response['SalesOrderList'][0]['D365SalesId']]);
                    $app->update(['dy_invoice_id' => $response['SalesOrderList'][0]['D365InvoiceId']]);
                    $appointment['dy_sales_id'] =  $response['SalesOrderList'][0]['D365SalesId'];
                    $appointment['dy_invoice_id'] = $response['SalesOrderList'][0]['D365InvoiceId'];
                    $data = $dy->CreateCustomerPayment($response['SalesOrderList'][0]['D365InvoiceId'], $payment_method);
                    Log::info(['DY_PAYMENT_DATA' =>  $data]);
                }
            }
        }

        // $subscriptions = Subscription::whereNull('dy_sales_id')
        //     ->whereNull('dy_invoice_id')
        //     ->whereIn('payment_type', ['online_payment', 'tabby', 'tamara'])
        //     ->get();
        return $this->setCode(code: 200)->setData(['appointments' => $appointments])->setMessage('success')->send();
    }

    public function full_summary(Request $request)
    {
        // Get total appointments by payment type
        $appointments = Appointment::select('payment_type', DB::raw('count(*) as total'))
            ->groupBy('payment_type')
            ->get()
            ->keyBy('payment_type');



        // Get total subscriptions by payment type
        $subscriptions = Subscription::select('payment_type', DB::raw('count(*) as total'))
            ->groupBy('payment_type')
            ->get()
            ->keyBy('payment_type');

        // Initialize the result array
        $combined = [];

        // Loop through appointments and sum totals for each payment type
        foreach ($appointments as $payment_type => $appointment) {
            $combined[$payment_type] = $appointment->total;
        }

        // Loop through subscriptions and sum totals for each payment type
        foreach ($subscriptions as $payment_type => $subscription) {
            if (isset($combined[$payment_type])) {
                // Add to existing payment type total
                $combined[$payment_type] += $subscription->total;
            } else {
                // Create new payment type entry if not already present
                $combined[$payment_type] = $subscription->total;
            }
        }

        // Get the grand total (sum of all payment types)
        $grandTotal = array_sum($combined);

        // Return the results as a JSON response
        return response()->json([
            'total' => $grandTotal,
            'by_payment_type' => $combined
        ]);
    }
    public function summary_byDate(Request $request)
    {
        // Define date ranges
        $today = Carbon::today();
        $startOfWeek = Carbon::now()->startOfWeek();
        $startOfMonth = Carbon::now()->startOfMonth();

        // Fetch totals for each range (today, this week, this month, and in total)
        $report = [
            'today' => $this->getReportByDateRange($today, Carbon::now()),
            'current_week' => $this->getReportByDateRange($startOfWeek, Carbon::now()),
            'current_month' => $this->getReportByDateRange($startOfMonth, Carbon::now()),
            'total' => $this->getReportByDateRange(null, null) // No date range for total
        ];

        return response()->json($report);
    }


    private function getReportByDateRange($startDate, $endDate)
    {
        // Build the appointment query
        $appointmentQuery = Appointment::select('payment_type', DB::raw('count(*) as total'))
            ->groupBy('payment_type');

        // Build the subscription query
        $subscriptionQuery = Subscription::select('payment_type', DB::raw('count(*) as total'))
            ->groupBy('payment_type');

        // Apply date filters if provided
        if ($startDate && $endDate) {
            $appointmentQuery->whereBetween('created_at', [$startDate, $endDate]);
            $subscriptionQuery->whereBetween('created_at', [$startDate, $endDate]);
        }

        // Get results
        $appointments = $appointmentQuery->get()->keyBy('payment_type');
        $subscriptions = $subscriptionQuery->get()->keyBy('payment_type');

        // Initialize the result array
        $combined = [];

        // Loop through appointments and sum totals for each payment type
        foreach ($appointments as $payment_type => $appointment) {
            $combined[$payment_type] = $appointment->total;
        }

        // Loop through subscriptions and sum totals for each payment type
        foreach ($subscriptions as $payment_type => $subscription) {
            if (isset($combined[$payment_type])) {
                // Add to existing payment type total
                $combined[$payment_type] += $subscription->total;
            } else {
                // Create new payment type entry if not already present
                $combined[$payment_type] = $subscription->total;
            }
        }

        // Get the grand total (sum of all payment types)
        $grandTotal = array_sum($combined);

        // Return the results
        return [
            'total' => $grandTotal,
            'by_payment_type' => $combined
        ];
    }
}
