<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Never reset an existing administrator during deployment or reseeding.
        if (User::where('role', 'admin')->exists()) return;
        $data = validator(config('initial_admin'), [
            'name' => 'required|string|max:255',
            'phone' => ['required', 'regex:/^[0-9]{9,15}$/D', 'unique:users,nomor_hp'],
            'email' => 'nullable|email|max:255|unique:users,email',
            'password' => 'required|string|min:12|max:72',
        ])->validate();
        User::create(
            [
                'nomor_hp' => $data['phone'],
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'password' => Hash::make($data['password']),
                'role' => 'admin',
                'status' => 'approved',
            ]
        );
    }
}
