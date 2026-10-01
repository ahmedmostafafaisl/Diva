<?php

namespace App\Services;

use App\Models\Packages;

class AppointmentPackageCheck
{
    public function check_if_services_in_package($servicesIds, $productIds)
    {
        if (count($servicesIds) > 0 && count($productIds) < 0) {
            $packages = Packages::with('services')->get()->filter(function ($package) use ($servicesIds) {
                $matchedServices = $package->services->pluck('id')->intersect($servicesIds);
                return $matchedServices->count() === count($servicesIds);
            })->first();
            if($packages) {
                return $packages;
            } else {
                return [];
            }
        }

        if (count($servicesIds) < 0 && count($productIds) > 0) {
            $packages = Packages::with('products')->get()->filter(function ($package) use ($productIds) {
                $matchedProducts = $package->services->pluck('id')->intersect($productIds);
                return $matchedProducts->count() === count($productIds);
            })->first();

            if($packages) {
                return $packages;
            } else {
                return [];
            }
        }

        if (count($servicesIds) > 0 && count($productIds) > 0) {

            $packages = Packages::query()
            // Ensure the package has all specified services
            ->whereHas('services', function ($query) use ($servicesIds) {
                $query->whereIn('services.id', $servicesIds);
            }, '=', count($servicesIds))
            // Ensure the package has all specified products
            ->whereHas('products', function ($query) use ($productIds) {
                $query->whereIn('products.id', $productIds);
            }, '=', count($productIds))
            // Ensure no other services are included
            ->whereDoesntHave('services', function ($query) use ($servicesIds) {
                $query->whereNotIn('services.id', $servicesIds);
            })
            // Ensure no other products are included
            ->whereDoesntHave('products', function ($query) use ($productIds) {
                $query->whereNotIn('products.id', $productIds);
            })
            ->first();

            if($packages) {
                return $packages;
            } else {
                return [];
            }
        }
    }
}
