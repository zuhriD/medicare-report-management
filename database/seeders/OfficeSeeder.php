<?php

namespace Database\Seeders;

use App\Models\AttendanceSetting;
use App\Models\Office;
use Illuminate\Database\Seeder;

class OfficeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Kuala Lumpur HQ
        $klOffice = Office::firstOrCreate(
            ['name' => 'Kuala Lumpur HQ'],
            [
                'address' => 'Menara Medicare, Level 15, Kuala Lumpur Sentral, Malaysia',
                'latitude' => 3.1340000,
                'longitude' => 101.6860000,
                'attendance_radius_meter' => 100,
                'timezone' => 'Asia/Kuala_Lumpur',
                'is_active' => true,
            ]
        );

        AttendanceSetting::firstOrCreate(
            [
                'office_id' => $klOffice->id,
                'effective_from' => '2026-01-01',
            ],
            [
                'regular_check_in_start' => '08:00:00',
                'regular_check_out_end' => '17:00:00',
                'minimum_regular_minutes' => 360,
                'regular_allowance_amount' => 50.00,
                'overtime_check_in_start' => '18:00:00',
                'overtime_check_out_end' => '22:00:00',
                'minimum_overtime_minutes' => 120,
                'overtime_allowance_amount' => 30.00,
                'effective_until' => null,
                'is_active' => true,
            ]
        );

        // 2. Jakarta Branch
        $jktOffice = Office::firstOrCreate(
            ['name' => 'Jakarta Branch Office'],
            [
                'address' => 'Gedung Medicare, Jl. Sudirman No. 45, Jakarta Selatan, Indonesia',
                'latitude' => -6.2088000,
                'longitude' => 106.8456000,
                'attendance_radius_meter' => 150,
                'timezone' => 'Asia/Jakarta',
                'is_active' => true,
            ]
        );

        AttendanceSetting::firstOrCreate(
            [
                'office_id' => $jktOffice->id,
                'effective_from' => '2026-01-01',
            ],
            [
                'regular_check_in_start' => '08:00:00',
                'regular_check_out_end' => '17:00:00',
                'minimum_regular_minutes' => 360,
                'regular_allowance_amount' => 100000.00,
                'overtime_check_in_start' => '18:00:00',
                'overtime_check_out_end' => '22:00:00',
                'minimum_overtime_minutes' => 120,
                'overtime_allowance_amount' => 50000.00,
                'effective_until' => null,
                'is_active' => true,
            ]
        );
    }
}
