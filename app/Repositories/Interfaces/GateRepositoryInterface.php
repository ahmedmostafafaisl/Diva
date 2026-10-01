<?php

namespace App\Repositories\Interfaces;

use App\Models\Gate;




interface GateRepositoryInterface
{
    public function all();
    public function find(int $id);
    public function create(array $data);
    public function update(Gate $gate, array $data);
    public function delete(int $id);
}
