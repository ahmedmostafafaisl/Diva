<?php

namespace App\Repositories\Interfaces;

interface UserAuthRepositoryInterface
{
    public function register(array $data);
    public function updateCustomerData(array $data, $id);
    public function login(array $credentials);
    public function loginCustomer(array $data);
    public function verifyOtp(array $data);
    public function getUserPermissions($id);
    public function logout();
    public function changeUserStatus();
}
