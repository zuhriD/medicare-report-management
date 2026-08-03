<?php

namespace Database\Seeders;

use App\Models\Section;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        collect([
            'Mobile Developer',
            'Web Developer',
            'Backend Developer',
            'UI/UX Designer',
            'Data Entry',
        ])->each(function (string $sectionName) {
            Section::query()->create(['name' => $sectionName]);
        });
    }
}
