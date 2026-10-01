<?php

namespace App\Repositories\Wishlist;

use App\Models\Wishlist;
use App\Repositories\Interfaces\WishlistRepositoryInterface;

class WishlistRepository implements WishlistRepositoryInterface
{
    public function getUserWishlist($userId)
    {
        return Wishlist::where('user_id', $userId)->with('products.product.images')->first();
    }

    public function addToWishlist($userId, $productId)
    {
        $wishlist = Wishlist::firstOrCreate(['user_id' => $userId]);

        // Check if the product already exists in the wishlist
        if (!$wishlist->products()->where('product_id', $productId)->exists()) {
            $wishlist->products()->create(['product_id' => $productId]);
        }

        return $wishlist->load('products.product.images');
    }
    public function removeFromWishlist($userId, $productId)
    {
        $wishlist = Wishlist::where('user_id', $userId)->first();

        if ($wishlist) {
            $wishlist->products()->where('product_id', $productId)->delete();
        }

        return $wishlist ? $wishlist->load('products.product.images') : null;
    }

    /////////////////////////
    public function all()
    {
        return Wishlist::with('products')->paginate(10);
    }

    public function find($id)
    {
        return Wishlist::with('products')->findOrFail($id);
    }

    public function create(array $data)
    {
        return Wishlist::create($data);
    }

    public function update($id, array $data)
    {
        $wishlist = $this->find($id);
        $wishlist->update($data);
        return $wishlist;
    }

    public function delete($id)
    {
        $wishlist = $this->find($id);
        return $wishlist->delete();
    }
}
