<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MyList extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'type',
        'name',
        'description',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function products()
    {
        return $this->belongsToMany(Product::class, 'list_product', 'list_id', 'product_id')->withTimestamps();
    }
}
