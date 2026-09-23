<?php

namespace Tests\Feature;

use App\Filament\Resources\AttendanceMonitoringResource;
use App\Filament\Resources\OvertimeMonitoringResource;
use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\Office;
use App\Models\Overtime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AttendanceAndOvertimeEditTest extends TestCase
{
    use RefreshDatabase;

    protected User $hrUser;
    protected User $staffUser;
    protected User $adminUser;
    protected Office $office;
    protected AttendanceSetting $setting;
    protected Attendance $attendance;
    protected Overtime $overtime;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'hr', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'team_member', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->office = Office::create([
            'name' => 'Head Office',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'radius_meters' => 150,
            'timezone' => 'Asia/Jakarta',
            'is_active' => true,
        ]);

        $this->setting = AttendanceSetting::create([
            'office_id' => $this->office->id,
            'regular_check_in_start' => '07:00:00',
            'regular_check_out_end' => '17:00:00',
            'minimum_regular_minutes' => 480,
            'regular_allowance_amount' => 25000.00,
            'absence_fine_amount' => 50000.00,
            'overtime_check_in_start' => '17:00:00',
            'overtime_check_out_end' => '22:00:00',
            'minimum_overtime_minutes' => 120,
            'overtime_allowance_amount' => 20000.00,
            'effective_from' => '2026-01-01',
            'is_active' => true,
        ]);

        $this->staffUser = User::create([
            'name' => 'Staff Member',
            'username' => 'staff1',
            'email' => 'staff1@test.com',
            'password' => bcrypt('password'),
            'office_id' => $this->office->id,
        ]);
        $this->staffUser->assignRole('team_member');

        $this->hrUser = User::create([
            'name' => 'HR Staff',
            'username' => 'hr1',
            'email' => 'hr1@test.com',
            'password' => bcrypt('password'),
            'office_id' => $this->office->id,
        ]);
        $this->hrUser->assignRole('hr');

        $this->adminUser = User::create([
            'name' => 'Administrator',
            'username' => 'admin1',
            'email' => 'admin1@test.com',
            'password' => bcrypt('password'),
            'office_id' => $this->office->id,
        ]);
        $this->adminUser->assignRole('admin');

        $this->attendance = Attendance::create([
            'user_id' => $this->staffUser->id,
            'office_id' => $this->office->id,
            'attendance_setting_id' => $this->setting->id,
            'attendance_date' => '2026-09-23',
            'check_in_at' => '2026-09-23 08:00:00',
            'check_out_at' => '2026-09-23 16:30:00',
            'working_minutes' => 510,
            'allowance_eligible' => true,
            'allowance_amount' => 25000.00,
            'notes' => 'Normal attendance',
        ]);

        $this->overtime = Overtime::create([
            'attendance_id' => $this->attendance->id,
            'overtime_date' => '2026-09-23',
            'check_in_at' => '2026-09-23 17:00:00',
            'check_out_at' => '2026-09-23 19:30:00',
            'overtime_minutes' => 150,
            'allowance_eligible' => true,
            'allowance_amount' => 20000.00,
            'notes' => 'Server maintenance',
        ]);
    }

    public function test_hr_and_admin_can_edit_attendance(): void
    {
        $this->actingAs($this->hrUser);
        $this->assertTrue(AttendanceMonitoringResource::canEdit($this->attendance));

        $this->actingAs($this->adminUser);
        $this->assertTrue(AttendanceMonitoringResource::canEdit($this->attendance));
    }

    public function test_non_hr_cannot_edit_attendance(): void
    {
        $this->actingAs($this->staffUser);
        $this->assertFalse(AttendanceMonitoringResource::canEdit($this->attendance));
    }

    public function test_hr_and_admin_can_edit_overtime(): void
    {
        $this->actingAs($this->hrUser);
        $this->assertTrue(OvertimeMonitoringResource::canEdit($this->overtime));

        $this->actingAs($this->adminUser);
        $this->assertTrue(OvertimeMonitoringResource::canEdit($this->overtime));
    }

    public function test_non_hr_cannot_edit_overtime(): void
    {
        $this->actingAs($this->staffUser);
        $this->assertFalse(OvertimeMonitoringResource::canEdit($this->overtime));
    }
}
