<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;



    protected $fillable = ['vendor_id', 'street_1', 'street_2', 'city', 'zip', 'country', 'state'];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
