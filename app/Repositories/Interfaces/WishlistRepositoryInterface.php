<?php

namespace App\Repositories\Interfaces;


interface WishlistRepositoryInterface
{
    public function getUserWishlist($userId);
    public function addToWishlist($userId, $productId);
    public function removeFromWishlist($userId, $productId);

    ///
    public function all();
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
}
