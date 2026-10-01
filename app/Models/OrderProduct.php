<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderProduct extends Model
{
    use HasFactory;



    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'standard',
        'right_standard',
        'right_quantity',
        'left_standard',
        'left_quantity',
        'price',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'id');
    }
}
