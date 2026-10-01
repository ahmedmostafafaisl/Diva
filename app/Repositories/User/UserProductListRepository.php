<?php

namespace App\Repositories\User;




use App\Models\User;
use App\Models\Product;
use App\Models\UserProductList;
use App\Models\UserProductListItem;
use App\Repositories\Interfaces\UserProductListRepositoryInterface;

class UserProductListRepository implements UserProductListRepositoryInterface
{
    protected $subcategoryIds = [211, 169, 210, 267, 189];

    public function createDefaultListsForAllUsers(): void
    {
        $users = User::all();
        foreach ($users as $user) {
            foreach ($this->subcategoryIds as $subcategoryId) {
                UserProductList::firstOrCreate([
                    'user_id' => $user->id,
                    'subcategory_id' => $subcategoryId,
                ]);
            }
        }
    }

    public function getAllListsForUser(int $userId)
    {
        return UserProductList::with('subcategory')
            ->where('user_id', $userId)
            ->get(); // ✅ Return the Eloquent collection
    }

    public function getListWithProducts(int $userId, int $subcategoryId)
    {
        return UserProductList::with('products')
            ->where('user_id', $userId)
            ->where('subcategory_id', $subcategoryId)
            ->firstOrFail();
    }

    public function addProductToList(int $userId, int $subcategoryId, int $productId): void
    {
        $list = UserProductList::firstOrCreate([
            'user_id' => $userId,
            'subcategory_id' => $subcategoryId,
        ]);

        UserProductListItem::firstOrCreate([
            'user_product_list_id' => $list->id,
            'product_id' => $productId,
        ]);
    }

    public function removeProductFromList(int $userId, int $subcategoryId, int $productId): void
    {
        $list = UserProductList::where('user_id', $userId)
            ->where('subcategory_id', $subcategoryId)
            ->firstOrFail();

        UserProductListItem::where('user_product_list_id', $list->id)
            ->where('product_id', $productId)
            ->delete();
    }
}
