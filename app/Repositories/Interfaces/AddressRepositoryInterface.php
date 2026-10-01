<?php

namespace App\Repositories\Interfaces;

use Illuminate\Http\Request;

interface AddressRepositoryInterface
{
    public function getAllAddresses();
    public function getAddressById($id);
    public function createAddress(array $data);
    public function updateAddress($id, array $data);
    public function deleteAddress($id);
    public function getAllAddressesForSpecificClient(Request $request);
    public function getDefaultAddress();
}
