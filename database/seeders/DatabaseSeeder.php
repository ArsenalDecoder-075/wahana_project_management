<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Branch;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BranchSeeder::class,
            UserSeeder::class,
            CategorySeeder::class,
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
            // Siapkan 3
            [
                'branch_id' => 1,
                'name' => 'UserWPM1',
                'email' => 'user1@example.com',
                'password' => Hash::make('user123a1'),
                'type' => 0, //Kayrawan
            ],
            [
                'branch_id' => 1,
                'name' => 'UserWPM2',
                'email' => 'user2@example.com',
                'password' => Hash::make('user123a2'),
                'type' => 0, //Kayrawan
            ],
            [
                'branch_id' => 1,
                'name' => 'UserWPM3',
                'email' => 'user3@example.com',
                'password' => Hash::make('user123a3'),
                'type' => 0, //Kayrawan
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Web Development',
                'description' => 'Proyek pengembangan website',
                'icon' => 'fa-globe',
                'color' => '#4F46E5'
            ],
            [
                'name' => 'Mobile Development',
                'description' => 'Proyek pengembangan aplikasi mobile',
                'icon' => 'fa-mobile-alt',
                'color' => '#7C3AED'
            ],
            [
                'name' => 'UI/UX Design',
                'description' => 'Proyek desain antarmuka dan pengalaman pengguna',
                'icon' => 'fa-paint-brush',
                'color' => '#EC4899'
            ],
            [
                'name' => 'DevOps',
                'description' => 'Proyek infrastruktur dan deployment',
                'icon' => 'fa-server',
                'color' => '#F59E0B'
            ],
            [
                'name' => 'Data Science',
                'description' => 'Proyek analisis dan pengolahan data',
                'icon' => 'fa-database',
                'color' => '#10B981'
            ],
            [
                'name' => 'Research & Development',
                'description' => 'Proyek penelitian dan pengembangan',
                'icon' => 'fa-flask',
                'color' => '#EF4444'
            ]
        ];

        foreach ($categories as $category) {
            Category::create([
                'name' => $category['name'],
                'description' => $category['description'],
                'is_active' => true
            ]);
        }
    }
}
