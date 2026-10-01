<?php

namespace App\Repositories\User;

use App\Models\City;
use App\Models\User;
use App\Models\Address;
use App\Models\Address2;
use App\Models\District;
use App\Models\CouponUser;
use App\Models\Appointment;
use App\Services\DyService;
use App\Models\Subscription;
use App\Services\Qoyod\QoyodService;
use App\Repositories\Interfaces\ClientRepositoryInterface;

class ClientRepository implements ClientRepositoryInterface
{
    public function all($perPage, $page)
    {
        return  $clients = User::where('type', 'customer')->paginate($perPage, ['*'], 'page', $page);
    }

    public function create(array $data)
    {
        $data['type'] = 'customer'; // Ensure type is always 'customer'

        return User::create($data);
    }

    public function find($id)
    {
        return  $customer = User::findOrFail($id);
    }

    public function update($id, array $data)
    {
        $user = User::findOrFail($id);
        $user->update($data);
        return $user;
    }

    ////

    public function searchClientByPhone(string $phone)
    {
        return User::where('type', 'customer')
            ->where(function ($query) use ($phone) {
                $query->where('phone', $phone)
                    ->orWhere('second_phone', $phone);
            })
            ->first();
    }
    public function searchClient($request)
    {
        $phone = $request->phone;
        $username = $request->username;

        return User::where('type', 'customer')
            ->where(function ($query) use ($phone, $username) {
                if ($phone) {
                    $query->where('phone', $phone)
                        ->orWhere('second_phone', $phone);
                }
                if ($username) {
                    $query->orWhere('username', 'like', '%' . $username . '%');
                }
            })
            ->get();
    }
    public function updateCustomerProfile(array $data)
    {
        $user = User::findOrFail($data['id']);
        $user->update($data);
        return $user;
    }

    public function storeCustomerWithAddress(array $data)
    {
        $customer = User::create([
            'username' => $data['username'],
            'phone' => $data['phone'],
            'type' => 'customer',
        ]);
        $address = Address2::create([
            'user_id' => $customer->id,
            'name' => $data['name'] ?? null,
            'city_id' => $data['city_id'],
            'district_id' => $data['district_id'],
            'type' => $data['type'],
            'location_note' => $data['location_note'] ?? '',
        ]);
        $dy = new DyService();
        // Qoyod Integration
        $dQ = new QoyodService();
        $dQ->createCustomerIfNotExists($customer);
        $response = $dy->StoreCustomer($customer->dy_id, $customer->username, $customer->phone);

        if ($response !== null && $response['ResponseStatus'] === true) {
            $customer->update(['dy_integrated' => 1]);
        } else {
            $customer->update(['dy_integrated' => 0]);
        }
        $cityName = City::where('id', $address->city_id)->value('name');
        $districtName = District::where('id', $address->district_id)->value('name');
        return  $customerData = [
            'id' => $customer->id,
            'username' => $customer->username,
            'phone' => $customer->phone,
            'address_id' => $address->id,
            'address_name' => $address->name,
            'address_type' => $address->type,
            'city_id' => $address->city_id,
            'district_id' => $address->district_id,
            'city_name' => $cityName,
            'district_name' => $districtName,
            'created_at' => $customer->created_at,
            'updated_at' => $customer->updated_at,
        ];
    }

    public function getClientAddresses(int $id)
    {
        return Address::where('user_id', $id)
            ->with(['city', 'district'])
            ->get();
    }

    public function updateAddress(int $id, array $data)
    {
        $address = Address::findOrFail($id);
        $address->update($data);
        return $address;
    }

    public function getAllClientAppointments(int $id)
    {
        return Appointment::where('customer_id', $id)
            ->with(['products', 'services'])
            ->get();
    }

    public function getAllClientSubscriptions(int $id)
    {
        return Subscription::where('customer_id', $id)
            ->with(['customer', 'package'])
            ->get();
    }

    public function getAllClientCoupons(int $id)
    {
        return CouponUser::where('user_id', $id)
            ->where('is_used', 1)
            ->with('coupon')
            ->get()
            ->pluck('coupon')
            ->unique('id');
    }
}
