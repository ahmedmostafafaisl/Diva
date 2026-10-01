<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'sku',
        'name',
        'brand',
        'brand_image',
        'price',
        'sale_price',
        'regular_price',
        'desc',
        'status',
        'vendor_id',
        'store_name',
        'store_url',
        'stock_status',
        'type_of_product',
        'short_description',
        'standard',
        'all_price',
        'plus_cat',
        'woo_synced_at',
        'tax', // new tax field
    ];

    protected $casts = [
        'standard' => 'array',
        'all_price' => 'array',
        'woo_synced_at' => 'datetime',
        'count' => 'array',
        'tax' => 'boolean', // cast tax field to boolean
    ];

    public function relatedProducts()
    {
        $categoryIds = [169, 189, 210, 211, 267]; // Define the allowed category IDs

        return Product::whereHas('subCategories', function ($query) use ($categoryIds) {
            $query->whereIn('sub_categories.id', $this->subCategories->pluck('id'))
                ->whereIn('sub_categories.id', $categoryIds); // Ensure subcategory is in the allowed list
        })
            ->where('id', '!=', $this->id) // Exclude the current product
            ->limit(5)
            ->get();
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function subCategories()
    {
        return $this->belongsToMany(SubCategory::class, 'product_subcategory');
    }

    public function suggestions()
    {
        return $this->hasMany(SubCategorySuggestion::class, 'product_id');
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_products', 'order_id', 'product_id')->withPivot('quantity', 'standard', 'right_standard', 'right_quantity', 'left_standard', 'left_quantity', 'price')->withTimestamps();
    }

    public function orderProduct()
    {
        return $this->belongsTo(OrderProduct::class, 'id');
    }

    // /  new

    public function attributes()
    {
        return $this->hasMany(ProductAttribute::class);
    }

    // public function categories()
    // {
    //     return $this->belongsToMany(Category::class);
    // }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'product_tag');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function addOns()
    {
        return $this->hasMany(AddOn::class, 'product_id');
    }

    public function variations()
    {
        return $this->hasMany(Variation::class);
    }

    //
    public function lists()
    {
        return $this->belongsToMany(MyList::class, 'list_product', 'list_id', 'product_id')->withTimestamps();
    }

    public function waiting_list()
    {
        return $this->belongsToMany(WaitingList::class, 'waiting_list_product')->withTimestamps();
    }

    // new lists

    public function userLists()
    {
        return $this->belongsToMany(UserProductList::class, 'user_product_list_items', 'product_id', 'user_product_list_id');
    }
}
