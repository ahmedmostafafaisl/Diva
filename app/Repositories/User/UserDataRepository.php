<?php

namespace App\Repositories\User;

use App\Models\Address2;
use App\Models\UserData;
use App\Repositories\Interfaces\UserDataRepositoryInterface;

class UserDataRepository implements UserDataRepositoryInterface
{
    public function all()
    {
        return UserData::all();
    }

    public function find($id)
    {
        return UserData::findOrFail($id);
    }

    public function getByUserId($user)
    {
        $userDate = [
            "first_name" => $user->first_name ?? null,
            "last_name" => $user->last_name ?? null,
            "email" => $user->email ?? null,
            "phone" => $user->phone ?? null,
        ];
        $address = Address2::where('user_id', $user->id)
            ->where('status', 'active')->where('default', true)
            ->first();
        $addressData = [
            "country" => $address->country ?? null,
            "city" => $address->city ?? null,
            "state" => $address->state ?? null,
            "street" => $address->street ?? null,
            "location_note" => $address->location_note ?? null,
        ];

        return [
            "id" => $user->id ?? null,
            "first_name" => $user->first_name ?? null,
            "last_name" => $user->last_name ?? null,
            "email" => $user->email ?? null,
            "phone" => $user->phone ?? null,
            "birth_date" => $user->birth_date ?? null,
            "gender" => $user->gender ?? null,
            "country" => $address->country ?? null,
            "city" => $address->city ?? null,
            "state" => $address->state ?? null,
            "street" => $address->street ?? null,
            "location_note" => $address->location_note ?? null,
        ];
    }
   public function create(array $data, $user)
{
    $userFields = ['first_name', 'last_name', 'email', 'gender', 'birth_date'];

    // Fix: Only check if the data has the field, not the current user value
    foreach ($userFields as $field) {
        if (isset($data[$field])) {
            $user->$field = $data[$field];
        }
    }
    $user->save();

    // Address handling
    $address = Address2::where('user_id', $user->id)
        ->where('status', 'active')
        ->where('default', true)
        ->first();

    $addressFields = ['city', 'state', 'street', 'location_note', 'type'];

    if (!$address) {
        $addressData = collect($data)
            ->only($addressFields)
            ->merge([
                'user_id' => $user->id,
                'status' => 'active',
                'default' => true,
            ])
            ->toArray();

        $address = Address2::create($addressData);
    } else {
        foreach ($addressFields as $field) {
            if (isset($data[$field])) {
                $address->$field = $data[$field];
            }
        }
        $address->save();
    }

    return [
        "id" => $user->id ?? null,
        "first_name" => $user->first_name ?? null,
        "last_name" => $user->last_name ?? null,
        "email" => $user->email ?? null,
        "phone" => $user->phone ?? null,
        "birth_date" => $user->birth_date ?? null,
        "gender" => $user->gender ?? null,
        "country" => $address->country ?? null,
        "city" => $address->city ?? null,
        "state" => $address->state ?? null,
        "street" => $address->street ?? null,
        "location_note" => $address->location_note ?? null,
    ];
}


    public function update($user, array $data)
    {
        // dd($data);
        $userFields = ['first_name', 'last_name', 'email', 'gender', 'birth_date'];

        foreach ($userFields as $field) {
            if (($user->$field) && isset($data[$field])) {
                $user->$field = $data[$field];
            }
        }
        $user->save();

        // Address
        $address = Address2::where('user_id', $user->id)
            ->where('status', 'active')->where('default', true)
            ->first();

        $addressFields = ['city', 'state', 'street', 'location_note', 'type'];

        if (!$address) {
            $addressData = collect($data)
                ->only($addressFields)
                ->merge([
                    'user_id' => $user->id,
                    'status' => 'active',
                    'default' => true,
                    'street' => $data['street'],

                ])
                ->toArray();

            Address2::create($addressData);
        } else {
            foreach ($addressFields as $field) {
                if (($address->$field) && isset($data[$field])) {
                    $address->$field = $data[$field];
                }
            }
            $address->street = $data['street'];
            $address->save();
        }


        return [
            "id" => $user->id ?? null,
            "first_name" => $user->first_name ?? null,
            "last_name" => $user->last_name ?? null,
            "email" => $user->email ?? null,
            "phone" => $user->phone ?? null,
            "birth_date" => $user->birth_date ?? null,
            "gender" => $user->gender ?? null,
            "country" => $address->country ?? null,
            "city" => $address->city ?? null,
            "state" => $address->state ?? null,
            "street" => $address->street ?? null,
            "location_note" => $address->location_note ?? null,
        ];
    }

    public function delete($id): bool
    {
        $userData = $this->find($id);
        return $userData->delete();
    }
}
