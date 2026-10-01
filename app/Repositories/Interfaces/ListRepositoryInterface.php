<?php


namespace App\Repositories\Interfaces;

use Illuminate\Http\Request;

interface ListRepositoryInterface
{
    public function all();
    public function store(Request $request);
    public function update($id, Request $request);
    public function delete($id);

    public function getUserListsWithProducts($userId);
    public function addProduct($listId, $productId);
    public function removeProduct($listId, $productId);
}
