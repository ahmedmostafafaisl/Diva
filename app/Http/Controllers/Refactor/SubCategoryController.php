<?php

namespace App\Http\Controllers\Refactor;

use App\Helper\ApiResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Refactor\SubCategory\StoreSubCategoryRequest;
use App\Http\Requests\Refactor\SubCategory\UpdateSubCategoryRequest;
use App\Http\Resources\Refactor\SubCategory\SingleSubCategoryResource;
use App\Http\Resources\Refactor\SubCategory\SubCategoryResource;
use App\Models\SubCategory;
use App\Repositories\Interfaces\SubCategoryRepositoryInterface;
use App\Services\CenterialMallService;
use App\Services\New\DivaCollectionsService;
use App\Services\New\WpProductsClient;
use App\Transformers\WpProductToEditedTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubCategoryController extends Controller
{
    use ApiResponseHelper;

    protected $subCategoryRepository;

    protected $centerialMallService;

    public function __construct(SubCategoryRepositoryInterface $subCategoryRepository, CenterialMallService $centerialMallService)
    {
        $this->subCategoryRepository = $subCategoryRepository;
        $this->centerialMallService = $centerialMallService;
    }

    public function index()
    {
        return $this->setCode(code: 200)->setData(SubCategoryResource::collection($this->subCategoryRepository->getAll()))->setMessage('success')->send();
    }

    public function store(StoreSubCategoryRequest $request)
    {
        $subCategory = $this->subCategoryRepository->create($request->validated());

        return $this->setCode(code: 200)->setData(new SubCategoryResource($subCategory))->setMessage('SubCategory Created successfully')->send();
    }

    public function show(Request $request, $id)
    {
        $subCategory = SubCategory::where('id', $id)->first();

        if (! $subCategory || ! in_array($id, [211, 137, 322])) {
            return $this->setCode(404)
                ->setData([])
                ->setMessage('SubCategory not found or invalid ID')
                ->send();
        }

        return $this->setCode(code: 200)->setData(new SingleSubCategoryResource(
            $this->subCategoryRepository->findById($id),
            $request->input('page', 1),
            $request->input('per_page', 10),
            $request->input('category_id', null)
        ))->setMessage('success')->send();
    }

    public function update(UpdateSubCategoryRequest $request, $id)
    {
        $subCategory = $this->subCategoryRepository->update($id, $request->validated());

        return $this->setCode(code: 200)->setData(new SubCategoryResource($subCategory))->setMessage('SubCategory Updated successfully')->send();
    }

    public function destroy($id)
    {
        $this->subCategoryRepository->delete($id);

        return $this->setCode(code: 200)->setData([])->setMessage('SubCategory deleted successfully')->send();
    }

    public function newShow(Request $request, $id, WpProductsClient $wp)
    {
        $subCategory = SubCategory::with('images')->find($id);

        if (! $subCategory || ! in_array((int) $id, [211, 137, 322])) {
            return $this->setCode(404)
                ->setData([])
                ->setMessage('SubCategory not found or invalid ID')
                ->send();
        }

        $page = max((int) $request->input('page', 1), 1);

        $perPage = (int) $request->input('per_page', 10);
        $perPage = $perPage > 0 ? $perPage : 10;

        $categoryId = $request->input('category_id', null);
        $categoryId = $categoryId !== null ? (int) $categoryId : null;

        // ✅ user wishlist/cart ids (true/false)
        $user = Auth::user();
        $wishlistIds = ($user && $user->wishlist)
            ? $user->wishlist->products->pluck('product_id')->all()
            : [];

        $cartIds = ($user && $user->cart)
            ? $user->cart->products->pluck('product_id')->all()
            : [];

        // 1) get ALL products of this subcategory from the new diva-commerce API (cached)
        $original = collect($wp->productsByCategory((int) $id));

        // 2) optional intersect with parent category products (لو category_id مبعوت)
        if ($categoryId) {
            $parentIds = collect($wp->productsByCategory($categoryId))
                ->pluck('id')->filter()->values()->all();

            $original = $original->filter(fn ($p) => in_array($p['id'] ?? null, $parentIds));
        }

        $original = $original->values();

        // 3) brands/new_brands/plus (على مستوى subcategory كلها)
        $brands = $original->pluck('brand_name')->filter()->unique()->values()->all();

        $new_brands = $original
            ->filter(fn ($p) => ! empty($p['brand_name']))
            ->unique('brand_name')
            ->map(fn ($p) => [
                'name' => $p['brand_name'],
                'image' => $p['brand_image_url'] ?? null,
            ])
            ->values()
            ->all();

        $plus = ['الترتيب', 'العلامات التجاريه'];

        // 4) paginate first, then transform only the current page
        $total = $original->count();
        $totalPages = (int) ceil($total / $perPage);

        $data = $original
            ->forPage($page, $perPage)
            ->map(fn ($p) => WpProductToEditedTransformer::transform($p, [
                'wishlist_ids' => $wishlistIds,
                'cart_ids' => $cartIds,
            ]))
            ->values();

        return $this->setCode(200)->setData([
            'id' => $subCategory->id,
            'gate_id' => (int) $subCategory->gate_id,
            'name' => (string) $subCategory->name,
            'desc' => (string) $subCategory->desc,
            'images' => $subCategory->images->map(fn ($img) => asset('storage/'.$img->image)),
            'brands' => $brands,
            'new_brands' => $new_brands,
            'plus' => $plus,
            'products' => [
                'data' => $data,
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'count' => $data->count(),
                'total_pages' => $totalPages,
            ],
        ])->setMessage('success')->send();
    }

    /**
     * GET /ref/subcategories/{id}/collections
     * Same products as newShow(), grouped into sections:
     * products of the month, most popular, then one section per Diva collection.
     */
    public function collections(Request $request, $id, WpProductsClient $wp, DivaCollectionsService $collections)
    {
        $subCategory = SubCategory::with('images')->find($id);

        if (! $subCategory || ! in_array((int) $id, [211, 137, 322])) {
            return $this->setCode(404)
                ->setData([])
                ->setMessage('SubCategory not found or invalid ID')
                ->send();
        }

        // user wishlist/cart ids (true/false)
        $user = Auth::user();
        $wishlistIds = ($user && $user->wishlist)
            ? $user->wishlist->products->pluck('product_id')->all()
            : [];

        $cartIds = ($user && $user->cart)
            ? $user->cart->products->pluck('product_id')->all()
            : [];

        // all products of this subcategory (cached in WpProductsClient)
        $original = collect($wp->productsByCategory((int) $id))->values();

        $likes = $collections->likesCount($original->pluck('id')->filter()->all());

        $context = [
            'wishlist_ids' => $wishlistIds,
            'cart_ids' => $cartIds,
            'likes' => $likes,
        ];

        $transform = function (array $p) use ($collections, $context) {
            $item = WpProductToEditedTransformer::transform($p, $context);
            $item['collection'] = $collections->collectionKeyFor($p['sku'] ?? null) ?? 'other';
            $item['lens_name'] = $collections->lensNameFor($p['sku'] ?? null);

            return $item;
        };

        $cfg = config('diva_collections');
        $products = $original->all();

        $sections = [];

        // 1) Products of the Month (random, fixed for the whole month)
        $sections[] = [
            'key' => 'products_of_the_month',
            'title' => $cfg['products_of_the_month']['title'],
            'title_ar' => $cfg['products_of_the_month']['title_ar'],
            'products' => array_map(
                $transform,
                $collections->productsOfTheMonth($products, (int) $cfg['products_of_the_month']['limit'])
            ),
        ];

        // 2) Most Popular (by wishlist count)
        $sections[] = [
            'key' => 'most_popular',
            'title' => $cfg['most_popular']['title'],
            'title_ar' => $cfg['most_popular']['title_ar'],
            'products' => array_map(
                $transform,
                $collections->mostPopular($products, $likes, (int) $cfg['most_popular']['limit'])
            ),
        ];

        // 3..6) Her Diva, Chic Diva, Miss Diva, Diva (+ Other for unmatched SKUs)
        $grouped = $original->groupBy(fn ($p) => $collections->collectionKeyFor($p['sku'] ?? null) ?? 'other');

        foreach ($cfg['collections'] as $key => $collection) {
            $sections[] = [
                'key' => $key,
                'title' => $collection['title'],
                'title_ar' => $collection['title_ar'],
                'products' => $grouped->get($key, collect())->map($transform)->values()->all(),
            ];
        }

        if ($grouped->has('other')) {
            $sections[] = [
                'key' => 'other',
                'title' => $cfg['other']['title'],
                'title_ar' => $cfg['other']['title_ar'],
                'products' => $grouped->get('other')->map($transform)->values()->all(),
            ];
        }

        $new_brands = $original
            ->filter(fn ($p) => ! empty($p['brand_name']))
            ->unique('brand_name')
            ->map(fn ($p) => [
                'name' => $p['brand_name'],
                'image' => $p['brand_image_url'] ?? null,
            ])
            ->values()
            ->all();

        return $this->setCode(200)->setData([
            'id' => $subCategory->id,
            'gate_id' => (int) $subCategory->gate_id,
            'name' => (string) $subCategory->name,
            'desc' => (string) $subCategory->desc,
            'images' => $subCategory->images->map(fn ($img) => asset('storage/'.$img->image)),
            'brands' => $original->pluck('brand_name')->filter()->unique()->values()->all(),
            'new_brands' => $new_brands,
            'plus' => ['الترتيب', 'العلامات التجاريه'],
            'total_products' => $original->count(),
            'sections' => $sections,
        ])->setMessage('success')->send();
    }
}
