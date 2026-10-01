<?php

namespace App\Services;

use App\Models\SectorSchedule;
use Carbon\Carbon;

class AppointmentCheckSectorSchedule
{
    public function check_sector_schedule_service($sector_id, $service_id, $required_date)
    {
        $required_date = Carbon::createFromFormat('Y-m-d', $required_date);
        $sector_schedule = SectorSchedule::where([
            ['sector_id', '=', $sector_id],
            ['start_date', '<=', $required_date],
            ['end_date', '>=', $required_date],
        ])->with('services')->first();

        if (!$sector_schedule) {
            return false;
        }

        $serviceIds = $sector_schedule->services->pluck('id')->all();

        return in_array($service_id, $serviceIds);
    }
}
