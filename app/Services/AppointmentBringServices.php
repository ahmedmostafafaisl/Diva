<?php

namespace App\Services;

use App\Models\Service;

class AppointmentBringServices
{
    public function get_requested_services($servicesIds)
    {
       $services = Service::whereIn('id', $servicesIds)->get();

       if (count($services) > 0) {
            $total_time = 0;
            foreach ($services as $service) {
                $total_time = $total_time + (int)$service->service_time;
            }
            $data = ['services' => $services, 'total_time' => $total_time];
            return $data;
       }
       $data = [];
       return $data;
    }
}
