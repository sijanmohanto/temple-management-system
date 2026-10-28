<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            UserSeeder::class,
            ClassificationSeeder::class,
            Phase2DemoSeeder::class,
            EventCategorySeeder::class,
            ChartOfAccountsSeeder::class,
        ]);
    }
}
