<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'username' => 'Super Admin',
            'email' => 'dev@dev.com',
            'password' => Hash::make('123456'),
            'type' => 'employee',
            'role' => 'super_admin'
        ]);
    }
}
