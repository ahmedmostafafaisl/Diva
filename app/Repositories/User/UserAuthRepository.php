<?php

namespace App\Repositories\User;

use App\Models\User;
use App\Models\EmployeeDay;
use Illuminate\Support\Facades\Hash;
use App\Repositories\Interfaces\UserAuthRepositoryInterface;

class UserAuthRepository implements UserAuthRepositoryInterface
{
    public function register(array $data)
    {
        $user = User::create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],
            'type' => 'employee',
            'city_id' => $data['city_id'],
            'role' => $data['role'],
        ]);

        $user->districts()->attach($data['district_id']);

        if ($data['role'] === 'technician') {
            $user->skills()->attach($data['skills']);
        }

        $days = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        foreach ($days as $day) {
            EmployeeDay::create([
                'employee_id' => $user->id,
                'day_name' => $day,
            ]);
        }
        $user->load('city', 'districts', 'skills');

        $responseUser = $user->only(['id', 'username', 'email', 'role']);
        $responseUser['city'] = $user->city;
        $responseUser['district'] = $user->districts;
        $responseUser['skills'] = $user->skills;

        return $user;
    }


    public function login(array $credentials)
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw new \Exception('Invalid credentials');
        }

        return $user;
    }

    public function updateCustomerData(array $data, $id)
    {
        $user = User::findOrFail($id);
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $user->update($data);

        return $user;
    }


    public function loginCustomer(array $data)
    {
        // Handle login for customers
    }

    public function verifyOtp(array $data)
    {
        // Handle OTP verification
    }

    public function getUserPermissions($id)
    {
        $user = User::with('roles.permissions')->findOrFail($id);
        return $user->roles[0]->permissions;
    }

    public function logout()
    {
        auth()->user()->currentAccessToken()->delete();
    }

    public function changeUserStatus()
    {
        $user = auth()->user();
        $user->update(['status' => 'inactive']);
    }
}
