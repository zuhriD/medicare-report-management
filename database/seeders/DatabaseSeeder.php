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
            RolePermissionSeeder::class,
            OfficeSeeder::class,
            SectionSeeder::class,
            UserSeeder::class,
            ModuleSeeder::class,
            DailyReportSeeder::class,
        ]);
    }
}
