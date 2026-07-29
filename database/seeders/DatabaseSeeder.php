<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class,
            ClientSeeder::class,
            MonthlySummarySeeder::class,
            CollectionSeeder::class,
            RiskSeeder::class,
            DataDictionarySeeder::class,
            ClientLogSeeder::class,
        ]);
    }
}
