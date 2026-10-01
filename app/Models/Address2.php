<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address2 extends Model
{
    use HasFactory;
    protected  $table = "address2s";
    protected $fillable = [
        'user_id',
        'type',
        'name',
        'description',
        'default',
        'status',
        'lat',
        'long',
        'location_note',
        'city',
        'street',
        'state',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
