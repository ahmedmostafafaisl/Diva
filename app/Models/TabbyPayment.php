<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TabbyPayment extends Model
{
    use HasFactory;
    protected $fillable = [
        'reference_id',
        'payment_id',
        'session_id',
        'session_url',
        'status',
        'user_id',
        'amount',
        'order_id'
    ];

    // generate a unique identifier for the Ticket
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tPayment) {
            $tPayment->reference_id = self::generateUniqueReferenceNumber();
        });
    }

    protected static function generateUniqueReferenceNumber()
    {
        $number = time() . '-' . rand(1000, 9999);

        // Ensure uniqueness
        if (self::where('reference_id', $number)->exists()) {
            return self::generateUniqueReferenceNumber();
        }

        return $number;
    }
}
