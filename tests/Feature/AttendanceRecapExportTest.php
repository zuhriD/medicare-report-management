<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\Office;
use App\Models\Overtime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AttendanceRecapExportTest extends TestCase
{
    use RefreshDatabase;

    protected Office $office;
    protected AttendanceSetting $setting;
    protected User $admin;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'team_member', 'guard_name' => 'web']);

        $this->office = Office::create([
            'name' => 'Klinik Surabaya',
            'latitude' => -7.257472,
            'longitude' => 112.752088,
            'attendance_radius_meter' => 150,
            'timezone' => 'Asia/Jakarta',
            'is_active' => true,
        ]);

        $this->setting = AttendanceSetting::create([
            'office_id' => $this->office->id,
            'regular_check_in_start' => '08:00:00',
            'regular_check_out_end' => '17:00:00',
            'minimum_regular_minutes' => 480,
            'regular_allowance_amount' => 20000.00,
            'absence_fine_amount' => 50000.00,
            'overtime_check_in_start' => '18:00:00',
            'overtime_check_out_end' => '22:00:00',
            'minimum_overtime_minutes' => 120,
            'overtime_allowance_amount' => 25000.00,
            'effective_from' => '2026-01-01',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin User',
            'username' => 'admin_test',
            'email' => 'admin_test@example.com',
            'password' => bcrypt('password'),
            'office_id' => $this->office->id,
        ]);
        $this->admin->assignRole('admin');

        $this->staff = User::create([
            'name' => 'Budi Santoso',
            'username' => 'budi_santoso',
            'email' => 'budi_santoso@example.com',
            'password' => bcrypt('password'),
            'office_id' => $this->office->id,
        ]);
        $this->staff->assignRole('team_member');
    }

    public function test_staff_pdf_export_renders_successfully()
    {
        // 1. Create an attendance record
        $att = Attendance::create([
            'user_id' => $this->staff->id,
            'office_id' => $this->office->id,
            'attendance_setting_id' => $this->setting->id,
            'attendance_date' => '2026-08-03',
            'check_in_at' => '2026-08-03 08:00:00',
            'check_out_at' => '2026-08-03 17:00:00',
            'working_minutes' => 480,
            'allowance_eligible' => true,
            'allowance_amount' => 20000.00,
        ]);

        // 2. Create an overtime record
        Overtime::create([
            'attendance_id' => $att->id,
            'office_id' => $this->office->id,
            'overtime_date' => '2026-08-03',
            'check_in_at' => '2026-08-03 18:00:00',
            'check_out_at' => '2026-08-03 21:00:00',
            'overtime_minutes' => 180,
            'allowance_eligible' => true,
            'allowance_amount' => 25000.00,
            'notes' => 'Lembur rekap stok obat',
        ]);

        // Request staff PDF
        $response = $this->actingAs($this->admin)->get(route('attendance-recap.staff-pdf', [
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'user_id' => $this->staff->id,
        ]));

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_general_pdf_export_renders_successfully()
    {
        $response = $this->actingAs($this->admin)->get(route('attendance-recap.export', [
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'format' => 'pdf',
        ]));

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }
}
