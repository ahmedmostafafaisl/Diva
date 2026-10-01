<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TamaraPaymentItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'tamara_payment_id',
        'item_id',
        'name',
        'description',
        'quantity',
        'price',
        'discount_amount',
        'category',
    ];

    public function payment()
    {
        return $this->belongsTo(TamaraPayment::class, 'tamara_payment_id');
    }
}
