<?php

namespace App\Repositories\Interfaces;

interface UserProductListRepositoryInterface
{
    public function createDefaultListsForAllUsers(): void;
    public function getAllListsForUser(int $userId);
    public function getListWithProducts(int $userId, int $subcategoryId);
    public function addProductToList(int $userId, int $subcategoryId, int $productId): void;
    public function removeProductFromList(int $userId, int $subcategoryId, int $productId): void;
}
