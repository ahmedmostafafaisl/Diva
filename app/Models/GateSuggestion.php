<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GateSuggestion extends Model
{
    use HasFactory;
    protected $fillable = ['gate_id', 'product_id'];

    public function gate()
    {
        return $this->belongsTo(Gate::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
