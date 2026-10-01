<?php

namespace App\Http\Controllers\Refactor\List;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\Refactor\List\ListResource;
use App\Http\Requests\Refactor\List\StoreListRequest;
use App\Repositories\Interfaces\ListRepositoryInterface;
use App\Http\Requests\Refactor\List\AddOrRemoveProductRequest;

class ListController extends Controller
{
    protected $repo;

    public function __construct(ListRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        return ListResource::collection($this->repo->all());
    }

    public function store(StoreListRequest $request)
    {
        return new ListResource($this->repo->store($request));
    }

    public function update(StoreListRequest $request, $id)
    {
        return new ListResource($this->repo->update($id, $request));
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        return response()->json(['message' => 'List deleted.']);
    }

    public function userLists(Request $request)
    {
        return ListResource::collection($this->repo->getUserListsWithProducts($request->user()->id));
    }

    public function addProduct(AddOrRemoveProductRequest $request)
    {
        return ($this->repo->addProduct($request->list_id, $request->product_id));
    }

    public function removeProduct(AddOrRemoveProductRequest $request)
    {
        return ($this->repo->removeProduct($request->list_id, $request->product_id));
    }
}
