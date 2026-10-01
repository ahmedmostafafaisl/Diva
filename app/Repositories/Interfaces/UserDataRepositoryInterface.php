<?php

namespace App\Repositories\Interfaces;

interface UserDataRepositoryInterface
{
    public function all();
    public function find($id);
    public function getByUserId($user,);
    public function create(array $data, $user);
    public function update($id, array $data);
    public function delete($id): bool;
}
