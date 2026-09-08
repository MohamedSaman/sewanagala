<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Staff User
        // User::create([
        //     'name' => 'Staff',
        //     'email' => 'staff@gmail.com',
        //     'password' => Hash::make('staff@1213'),
        //     'role' => 'staff',
        //     'contact' => '0776657107',
        // ]);

        // Create Admin User
        User::updateOrCreate(
            ['email' => 'test@gmail.com'],
            [
                'name' => 'Test',
                'password' => Hash::make('admin@1213'),
                'role' => 'admin',
                'contact' => '0717894272',
            ]
        );

        // Create Sewanagala User (30% Masked Mode)
        User::updateOrCreate(
            ['email' => 'sewanagala@gmail.com'],
            [
                'name' => 'sewanagala',
                'password' => Hash::make('admin@1213'),
                'role' => 'admin',
                'contact' => '0771234567',
            ]
        );
    }
}
