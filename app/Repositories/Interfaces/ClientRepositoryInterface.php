<?php

namespace App\Repositories\Interfaces;

interface ClientRepositoryInterface
{
    public function all($perPage, $page);
    public function create(array $data);
    public function find($id);
    public function update($id, array $data);

    public function searchClientByPhone(string $phone);
    public function searchClient($request);
    public function updateCustomerProfile(array $data);
    public function storeCustomerWithAddress(array $data);
    public function getClientAddresses(int $id);
    public function updateAddress(int $id, array $data);
    public function getAllClientAppointments(int $id);
    public function getAllClientSubscriptions(int $id);
    public function getAllClientCoupons(int $id);
}
