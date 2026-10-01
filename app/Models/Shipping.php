<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipping extends Model
{
    use HasFactory;

    protected $fillable = [
        'zone_id',
        'title',
        'method_title',
        'method_description',
        'instance_id',
        'order',
        'enabled',
        'cost',
        'tax',
    ];

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }
}
