<?php

namespace App\Repositories\List;



use App\Models\WaitingList;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Interfaces\WaitingListRepositoryInterface;

class WaitingListRepository implements WaitingListRepositoryInterface
{
    public function getAuthUserWaitingList()
    {
        $user = Auth::user();
        return $user->waitingList()->with('products')->first();
    }

    public function create(array $data)
    {
        // Ensure user has no waiting list
        if (WaitingList::where('user_id', $data['user_id'])->exists()) {
            return 'Waiting list already exists for this user';
        }
        return WaitingList::create($data);
    }

    public function update(int $id, array $data)
    {
        $waitingList = WaitingList::find($id);
        if (!$waitingList) return 'Waiting list not found';

        $waitingList->update($data);
        return $waitingList;
    }

    public function delete(int $id)
    {
        $waitingList = WaitingList::find($id);
        if (!$waitingList) return 'Waiting list not found';

        $waitingList->delete();
        return true;
    }

    public function addProduct(int $productId)
    {
        $user = auth()->user();

        // Get or create waiting list for user
        $waitingList = WaitingList::firstOrCreate(
            ['user_id' => $user->id]
        );

        // Check if product already attached
        if ($waitingList->products()->where('product_id', $productId)->exists()) {
            return 'Product already exists in waiting list';
        }

        $waitingList->products()->attach($productId);

        return true;
    }

    public function removeProduct(int $productId)
    {
        $user = auth()->user();

        $waitingList = WaitingList::where('user_id', $user->id)->first();

        if (!$waitingList) {
            return 'Waiting list not found for user';
        }

        if (!$waitingList->products()->where('product_id', $productId)->exists()) {
            return 'Product not found in waiting list';
        }

        $waitingList->products()->detach($productId);

        return true;
    }

    public function getByUserId(int $userId)
    {
        return WaitingList::where('user_id', $userId)->with('products')->first();
    }
}
