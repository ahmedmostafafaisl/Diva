<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ContactRequestOrder;

class WhatsappService
{
    public function clientNoAnswer3($phone)
    {
        $client = Client::where('phone', $phone)->orWhere('second_phone', $phone)->first();
        // $phone = '966' . $phone;
        $phone = '966561037476';

        // if ($client) {
        //     $curl = curl_init();
        //     curl_setopt_array($curl, array(
        //         CURLOPT_URL => 'https://graph.facebook.com/v14.0/109567455104882/messages',
        //         CURLOPT_RETURNTRANSFER => true,
        //         CURLOPT_ENCODING => '',
        //         CURLOPT_MAXREDIRS => 10,
        //         CURLOPT_TIMEOUT => 0,
        //         CURLOPT_FOLLOWLOCATION => true,
        //         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        //         CURLOPT_CUSTOMREQUEST => 'POST',
        //         CURLOPT_POSTFIELDS =>'{
        //         "messaging_product" : "whatsapp",
        //         "to": '. $phone .',
        //         "type": "template",
        //         "template": {
        //                 "name": "client_no_answer3",
        //                 "language": {
        //                     "code": "ar"
        //                 },
        //                 "components": [
        //                     {
        //                     "type": "body",
        //                     "parameters": [
        //                             {
        //                                 "type": "text",
        //                                 "text": '. $client->name ? $client->name : 'عميلنا العزيز' .',
        //                             }
        //                     ]
        //                     }
        //                 ]
        //         }
        //     }',
        //         CURLOPT_HTTPHEADER => array(
        //             'Authorization: Bearer '.env('WHATSAPP_API_TOKEN'),
        //             'Content-Type: application/json'
        //         ),
        //     ));

        //     $response = curl_exec($curl);
        //     return $response;
        // } else {
        //     $curl = curl_init();
        //     curl_setopt_array($curl, array(
        //         CURLOPT_URL => 'https://graph.facebook.com/v14.0/109567455104882/messages',
        //         CURLOPT_RETURNTRANSFER => true,
        //         CURLOPT_ENCODING => '',
        //         CURLOPT_MAXREDIRS => 10,
        //         CURLOPT_TIMEOUT => 0,
        //         CURLOPT_FOLLOWLOCATION => true,
        //         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        //         CURLOPT_CUSTOMREQUEST => 'POST',
        //         CURLOPT_POSTFIELDS =>'{
        //         "messaging_product" : "whatsapp",
        //         "to": ' . $phone .',
        //         "type": "template",
        //         "template": {
        //                 "name": "client_no_answer3",
        //                 "language": {
        //                     "code": "ar"
        //                 },
        //                 "components": [
        //                     {
        //                     "type": "body",
        //                     "parameters": [
        //                             {
        //                                 "type": "text",
        //                                 "text": "عميلنا العزيز" ,
        //                             }
        //                     ]
        //                     }
        //                 ]
        //         }
        //     }',
        //         CURLOPT_HTTPHEADER => array(
        //             'Authorization: Bearer '.env('WHATSAPP_API_TOKEN'),
        //             'Content-Type: application/json'
        //         ),
        //     ));

        //     $response = curl_exec($curl);
        //     return $response;
        // }
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://graph.facebook.com/v14.0/109567455104882/messages',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
                "messaging_product" : "whatsapp",
            "to": '.$phone.',
            "type": "template",
            "template": {
                    "name": "client_no_answer3",
                    "language": {
                        "code": "ar"
                    },
                    "components": [
                        {
                        "type": "body",
                        "parameters": [
                                {
                                    "type": "text",
                                    "text": "عميلنا العزيز" ,
                                }
                        ]
                        }
                    ]
            }
        }',
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer '.env('WHATSAPP_API_TOKEN'),
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($curl);

        return $response;
    }

    public function preAppointmentActionV3($order_id)
    {
        $order = ContactRequestOrder::where('id', $order_id)->with('client', 'products')->first();
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://graph.facebook.com/v14.0/109567455104882/messages',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
                    "messaging_product" : "whatsapp",
                "to": '.$order->client->phone.',
                "type": "template",
                "template": {
                        "name": "client_no_answer3",
                        "language": {
                            "code": "ar"
                        },
                        "components": [
                            {
                            "type": "body",
                            "parameters": [
                                    {
                                        "type": "text",
                                        "text": '.$order->client->name ? $order->client->name : 'عميلنا العزيز'.',
                                    },
                            ]
                            }
                        ]
                }
            }',
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer '.env('WHATSAPP_API_TOKEN'),
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($curl);

        return $response;
    }

    public function clientNoAnswer1($phone)
    {
        $phone = '966561037476';
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://graph.facebook.com/v14.0/109567455104882/messages',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
            "messaging_product" : "whatsapp",
            "to": '.$phone.',
            "type": "template",
            "template": {
                    "name": "client_not_answer_step_1 ",
                    "language": {
                        "code": "ar"
                    },
                    "components": [
                        {
                        "type": "body",
                        }
                    ]
                }
            }',
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer '.env('WHATSAPP_API_TOKEN'),
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($curl);

        return $response;
    }
}
