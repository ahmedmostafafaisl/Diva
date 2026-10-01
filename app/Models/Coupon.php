<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'status',
        'usage_count',
        'expiry_date',
        'start_date',
        'usage_limit_per_user',
        'discount_type',
        'discount_amount',
        'first_time_only',
        'type',
        'reference_id',
    ];

    protected $casts = [
        'expiry_date' => 'datetime',
        'start_date' => 'datetime',
    ];





    public function users()
    {
        return $this->belongsToMany(User::class, 'coupon_users', 'coupon_id', 'user_id')
            ->withPivot('is_used')
            ->withTimestamps();
    }


    public function isAvailableForUser(User $user)
    {
        $currentDate = now();

        // Check if the coupon is within the valid date range
        if ($this->start_date > $currentDate || $this->expiry_date < $currentDate) {
            return false;
        }
        if ($this->status == 'inactive') {
            return false;
        }

        // Check the total usage limit of the coupon
        $totalUsage = $this->couponUsers()->count();
        if ($this->usage_limit !== null && $totalUsage >= $this->usage_limit) {
            return false;
        }

        // Check the usage limit per user
        $userUsage = $this->couponUsers()->where('user_id', $user->id)->count();
        if ($this->limit_per_user !== null && $userUsage >= $this->limit_per_user) {
            return false;
        }

        return true;
    }
}
