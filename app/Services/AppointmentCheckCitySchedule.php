<?php

namespace App\Services;

use App\Models\CitySchedule;

class AppointmentCheckCitySchedule
{
    public function check_city_schedule_service($city_id, $service_id)
    {
        $city = CitySchedule::where('city_id', $city_id)->with('services')->first();

        $serviceIds = $city->services->pluck('id')->all();

        return in_array($service_id, $serviceIds);
    }
}
