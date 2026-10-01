<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GateBanner extends Model
{
    use HasFactory;
    protected $fillable = ['gate_id', 'image'];

    public function gate()
    {
        return $this->belongsTo(Gate::class);
    }
}
