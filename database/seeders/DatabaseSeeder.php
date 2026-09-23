<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DepartmentSeeder::class,
            RolePermissionSeeder::class,
            ServiceSeeder::class,
            StatusSeeder::class,
            EmployeeSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}