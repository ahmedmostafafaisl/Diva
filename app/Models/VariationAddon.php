<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VariationAddon extends Model
{
    protected $fillable = [
        'variation_id',
        'side',
        'addon_type',
        'label',
        'price',
    ];

    public function variation()
    {
        return $this->belongsTo(Variation::class);
    }
}
