<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\Office;
use App\Models\User;
use App\Services\AttendanceFineService;
use App\Services\AttendanceRecapService;
use App\Services\DailyAttendanceAuditService;
use App\Services\GeoLocationService;
use App\Services\SelfieStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportTeamMemberFilterTest extends TestCase
{
    use RefreshDatabase;

    protected Office $office;
    protected AttendanceSetting $setting;
    protected User $admin;
    protected User $teamMember;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'team_member', 'guard_name' => 'web']);

        $this->office = Office::create([
            'name' => 'Office A',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'radius_meters' => 150,
            'timezone' => 'Asia/Jakarta',
            'is_active' => true,
        ]);

        $this->setting = AttendanceSetting::create([
            'office_id' => $this->office->id,
            'regular_check_in_start' => '08:00:00',
            'regular_check_out_end' => '17:00:00',
            'minimum_regular_minutes' => 480,
            'regular_allowance_amount' => 25000.00,
            'absence_fine_amount' => 50000.00,
            'effective_from' => '2026-01-01',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Super Admin User',
            'username' => 'admin_user',
            'email' => 'admin_user@test.com',
            'password' => bcrypt('password'),
            'office_id' => $this->office->id,
        ]);
        $this->admin->assignRole('admin');

        $this->teamMember = User::create([
            'name' => 'Team Member Staff',
            'username' => 'member_user',
            'email' => 'member_user@test.com',
            'password' => bcrypt('password'),
            'office_id' => $this->office->id,
        ]);
        $this->teamMember->assignRole('team_member');
    }

    public function test_attendance_recap_only_includes_team_members(): void
    {
        $recapService = new AttendanceRecapService(new AttendanceFineService());
        $recap = $recapService->getMonthlyRecap(9, 2026, $this->office->id);

        $staffNames = collect($recap['staff_data'])->pluck('name')->all();

        $this->assertContains('Team Member Staff', $staffNames);
        $this->assertNotContains('Super Admin User', $staffNames);
    }

    public function test_daily_attendance_audit_only_includes_team_members(): void
    {
        $auditService = app(DailyAttendanceAuditService::class);
        $audit = $auditService->getDailyAuditData('2026-09-23', $this->office->id);

        $staffNames = collect($audit['staff_logs'])->pluck('name')->all();

        $this->assertContains('Team Member Staff', $staffNames);
        $this->assertNotContains('Super Admin User', $staffNames);
    }
}
