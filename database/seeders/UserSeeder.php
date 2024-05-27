<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'first_name' => 'Rohan',
            'last_name' => 'Admin',
            'email' => 'rohan@gmail.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'about_me' => "Hey I'm Rohan",
            'role' => 'admin'
        ]);
        User::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'johndoe@gmail.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'about_me' => "Hey I'm John",
            'role' => 'user'
        ]);
        User::create([
            'first_name' => 'Alex',
            'last_name' => 'Doe',
            'email' => 'alexdoe@gmail.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'about_me' => "Hey I'm Alex",
            'role' => 'user'
        ]);
    }
}
