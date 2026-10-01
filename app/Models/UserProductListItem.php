<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProductListItem extends Model
{
    use HasFactory;

    protected $fillable = ['user_product_list_id', 'product_id'];

    public function list()
    {
        return $this->belongsTo(UserProductList::class, 'user_product_list_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
