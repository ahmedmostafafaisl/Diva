<?php

namespace App\Repositories\List;

use App\Models\MyList;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Helper\ApiResponseHelper;
use App\Http\Resources\Refactor\List\ListResource;
use App\Repositories\Interfaces\ListRepositoryInterface;

class ListRepository implements ListRepositoryInterface
{

    use ApiResponseHelper;
    public function all()
    {
        return MyList::with('products')->get();
    }

    public function store(Request $request)
    {
        return $request->user()->lists()->create($request->validated());
    }

    public function update($id, Request $request)
    {
        $list = MyList::findOrFail($id);
        $list->update($request->validated());
        return $list;
    }

    public function delete($id)
    {
        $list = MyList::findOrFail($id);
        return $list->delete();
    }

    public function getUserListsWithProducts($userId)
    {
        return MyList::with('products')->where('user_id', $userId)->get();
    }

    public function addProduct($listId, $productId)
    {
        $list = MyList::findOrFail($listId);
        if ($list->products()->where('product_id', $productId)->exists()) {
            return $this->setCode(401)->setData([])->setMessage('Product already in the list')->send();
        }
        if (!$list->products()->where('product_id', $productId)->exists()) {
            $list->products()->attach($productId);
        }

        return $this->setCode(200)->setData(new ListResource($list->load('products')))->setMessage('Success')->send();
    }

    public function removeProduct($listId, $productId)
    {
        $list = MyList::findOrFail($listId);
        if (!$list->products()->where('product_id', $productId)->exists()) {

            return $this->setCode(401)->setData([])->setMessage('Product not found in the list')->send();
        }
        if ($list->products()->where('product_id', $productId)->exists()) {
            $list->products()->detach($productId);
        }

        return $this->setCode(200)->setData(new ListResource($list->load('products')))->setMessage('Success')->send();
    }
}
