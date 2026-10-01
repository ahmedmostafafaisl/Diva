<?php

namespace App\Http\Controllers\Refactor;

use App\Models\AddOn;
use App\Models\Vendor;
use GuzzleHttp\Client;
use App\Models\Address;
use App\Models\Product;
use App\Models\Variation;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use App\Models\ProductAttribute;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Services\CenterialMallService;
use App\Services\ThirdPartyApiService;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use App\Http\Resources\Refactor\Product\NewProductResource;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use App\Services\WooOrderService;

  // Save Comment
class CenterialController extends Controller
{
    protected $centerialMallService;
    protected $apiService;

    protected $wooService;
    public function __construct(CenterialMallService $centerialMallService, ThirdPartyApiService $apiService, WooOrderService $wooService)
    {
        $this->centerialMallService = $centerialMallService;
        $this->apiService = $apiService;
        $this->wooService = $wooService;
    }
    public function getProductsByCategory(Request $request)
    {
        $createdProducts = [];
        $categoryIds = '211'; // Default value

        // return  $products = $this->centerialMallService->getProductsByCategory($categoryIds);

        // return $products = $response->json();

        $categoryIds = [267, 211, 169, 210,  288, 290, 322, 137, 189];
        foreach ($categoryIds as $categoryId) {
            $response = Http::withoutVerifying()->get('http://3.73.173.183/wp-json/procontent/v1/products-by-category-s', [
                'category_ids' => $categoryId,
            ]);
            return $products = $response->json();
            DB::beginTransaction();

            foreach ($products as $productData) {
                ///
                return ($productData);
                $allowedIds = [267, 211, 169, 210, 288, 290, 322, 137, 189];

                $plusCategories = null;

                foreach ($productData["categories"] as $category) {
                    if (!in_array($category["id"], $allowedIds)) {
                        $plusCategories  = $category["name"];
                    }
                }
                // return $plusCategories;
                //
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
                        'regular_price' => isset($productData['regular_price']) && $productData['regular_price'] !== '' ? (float) $productData['regular_price'] : null,
                        'sale_price' => isset($productData['sale_price']) && $productData['sale_price'] !== '' ? (float) $productData['sale_price'] : null,
                        'stock_status' => $productData['stock_status'],
                        'sku' => $productData['sku'] ?? null,
                        'vendor_id' => $productData['vendor']['id'] ?? null,
                        'brand' => $productData['brand_name'] ?? null,
                        'type_of_product' => $productData['type_of_product'] ?? null,
                        'standard' => $standard ?? null,
                        'all_price' => $all_price ?? null,
                        'count' => $count ?? null,
                        'plus_cat' => $plusCategories ?? null,

                    ]
                );

                // **Attach Images** (Only if Product is Newly Created)
                // if ($product->wasRecentlyCreated && !empty($productData['images'])) {
                //     foreach ($productData['images'] as $image) {
                //         ProductImage::create([
                //             'product_id' => $product->id,
                //             'image' => $image
                //         ]);
                //     }
                // }

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

                // **Add Add-Ons** (Prevent Duplicates)

                // if (!empty($productData['add_ons'])) {
                //     foreach ($productData['add_ons'] as $addOn) {
                //         AddOn::firstOrCreate([
                //             'product_id' => $product->id,
                //             'title' => $addOn['setting']['title'],

                //         ], [
                //             'type' => $addOn['setting']['type'] ?? null,
                //             // 'required' => (bool) $addOn['setting']['required'] ?? null,
                //             'options' => json_encode($addOn['options'] ?? [])
                //         ]);
                //     }
                // }

                // **Add Variations** (Prevent Duplicates)
                if (!empty($productData['variations'])) {
                    foreach ($productData['variations'] as $variation) {
                        Variation::firstOrCreate([
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
        }


        return response()->json(['message' => 'Products created successfully!', 'products' => NewProductResource::collection($createdProducts)], 201);
    }


    public function searchCustomerByPhone($phone)
    {

        // $response = Http::get('https://3.73.173.183/wp-json/wc/v3/orders?consumer_key=ck_3ac0df7ec455715d7173bef920c7a779ac907c51&consumer_secret=cs_03ea5e697c1d25a77c5f578cf8a88593ba3c1d4e&search=541356095');
        $response = Http::withOptions([
            'verify' => false
        ])->get('https://3.73.173.183/wp-json/wc/v3/orders', [
            'consumer_key' => 'ck_3ac0df7ec455715d7173bef920c7a779ac907c51',
            'consumer_secret' => 'cs_03ea5e697c1d25a77c5f578cf8a88593ba3c1d4e',
            'search' => $phone
        ]);
        if ($response->successful()) {
            $data = $response->json(); // Get JSON data
            if (!empty($data)) {
                return response()->json($data[0]); // Return the first customer
            } else {
                return response()->json(['message' => 'No customer found'], 404);
            }
        } else {
            // Log error details
            return response()->json([
                'error' => 'Request failed',
                'status_code' => $response->status(),
                'body' => $response->body(), // Check the response body for debugging
                'headers' => $response->headers()
            ], $response->status());
        }


        return   $products = $response->json();
        $client = new Client();

        // OAuth 1.0 authentication
        $oauth = new Oauth1([
            'consumer_key'    => 'ck_3ac0df7ec455715d7173bef920c7a779ac907c51',
            'consumer_secret' => 'cs_03ea5e697c1d25a77c5f578cf8a88593ba3c1d4e',
            'token'           => '', // If you have an access token, use it here
            'token_secret'    => '', // If you have a token secret, use it here
        ]);
        $baseUrl = 'https://centerialmall.com'; // http://3.73.173.183
        $consumerKey = 'ck_3ac0df7ec455715d7173bef920c7a779ac907c51'; // or hardcode
        $consumerSecret = 'cs_03ea5e697c1d25a77c5f578cf8a88593ba3c1d4e';

        // Add OAuth 1.0 to the request
        $client->getEmitter()->attach($oauth);

        try {
            // Make the request with OAuth authentication
            $response = $client->get('https://centerialmall.com' . '/wp-json/wc/v3/customers', [
                'query' => [
                    'search' => $phone,
                ]
            ]);

            $customers = json_decode($response->getBody()->getContents(), true);

            // Check if customers are found
            if (!empty($customers)) {
                return response()->json($customers[0]); // Return the first customer
            } else {
                return response()->json(['message' => 'No customer found.'], 404);
            }
        } catch (RequestException $e) {
            // Log the error and return a failed response
            \Log::error('WooCommerce API Request Failed', [
                'url' => $e->getRequest()->getUri(),
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Failed to connect to WooCommerce.'], 500);
        }
    }
    // Beauty Show
    // Get All Posts

    public function getAllPosts()
    {
        return  $this->wooService->getAllPosts();
    }
    // Get Single Post

    public function getSinglePost($id)
    {

        return  $this->wooService->getSinglePost($id);
    }
    
        // Get All Comments
    public function getAllComments($id)
    {
        return  $this->wooService->getAllComments($id);
    }
    
        // Get influencer Followers  And Following
    public function getInfluencerFollowersAndFollowing($id)
    {
        return  $this->wooService->getInfluencerFollowersAndFollowing($id);
    }
    
        // Get All Stores
    public function getAllStores()
    {
        return  $this->wooService->getAllStores();
    }
    
    
    // Like Post
    public function likePost($id)
    {
        return  $this->wooService->likePost($id);
    }
    
        // Save Post
    public function savePost($id)
    {
        return  $this->wooService->savePost($id);
    }
    
     // Save Comment
    public function saveComment(Request $request, $id)
    {

        $validatedData = $request->validate([
            'comment' => 'required|string',
        ]);
        return  $this->wooService->saveComment($id, $validatedData['comment']);
    }
    
     // nested Comment
    public function nestedComment(Request $request, $id)
    {

        $validatedData = $request->validate([
            'comment' => 'required|string',
            'parent_id' => 'required',
        ]);
        return  $this->wooService->nestedComment($id, $validatedData['parent_id'], $validatedData['comment']);
    }
    
    // Follow User
    public function followUser($id)
    {

        return  $this->wooService->followUser($id);
    }
    
      //  User Contents
    public function userContents($id)
    {

        return  $this->wooService->userContents($id);
    }
    
           //  User Products
    public function userProducts($id)
    {

        return  $this->wooService->userProducts($id);
    }
    
        //  Liked Contents
    public function likedContents()
    {

        return  $this->wooService->likedContents();
    }

    //  Saved Contents
    public function savedContents()
    {

        return  $this->wooService->savedContents();
    }
    
    //  allInfluencers
    public function allInfluencers()
    {

        return  $this->wooService->allInfluencers();
    }

     // followersInfluencers
    public function followedInfluencersPosts()
    {
        return  $this->wooService->followedInfluencersPosts();
    }
    
       // Get All Collections

    public function getAllCollections()
    {
        return  $this->wooService->getAllCollections();
    }
    
    // Influencers


    // Get Influencer Posts

    public function getInfluencerPosts()
    {
        return  $this->wooService->getInfluencerPosts();
    }
    // Create Post

    public function createPost(Request $request)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:1000',
            'product_ids' => 'required|string',
            'media' => 'required|array|min:1',
            'media.*' => 'mimes:jpg,jpeg,png,avi,flv,wmv,mov,mp4|max:50000',
        ]);

        // Call the service to create the post
        return $this->wooService->createPost($validated);
    }


    // Get Influencer Products

    public function getInfluencerProducts()
    {
        return  $this->wooService->getInfluencerProducts();
    }

    // Get Influencer Profile

    public function getInfluencerProfile()
    {
        return  $this->wooService->getInfluencerProfile();
    }
    
        // Delete Post

    public function deletePost($id)
    {
        // Call the service to create the post
        return $this->wooService->deletePost($id);
    }
    
       // Create Banner
    public function createBanner(Request $request)
    {
        $validatedData = $request->validate([
            'image'      => 'required|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);
        return $this->wooService->createBanner($validatedData);
    }
    // Delete Banner
    public function deleteBanner()
    {
        return $this->wooService->deleteBanner();
    }
 //  update profile picture

    public function updateProfilePicture(Request $request)
    {
        $validatedData = $request->validate([
            'image'      => 'required|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);
        return $this->wooService->updateProfilePicture($validatedData);
    }
    // delete profile picture
    public function deleteProfilePicture()
    {
        return $this->wooService->deleteProfilePicture();
    }
    
       // user stories
    public function createUserStory(Request $request)
    {
        $validatedData = $request->validate([
            'image'      => 'required|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);
        return $this->wooService->createUserStory($validatedData);
    }
    public function getUserStories()
    {
        return $this->wooService->getUserStories();
    }
    public function deleteUserStory($id)
    {
        return $this->wooService->deleteUserStory($id);
    }
}
