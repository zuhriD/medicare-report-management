<?php

namespace Database\Seeders;

use App\Models\DailyReport;
use App\Models\SubModule;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DailyReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $developerA = User::where('username', 'dev1')->first();
        $sub1a = SubModule::where('name', 'Medicare User App')->first();

        if ($developerA && $sub1a) {
            DailyReport::create([
                'user_id' => $developerA->id,
                'sub_module_id' => $sub1a->id,
                'report_date' => Carbon::now()->toDateString(),
                'description' => 'Implemented patient selection flow.',
            ]);
        }
    }
}
