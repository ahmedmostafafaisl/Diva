<?php

namespace App\Repositories\Interfaces;

interface WaitingListRepositoryInterface
{
    public function getAuthUserWaitingList();

    public function create(array $data);

    public function update(int $id, array $data);

    public function delete(int $id);

    public function addProduct(int $productId);
    public function removeProduct(int $productId);

    public function getByUserId(int $userId);
}
