<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubCategorySuggestion extends Model
{
    use HasFactory;

    protected $table = 'sub_category_suggestions';

    protected $fillable = [
        'subCategory_id',
        'product_id',
    ];

    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class, 'subCategory_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
