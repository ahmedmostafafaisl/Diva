<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class UserProductList extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'subcategory_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(SubCategory::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'user_product_list_items', 'user_product_list_id', 'product_id');
    }

    public function items()
    {
        return $this->hasMany(UserProductListItem::class);
    }

    public function getNameAttribute(): string
    {
        return match ($this->subcategory_id) {
            211 => 'العدسات',
            169 => 'العطور',
            210 => 'النظارات',
            267 => 'العناية',
            189 => 'التجميل',
            default => 'غير معروف',
        };
    }
}
