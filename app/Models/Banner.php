<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = [

        'banner_image',
        'cta',
        'from',
        'to',
        'type',
        'reference_id',
        'duration',
        'status',
    ];
}
