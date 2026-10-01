<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'product_id',
        'variation_id',
        'quantity',
        'standard',
        'right_standard',
        'right_quantity',
        'right_price',
        'left_standard',
        'left_quantity',
        'left_price',
        'unit_price',
        'line_total',
    ];

    protected $casts = [
        'standard' => 'float',
        'right_standard' => 'float',
        'right_price' => 'float',
        'left_standard' => 'float',
        'left_price' => 'float',
        'unit_price' => 'float',
        'line_total' => 'float',
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variation()
    {
        return $this->belongsTo(Variation::class);
    }
}
