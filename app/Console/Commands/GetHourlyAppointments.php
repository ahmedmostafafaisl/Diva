<?php

namespace App\Console\Commands;

use Carbon\Carbon;

use App\Models\User;
use App\Models\Package;
use App\Models\Service;
use App\Models\Appointment;
use App\Services\DyService;
use App\Models\Notification;
use App\Models\Subscription;
use App\Models\TabbyPayment;
use App\Models\TamaraPayment;
use App\Services\TabbyService;
use App\Services\TamaraService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Laravel\Firebase\Facades\Firebase;

class GetHourlyAppointments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointments:hourly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('Capture Corn Started');
        $now = Carbon::now();
        $oneHourAgo = $now->subHour();


        $appointments = Appointment::whereIn('payment_type', ['tamara', 'tabby'])
            ->where('payment_info', '!=', null)->where('payment_status', 'pending')->where('status', 'ongoing')->whereBetween('created_at', [$oneHourAgo, Carbon::now()])->get();

        $subscriptions = Subscription::whereIn('payment_type', ['tamara', 'tabby'])
            ->where('payment_info', '!=', null)->where('payment_status', 'waiting')
            ->whereBetween('created_at', [$oneHourAgo, Carbon::now()])->get();

        Log::info('subscriptions', $subscriptions->toArray());
        Log::info(message: 'appointments', context: $appointments->toArray());
        foreach ($subscriptions as $sub) {
            if ($sub->payment_type == 'tabby') {

                $user = User::find($sub->customer_id);
                $package = Service::find($sub->package_id);

                $tabby = new TabbyService();
                $sessionPayment = $tabby->retrieveTabbySession($sub->payment_info);
                $payment_id = $sessionPayment['payment']['id'];

                $tabby_payment = TabbyPayment::where('session_id', $sub->payment_info)->first();
                $tabby_payment->update([
                    'payment_id' => $payment_id,
                ]);

                $tabby = new TabbyService();
                $retrievePayment = $tabby->retrieveTabbyPayment($payment_id);

                if ($retrievePayment['status'] == "AUTHORIZED") {
                    $tabby_payment->update([
                        'status' => $retrievePayment['status'],
                    ]);
                    $payment = $tabby->capturePaymentRequest($payment_id, $tabby_payment->reference_id, $tabby_payment->amount);
                    $tabby_payment->update([
                        'status' => $payment['status'],
                    ]);
                    $sub->update([
                        'payment_status' => 'paid',
                        'payment_id' => $payment_id,
                    ]);

                    $payment_method =  $sub->payment_type;

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
                    if ($sub->dy_invoice_id == null) {
                        $dy = new DyService();
                        $response = $dy->createSalesOrder($sub->subscription_num, $user->dy_id, $package->dy_item_number, 1, $sub->price, 0, $payment_method);

                        if ($response['SalesOrderList'][0]['ResponseStatus'] == true) {
                            $sub = Subscription::find($sub->id);
                            $sub->update(['dy_sales_id' => $response['SalesOrderList'][0]['D365SalesId']]);
                            $sub->update(['dy_invoice_id' => $response['SalesOrderList'][0]['D365InvoiceId']]);
                            $sub['dy_sales_id'] =  $response['SalesOrderList'][0]['D365SalesId'];
                            $sub['dy_invoice_id'] = $response['SalesOrderList'][0]['D365InvoiceId'];
                            $data = $dy->CreateCustomerPayment($response['SalesOrderList'][0]['D365InvoiceId'], $payment_method);
                            Log::info('DY_PAYMENT_DATA',  $data);
                        }
                    }
                    $message = 'تم الأشتراك فى باقة' . " " . $package->name_ar . " " . 'بنجاح'  . 'و سيكون تاريخ الأنتهاء فى :' . " " . $sub->expires_at . " " . 'أو عند الأستخدام ' . " " . $package->count . " " . 'مرات';
                    $title = 'اشتراك جديد';
                    $userId = $user->id;

                    // $FcmToken = $user->fcm_token;
                    // $message1 = CloudMessage::withTarget('token', $FcmToken)
                    //     ->withNotification([
                    //         'title' => $title,
                    //         'body' => $message,
                    //         "sound" => "default",  // Sound file name
                    //         "channel_id" => "basic_channel",
                    //         "priority" => "high",
                    //         "apns-priority" => "10"
                    //     ]);

                    // Firebase::messaging()->send($message1);
                    // $notification = Notification::create([
                    //     'notifiable_id' => $user->id,
                    //     'title' => $title,
                    //     'body' => $message,
                    // ]);
                } else {
                    $payment = false;
                    $tabby_payment->update([
                        'status' => $retrievePayment['status'],
                    ]);
                }
            } elseif ($sub->payment_type == 'tamara') {
                $tamara = new TamaraService();
                // auth
                $response = $tamara->authorizeOrder($sub->payment_info);
                if (isset($response['status'])) {
                    if ($response['status'] == 'authorised') {
                        $order = TamaraPayment::where('order_id', $sub->payment_info)->first();

                        if ($order->item_type == 'service') {
                            $item = Service::find($order->item_id);
                            // return $service;
                        } else {
                            $item = Package::find($order->item_id);
                            // return $package;
                        }
                        $orderData = [
                            'order_id' => $sub->payment_info,  // Set order_id dynamically from the request
                            'total_amount' => [
                                'amount' => $order->amount, // Example amount
                                'currency' => 'SAR'
                            ],

                            'items' => [
                                [
                                    'name' => $item->name_ar,
                                    'type' => $order->item_type,
                                    'reference_id' => $item->id,
                                    'sku' => $item->dy_item_number,
                                    'quantity' => 1,
                                    'discount_amount' => [
                                        'amount' => 0,
                                        'currency' => 'SAR'
                                    ],
                                    'tax_amount' => [
                                        'amount' => 10,
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
                        // capture
                        $capture = $tamara->captureOrder($orderData);
                        if ($capture['status'] == 'fully_captured') {
                            $sub->update([
                                'payment_status' => 'paid',
                                'payment_id' => $sub->payment_info,
                            ]);
                        }
                    }
                }
            }
        }

        foreach ($appointments as $appointment) {
            if ($appointment->payment_type == 'tabby') {
                $user = User::find($appointment->customer_id);
                $service = Service::find($appointment->services[0]->service_id);
                $tabby = new TabbyService();
                $sessionPayment = $tabby->retrieveTabbySession($appointment->payment_info);
                $payment_id = $sessionPayment['payment']['id'];

                $tabby_payment = TabbyPayment::where('session_id', $appointment->payment_info)->first();
                $tabby_payment->update([
                    'payment_id' => $payment_id,
                ]);

                $tabby = new TabbyService();
                $retrievePayment = $tabby->retrieveTabbyPayment($payment_id);

                if ($retrievePayment['status'] == "AUTHORIZED") {
                    $tabby_payment->update([
                        'status' => $retrievePayment['status'],
                    ]);
                    $payment = $tabby->capturePaymentRequest($payment_id, $tabby_payment->reference_id, $tabby_payment->amount);
                    $tabby_payment->update([
                        'status' => $payment['status'],
                    ]);
                    $appointment->update([
                        'payment_status' => 'paid',
                        'payment_id' => $payment_id,
                    ]);
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
                    if ($appointment->dy_invoice_id == null) {
                        $dy = new DyService();
                        $response = $dy->createSalesOrder($appointment->appointment_num, $user->dy_id, $service->dy_item_number, (int)$appointment->car_count, (int)$appointment->subtotal, (int)$appointment->discount ?? 0, $payment_method);

                        if ($response['SalesOrderList'][0]['ResponseStatus'] == true) {
                            $app = Appointment::find($appointment->id);
                            $app->update(['dy_sales_id' => $response['SalesOrderList'][0]['D365SalesId']]);
                            $app->update(['dy_invoice_id' => $response['SalesOrderList'][0]['D365InvoiceId']]);
                            $appointment['dy_sales_id'] =  $response['SalesOrderList'][0]['D365SalesId'];
                            $appointment['dy_invoice_id'] = $response['SalesOrderList'][0]['D365InvoiceId'];
                            $data = $dy->CreateCustomerPayment($response['SalesOrderList'][0]['D365InvoiceId'], $payment_method);
                            Log::info('DY_PAYMENT_DATA',  $data);
                        }
                    }

                    // $message = 'تم حجز موعد جديد بتاريخ' . $appointment->requested_date;
                    // $title = 'موعد جديد';
                    // $fcmToken = $user->fcm_token;
                    // $message1 = CloudMessage::withTarget('token', $fcmToken)
                    //     ->withNotification(
                    //         \Kreait\Firebase\Messaging\Notification::create()
                    //             ->withTitle($title)
                    //             ->withBody($message)
                    //     );

                    // Firebase::messaging()->send($message1);
                    // $notification = Notification::create([
                    //     'notifiable_id' => $user->id,
                    //     'title' => $title,
                    //     'body' => $message,
                    // ]);
                } else {
                    $payment = false;
                    $tabby_payment->update([
                        'status' => $retrievePayment['status'],
                    ]);
                }
            } elseif ($appointment->payment_type == 'tamara') {
                $tamara = new TamaraService();
                // auth
                Log::info($appointment->payment_info);
                $response = $tamara->authorizeOrder($appointment->payment_info);
                Log::info($response);

                if (isset($response['status'])) {
                    if ($response['status'] == 'authorised') {
                        $order = TamaraPayment::where('order_id', $appointment->payment_info)->first();

                        if ($order->item_type == 'service') {
                            $item = Service::find($order->item_id);
                        } else {
                            $item = Package::find($order->item_id);
                        }
                        $orderData = [
                            'order_id' => $appointment->payment_info,  // Set order_id dynamically from the request
                            'total_amount' => [
                                'amount' => $order->amount, // Example amount
                                'currency' => 'SAR'
                            ],

                            'items' => [
                                [
                                    'name' => $item->name_ar,
                                    'type' => $order->item_type,
                                    'reference_id' => $item->id,
                                    'sku' => $item->dy_item_number,
                                    'quantity' => 1,
                                    'discount_amount' => [
                                        'amount' => 0,
                                        'currency' => 'SAR'
                                    ],
                                    'tax_amount' => [
                                        'amount' => 10,
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
                        // capture
                        $capture = $tamara->captureOrder($orderData);
                        if ($capture['status'] == 'fully_captured') {
                            $appointment->update([
                                'payment_status' => 'paid',
                                'payment_id' => $appointment->payment_info
                            ]);
                            $order->update(['status' => 'success']);
                        }
                    }
                }
                Log::info($response);
            }
        }



        // Indicate success
        $this->info('Retrieved ' . $appointments->count() . ' appointments created within the last hour.');
        $this->info('Retrieved ' . $subscriptions->count() . ' appointments created within the last hour.');
    }
}
