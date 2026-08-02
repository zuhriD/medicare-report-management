<?php

namespace Database\Seeders;

use App\Models\DailyReport;
use App\Models\Module;
use App\Models\SubModule;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        $sections = collect([
            'Mobile Developer',
            'Web Developer',
            'Backend Developer',
            'UI/UX Designer',
            'Data Entry',
        ])->mapWithKeys(function (string $sectionName): array {
            $section = Section::query()->create(['name' => $sectionName]);
            return [$sectionName => $section];
        });

        $admin = User::factory()->create([
            'name' => 'MRM Admin',
            'email' => 'admin@mrm.test',
            'username' => 'admin',
            'section_id' => $sections['Web Developer']->id,
        ]);
        $admin->assignRole('super_admin');

        $leadPrimary = User::factory()->create([
            'name' => 'Primary Lead',
            'email' => 'lead1@mrm.test',
            'username' => 'lead1',
            'section_id' => $sections['Backend Developer']->id,
        ]);
        $leadPrimary->assignRole('lead');

        $developerA = User::factory()->create([
            'name' => 'Mobile Dev One',
            'email' => 'dev1@mrm.test',
            'username' => 'dev1',
            'section_id' => $sections['Mobile Developer']->id,
        ]);
        $developerA->assignRole('team_member');

        $module1 = Module::create(['name' => 'Home Nursing', 'type' => 'module']);
        $sub1a = SubModule::create(['module_id' => $module1->id, 'name' => 'Medicare User App']);
        $sub1b = SubModule::create(['module_id' => $module1->id, 'name' => 'Admin Panel']);

        $module2 = Module::create(['name' => 'Doctor Home Visit', 'type' => 'module']);
        $sub2a = SubModule::create(['module_id' => $module2->id, 'name' => 'API']);

        DailyReport::create([
            'user_id' => $developerA->id,
            'sub_module_id' => $sub1a->id,
            'report_date' => Carbon::now()->toDateString(),
            'description' => 'Implemented patient selection flow.',
        ]);
    }
}
