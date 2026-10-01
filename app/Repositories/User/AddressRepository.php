<?php

namespace App\Repositories\User;

use App\Models\City;
use App\Models\Address;
use App\Models\Address2;
use App\Models\District;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Repositories\Interfaces\AddressRepositoryInterface;
use App\Helper\ApiResponseHelper;
class AddressRepository implements AddressRepositoryInterface
{
    
     use ApiResponseHelper;
    public function getAllAddresses()
    {
        return Address2::all();
    }

    public function getAddressById($id)
    {
        return Address2::findOrFail($id);
    }

    public function createAddress(array $data)
    {
        if (!empty($data['default']) && $data['default'] == true) {
            Address2::where('user_id', $data['user_id'])->update(['default' => false]);
        }
        return Address2::create($data);
    }


    public function updateAddress($id, array $data)
    {
        $address = Address2::findOrFail($id);

        if (!$address) {
            return response()->json([
                'error' => 'Address not found'
            ], 404);
        }

        // If the updated address is set to default, update all other addresses to false
        if (!empty($data['default']) && $data['default'] == true) {
            Address2::where('user_id', $address->user_id)
                ->where('id', '!=', $id)
                ->update(['default' => false]);
        }

        // Update the address
        $address->update($data);

        return $address;
    }

    public function deleteAddress($id)
    {
        $address = Address2::findOrFail($id);
        if (!$address) {
            return response()->json([
                'error' => 'Address not found'
            ], 404);
        }
        $address->delete();
    }

public function getAllAddressesForSpecificClient($request)
{
    $clientId = $request->user_id;

    if (!$clientId) {
        return response()->json([
            'status' => 404,
            'message' => 'User not found',
            'data' => []
        ], 404);
    }

     $addresses = Address2::where('user_id', $clientId)
        ->where('status', 'active')
        ->get();

  return  $data = $addresses->map(function ($address) {
        return [
            'id' => $address->id,
            'user_id' => (int) $address->user_id,
            'type' => $address->type,
            'name' => (string) $address->name,
            'description' => $address->description,
            'default' => (int) $address->default,
            'status' => $address->status,
            'lat' => $address->lat,
            'long' => $address->long,
            'location_note' => $address->location_note,
            'city' => $address->city,
            'street' => $address->street,
            'country' => $address->country,
            'state' => $address->state,
            'created_at' => optional($address->created_at)->toISOString(),
            'updated_at' => optional($address->updated_at)->toISOString(),
        ];
    });

    return response()->json([
        'status' => 200,
        'data' => $data,
        'message' => 'success'
    ], 200);
}


public function getDefaultAddress()
{
    $clientId = auth()->user()->id;

      $address = Address2::where('user_id', $clientId)
        ->where('status', 'active')
        ->where('default', true)
        ->first();  

    $data = [[
        'id' => $address->id,
        'user_id' => (int) $address->user_id,
        'type' => $address->type,
        'name' => (string) $address->name,
        'description' => $address->description,
        'default' => (int) $address->default,
        'status' => $address->status,
        'lat' => $address->lat,
        'long' => $address->long,
        'location_note' => $address->location_note,
        'city' => $address->city,
        'street' => $address->street,
        'country' => $address->country,
        'state' => $address->state,
        'created_at' => $address->created_at->toISOString(),
        'updated_at' => $address->updated_at->toISOString(),
    ]];

    return $this->setCode(200)->setData($data)->setMessage('success')->send();

    return $this->setCode(200)
        ->setData($address)
        ->setMessage('success')
        ->send();
}

}
