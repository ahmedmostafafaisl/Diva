<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserData extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'phone',
        'email',
        'city',
        'country',
        'state',
        'address_1',
        'address_2',
        'postcode',
        'type',
        'street'
    ];

    public function User()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
