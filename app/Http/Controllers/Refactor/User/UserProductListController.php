<?php

namespace App\Http\Controllers\Refactor\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\Refactor\User\UserListsResource;
use App\Http\Requests\Refactor\User\AddProductToListRequest;
use App\Http\Resources\Refactor\User\UserProductListResource;
use App\Repositories\Interfaces\UserProductListRepositoryInterface;

class UserProductListController extends Controller
{
    public function __construct(protected UserProductListRepositoryInterface $repo) {}

    public function createDefaultLists()
    {
        $this->repo->createDefaultListsForAllUsers();
        return response()->json(['message' => 'Default lists created.']);
    }

    public function index(Request $request)
    {

        $lists = $this->repo->getAllListsForUser($request->user()->id);

        return UserListsResource::collection($lists);
    }

    public function show(Request $request, int $subcategoryId)
    {
        $list = $this->repo->getListWithProducts($request->user()->id, $subcategoryId);
        return new UserProductListResource($list->load('products'));
    }

    public function store(AddProductToListRequest $request)
    {
        $this->repo->addProductToList(
            $request->user()->id,
            $request->subcategory_id,
            $request->product_id
        );

        return response()->json(['message' => 'Product added.']);
    }

    public function destroy(Request $request, int $subcategoryId, int $productId)
    {
        $this->repo->removeProductFromList($request->user()->id, $subcategoryId, $productId);
        return response()->json(['message' => 'Product removed.']);
    }
}
