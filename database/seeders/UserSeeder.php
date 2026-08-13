<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'thakuranjali8431@gmail.com'],
            [
                'username' => 'Anjali Thakur',
                'password' => Hash::make('12345678'),
                'email_verified_at' => null,
            ]
        );

        User::updateOrCreate(
            ['email' => 'testingdemo5501@gmail.com'],
            [
                'username' => 'testing demo',
                'password' => Hash::make('12345678'),
                'email_verified_at' => null,
            ]
        );

        User::updateOrCreate(
            ['email' => 'geniusunil@gmail.com'],
            [
                'username' => 'Sunil Sharma',
                'password' => Hash::make('12345678'),
                'email_verified_at' => null,
            ]
        );

        User::updateOrCreate(
            ['email' => 'sharmarajesh3578@gmail.com'],
            [
                'username' => 'Rajesh Sharma',
                'password' => Hash::make('12345678'),
                'email_verified_at' => null,
            ]
        );
    }
}