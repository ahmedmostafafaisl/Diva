<?php

namespace App\Services;

use App\Models\Package;

class PackagesCheck
{
    public function check_packages(array $serviceIds, array $productIds)
    {

        // Early return for cases where no IDs are provided
        if (empty($serviceIds) && empty($productIds)) {
            return collect(); // Return an empty collection for consistency
        }

        $query = Package::query();

        // Conditionally load relations and filter by service IDs if provided
        if (!empty($serviceIds)) {
            $query->whereHas('services', function ($query) use ($serviceIds) {
                $query->whereIn('services.id', $serviceIds);
            })->with('services');
        }

        // Conditionally load relations and filter by product IDs if provided
        if (!empty($productIds)) {
            $query->whereHas('products', function ($query) use ($productIds) {
                $query->whereIn('products.id', $productIds);
            })->with('products');
        }

        // Retrieve the packages that match the criteria
        $packages = $query->get();

        // Further filter the packages if both service and product IDs are provided
        if (!empty($serviceIds) && !empty($productIds)) {
            $packages = $packages->filter(function ($package) use ($serviceIds, $productIds) {
                $matchedServices = $package->services->pluck('id')->intersect($serviceIds);
                $matchedProducts = $package->products->pluck('id')->intersect($productIds);
                return $matchedServices->count() === count($serviceIds) && $matchedProducts->count() === count($productIds);
            });
        }

        return $packages;
    }
}
