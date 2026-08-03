<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\SubModule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $module1 = Module::create(['name' => 'Home Nursing', 'type' => 'module']);
        SubModule::create(['module_id' => $module1->id, 'name' => 'Medicare User App']);
        SubModule::create(['module_id' => $module1->id, 'name' => 'Admin Panel']);

        $module2 = Module::create(['name' => 'Doctor Home Visit', 'type' => 'module']);
        SubModule::create(['module_id' => $module2->id, 'name' => 'API']);
    }
}
