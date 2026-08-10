<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Branch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BranchSeeder::class,
            UserSeeder::class,
        ]);
    }
}

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            // Area 1: Jakarta (contoh branch)
            ['area' => '1', 'city' => 'Jakarta', 'name' => 'Kelapa Gading', 'initials' => 'KGA', 'category' => 'H123', 'address' => 'Jl. Boulevard Raya Blok WB II / 7-8 Kelapa Gading Timur, Jakarta Utara 14240'],
        ];

        foreach ($branches as $branch) {
            Branch::create($branch);
        }
    }
}

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            // AlphaAdmin (global, type 1)
            [
                'branch_id' => null,
                'name' => 'AdminWPM',
                'email' => 'adminWPM@example.com',
                'password' => Hash::make('admwpm123'),
                'type' => 1, //Admin
            ],
            [
                'branch_id' => null,
                'name' => 'ManagerWPM',
                'email' => 'manager@example.com',
                'password' => Hash::make('mngr123'),
                'type' => 2, //Manager
            ],
            // User untuk Branch 1 (Area 1, type 0)
            [
                'branch_id' => 1,
                'name' => 'UserWPM',
                'email' => 'user@example.com',
                'password' => Hash::make('user123'),
                'type' => 0, //Kayrawan
            ]
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
