<?php

namespace App\Console\Commands;

use App\Models\Tag;
use App\Models\AddOn;
use App\Models\Vendor;
use App\Models\Address;
use App\Models\Product;
use App\Models\Variation;
use App\Models\ProductImage;
use Illuminate\Console\Command;
use App\Models\ProductAttribute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SyncProducts extends Command
{
    protected $signature = 'sync:products';
    protected $description = 'Sync products from Centerial Mall every day at 5:00 AM';

    protected $centerialMallService;

    public function __construct()
    {
        parent::__construct();
        $this->centerialMallService = app('App\Services\CenterialMallService'); // Inject the service
    }

    public function handle()
    {
        $categoryIds = [211, 169, 210,  267, 288, 290, 322, 137, 189];

        foreach ($categoryIds as $categoryId) {
            $response = Http::withoutVerifying()->get('http://3.73.173.183/wp-json/procontent/v1/products-by-category-s', [
                'category_ids' => $categoryId,
            ]);
            $products = $response->json();
            DB::beginTransaction();

            try {
                foreach ($products as $productData) {
                    foreach ($productData["add_ons"] as   $value) {
                        if (($value["id"]  == "11") || ($value["id"]  == "5")) {
                            $standard =  $value["options"]["standard"];
                        }
                        if (($value["id"]  == "13") || ($value["id"]  == "7")) {
                            $all_price =  $value["options"]["price"];
                        }

                        if (($value["id"]  == "15") || ($value["id"]  == "7")) {
                            $count =  $value["options"]["count"];
                        }
                    }
                    
                    $product = Product::updateOrCreate(
                        ['id' => $productData['id']],

                        [
                            'name' => $productData['name'],
                            'short_description' => $productData['short_description'] ?? null,
                            'desc' => $productData['description'] ?? null,
                            'price' => isset($productData['price']) && $productData['price'] !== '' ? (float) $productData['price'] : null,
                            'regular_price' => isset($productData['regular_price']) && $productData['regular_price'] !== ''
                                ? (float) $productData['regular_price']
                                : (
                                    isset($productData['regular_price_1']) && $productData['regular_price_1'] !== ''
                                    ? (float) $productData['regular_price_1']
                                    : null
                                ),
                            'sale_price' => isset($productData['sale_price']) && $productData['sale_price'] !== '' ? (float) $productData['sale_price'] : (
                                isset($productData['sale_price_1']) && $productData['sale_price_1'] !== ''
                                ? (float) $productData['sale_price_1']
                                : null
                            ),
                            'stock_status' => $productData['stock_status'],
                            'sku' => $productData['sku'] ?? null,
                            'vendor_id' => $productData['vendor']['id'] ?? null,
                            'brand' => $productData['brand_name'] ?? null,
                            'brand_image' => $productData['brand_image_url'] ?? null,
                            'type_of_product' => $productData['type_of_product'] ?? null,
                            'standard' => $standard ?? null,
                            'all_price' => $all_price ?? null,
                            'count' => $count ?? null,

                        ]
                    );

                    // **Attach Images** (Only if Product is Newly Created)
                    if (!empty($productData['images'])) {
                        // If product exists (not newly created), delete old images before inserting new ones
                        if (!$product->wasRecentlyCreated) {
                            ProductImage::where('product_id', $product->id)->delete();
                        }

                        // Insert new images
                        foreach ($productData['images'] as $image) {
                            ProductImage::create([
                                'product_id' => $product->id,
                                'image' => $image
                            ]);
                        }
                    }

                    // // **Attach Attributes** (Prevent Duplicates)
                    if (!empty($productData['attributes'])) {
                        foreach ($productData['attributes'] as $attribute) {
                            ProductAttribute::firstOrCreate([
                                'product_id' => $product->id,
                                'name' => $attribute['name'],
                                'value' => $attribute['value'],
                            ], [
                                'options' => $attribute['options']
                            ]);
                        }
                    }

                    // // **Attach Attributes** (Prevent Duplicates)
                    if (!empty($productData['all_attributes'])) {
                        foreach ($productData['all_attributes'] as $attribute) {
                            ProductAttribute::firstOrCreate([
                                'product_id' => $product->id,
                                'name' => $attribute['name'],
                                'value' => $attribute['value'],
                            ], [
                                'options' => $attribute['options']
                            ]);
                        }
                    }


                    // **Attach Categories**
                    if (!empty($productData['categories'])) {
                        $categoryIds = collect($productData['categories'])->pluck('id')->toArray();
                        $product->subCategories()->syncWithoutDetaching($categoryIds);
                        $product->subCategories()->syncWithoutDetaching($categoryId);
                    }

                    // **Attach Tags**
                    if (!empty($productData['tags'])) {
                        $tagIds = [];
                        foreach ($productData['tags'] as $tagData) {
                            $tag = \App\Models\Tag::firstOrCreate(
                                ['slug' => $tagData['slug']],  // Search by slug
                                ['name' => $tagData['name']]   // Create with name if not found
                            );
                            $tagIds[] = $tag->id;
                        }
                        $product->tags()->syncWithoutDetaching($tagIds);
                    }



                    // **Add Variations** (Prevent Duplicates)
                    if (!empty($productData['variations'])) {
                        foreach ($productData['variations'] as $variation) {
                            Variation::updateOrCreate([
                                'product_id' => $product->id,
                                'sku' => $variation['sku'],
                            ], [
                                'price' => isset($variation['price']) && $variation['price'] !== '' ? (float) $variation['price'] : null,
                                'regular_price' => isset($variation['regular_price']) && $variation['regular_price'] !== '' ? (float) $variation['regular_price'] : null,
                                'sale_price' => isset($variation['sale_price']) && $variation['sale_price'] !== '' ? (float) $variation['sale_price'] : null,
                                'stock_status' => $variation['stock_status'],
                                'image_url' => $variation['image_url'] ?? null,
                                'attributes' => json_encode($variation['attributes'] ?? []),
                                'type' => $variation['attribute_pa_lense-type'] ?? null,
                            ]);
                        }
                    }

                    // **Vendor Handling** (Prevent Duplicate Creation)
                    if (!empty($productData['vendor'])) {
                        $vendor = Vendor::firstOrCreate(
                            ['id' => $productData['vendor']['id']],
                            [
                                'store_url' => $productData['vendor']['store_url'] ?? null,
                                'image_url' => $productData['vendor']['image_url'] ?? null,
                            ]
                        );

                        if (!empty($productData['vendor']['address'])) {
                            Address::updateOrCreate(
                                ['vendor_id' => $vendor->id],
                                [
                                    'street_1' => $productData['vendor']['address']['street_1'] ?? null,
                                    'street_2' => $productData['vendor']['address']['street_2'] ?? null,
                                    'city' => $productData['vendor']['address']['city'] ?? null,
                                    'zip' => $productData['vendor']['address']['zip'] ?? null,
                                    'country' => $productData['vendor']['address']['country'] ?? null,
                                    'state' => $productData['vendor']['address']['state'] ?? null,
                                ]
                            );
                        }
                    }

                    // Store the created product
                    $createdProducts[] = $product;
                }

                DB::commit();
                $this->info("Successfully synced products for category ID: $categoryId");
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Error syncing products for category ID: $categoryId - " . $e->getMessage());
            }
        }
    }
}
