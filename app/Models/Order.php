<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'address_id',
        'order_transaction',
        'currency',
        'tax',
        'total_amount',
        'total_price',
        'billing_email',
        'payment_method',
        'payment_provider',
        'shipment_note',
        'status',
        'payment_status',
        'payment_id',
        'payment_url',
        'paid_at',
        'discount_total',
        'woo_order_id',
    ];

    protected $casts = [
        'tax' => 'float',
        'total_price' => 'float',
        'discount_total' => 'float',
        'paid_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function address()
    {
        return $this->belongsTo(Address2::class, 'address_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'order_products', 'order_id', 'product_id')
            ->withPivot([
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
                'price',
            ])
            ->withTimestamps();
    }

    // ✅ الصحيح بدل belongsTo الغلط
    public function orderProducts()
    {
        return $this->hasMany(OrderProduct::class, 'order_id');
    }
}
