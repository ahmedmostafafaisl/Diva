<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TamaraPayment extends Model
{
    use HasFactory;
    protected $fillable = [
        'phone_number',
        'reference_id',
        'payment_id',
        'checkout_url',
        'status',
        'amount',
        'user_id',
        'discount',
    ];

    // lines relationship
    public function items()
    {
        return $this->hasMany(TamaraPaymentItem::class, 'tamara_payment_id');
    }

    // protected static function boot()
    // {
    //     parent::boot();

    //     static::creating(function ($payment) {
    //         if (empty($payment->reference_id)) {
    //             do {
    //                 // Example: TAM-9F3K8X2A
    //                 $reference = 'TAM-' . strtoupper(Str::random(8));
    //             } while (self::where('reference_id', $reference)->exists());

    //             $payment->reference_id = $reference;
    //         }
    //     });
    // }
}
