<?php

namespace App\Services;

use App\Models\Product;

class AppointmentBringProducts
{
    public function get_requested_products($productIds)
    {
        $products =  Product::whereIn('id', $productIds)->get();
        return $products;
    }
}
