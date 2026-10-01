<?php

namespace App\Repositories\Interfaces;

use App\Models\Order;

interface OrderRepositoryInterface
{
    public function all($status);
    public function find($id);
    public function store(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function getAuthUserOrders($status);
}
