<?php

namespace App\Repositories\Interfaces;

interface UserRepositoryInterface
{
    public function getAllUsers();

    public function getUserById($id);

    public function createUser(array $data);

    public function updateUser($id, array $data);
    public function updateUserPassword($id, $password);

    public function deleteUser($id);
}
