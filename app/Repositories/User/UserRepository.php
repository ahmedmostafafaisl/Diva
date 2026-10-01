<?php

namespace App\Repositories\User;

use App\Models\User;
use App\Models\EmployeeDay;
use Illuminate\Support\Facades\Hash;
use App\Repositories\Interfaces\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    public function getAllUsers()
    {
        return User::all();
    }

    public function getUserById($id)
    {
        return User::with('skills')->findOrFail($id);
    }

    public function createUser(array $data)
    {
        // return User::create($data);
        $skills = isset($data['skills']) ? $data['skills'] : [];
        // unset($data['skills']);

        // Create user and attach skills
        $user = User::create($data);

        if (!empty($skills)) {
            $user->skills()->sync($skills);
        }
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

        return $user->load('city', 'districts', 'skills');
    }

    public function updateUser($id, array $data)
    {
        // Extract skills and districts from the data if they exist
        $skills = isset($data['skills']) ? $data['skills'] : [];
        $districtIds = isset($data['district_id']) ? (array) $data['district_id'] : [];

        // Remove skills and district_id from the data array
        unset($data['skills'], $data['district_id']);

        // Find the user by ID and update the details
        $user = User::findOrFail($id);
        $user->update($data);

        // Handle skills
        if (!empty($skills)) {
            $user->skills()->sync([]); // Remove all skills
            $user->skills()->sync($skills); // Attach the new skills
        }
        // Handle skills
        if (empty($skills)) {
            $user->skills()->sync([]); // Remove all skills
            // $user->skills()->sync($skills); // Attach the new skills
        }

        // Handle districts
        if (!empty($districtIds)) {
            $user->districts()->sync([]); // Remove all districts
            $user->districts()->sync($districtIds); // Attach the new districts
        }

        // Return the user with skills and districts loaded
        return $user->load(['skills', 'districts']);
    }


    public function updateUserPassword($id, $password)
    {
        $user = User::findOrFail($id);

        // $password = Hash::make($password);
        $user->update(['password' => $password]);
        $user->save();
        return $user;
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        // Detach all skills
        $user->skills()->detach();
        // Delete user
        $user->delete();
    }
}
