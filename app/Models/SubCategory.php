<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubCategory extends Model
{
    use HasFactory;
    protected $fillable = [
        'gate_id',
        'name',
        'desc',
        'parent',
        'count',
        'status',
        'priority',

    ];

    public function gate()
    {
        return $this->belongsTo(Gate::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_subcategory');
    }

    public function suggestions()
    {
        return $this->hasMany(SubCategorySuggestion::class, 'subCategory_id');
    }

    public function images()
    {
        return $this->hasMany(SubCategoryImage::class, 'sub_category_id');
    }

    // new

    // Parents of this subcategory
    // public function parents()
    // {
    //     return $this->belongsToMany(SubCategory::class, 'sub_category_parent', 'sub_category_id', 'parent_id');
    // }

    // Children of this subcategory
    // public function children()
    // {
    //     return $this->belongsToMany(SubCategory::class, 'sub_category_parent', 'parent_id', 'sub_category_id');
    // }

    public function parents()
    {
        return $this->belongsToMany(
            SubCategory::class,
            'sub_category_parent',
            'sub_category_id',
            'parent_id'
        );
    }
    public function children()
    {
        return $this->belongsToMany(
            SubCategory::class,
            'sub_category_parent',
            'parent_id',
            'sub_category_id'
        );
    }


    // new lists

    public function userProductLists()
    {
        return $this->hasMany(UserProductList::class);
    }
}
