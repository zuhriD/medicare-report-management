<?php

namespace Database\Seeders;

use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $webSection = Section::where('name', 'Web Developer')->first();
        $backendSection = Section::where('name', 'Backend Developer')->first();
        $mobileSection = Section::where('name', 'Mobile Developer')->first();

        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'username' => 'admin',
            'password' => bcrypt('admin123'),
        ]);
        if ($webSection) $admin->sections()->attach($webSection->id);
        $admin->assignRole('super_admin');

        $leadPrimary = User::factory()->create([
            'name' => 'Primary Lead',
            'email' => 'lead@gmail.com',
            'username' => 'lead1',
            'password' => bcrypt('lead123'),
        ]);
        if ($backendSection) $leadPrimary->sections()->attach($backendSection->id);
        $leadPrimary->assignRole('lead');

        $developerA = User::factory()->create([
            'name' => 'Mobile Developer',
            'email' => 'mobile_dev@gmail.com',
            'username' => 'dev1',
            'password' => bcrypt('mobiledev123'),
        ]);
        if ($mobileSection) $developerA->sections()->attach($mobileSection->id);
        $developerA->assignRole('team_member');
    }
}
