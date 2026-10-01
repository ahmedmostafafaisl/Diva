<?php

namespace App\Services;

use App\Models\Client;

class AppointmentCheckClientExistence
{
    public function check_client_existence($id)
    {
        $client = Client::where('user_id', $id)->first();
        if (!$client) {
            return false;
        }
        return $client;
    }
}
