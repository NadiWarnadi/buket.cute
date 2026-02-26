<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin Toko Bucket Cutie',
            'email' => 'admin@bucketcutie.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);
    }
}