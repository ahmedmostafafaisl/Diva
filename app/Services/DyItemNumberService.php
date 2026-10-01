<?php

namespace App\Services;

use Illuminate\Support\Str;
use App\Models\Package;
use App\Models\Service;

class DyItemNumberService
{
    public static function generateUniqueDyItemNumber()
    {
        do {
            $number = 'DY-' . Str::random(8); // Example: DY-ABC123XY
        } while (
            Package::where('dy_item_number', $number)->exists() ||
            Service::where('dy_item_number', $number)->exists()
        );

        return $number;
    }
}
