<?php

namespace Tests\Unit;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\AttendanceSetting;
use App\Models\LeaveRequest;
use App\Models\Office;
use App\Models\Overtime;
use App\Models\User;
use App\Services\AttendanceFineService;
use App\Services\DailyAttendanceAuditService;
use App\Services\GeoLocationService;
use App\Services\SelfieStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyAttendanceAuditServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DailyAttendanceAuditService $auditService;
    protected Office $officeMalang;
    protected AttendanceSetting $settingMalang;
    protected User $staffHadir;
    protected User $staffCuti;
    protected User $staffAlpha;

    protected function setUp(): void
    {
        parent::setUp();

        $geoService = new GeoLocationService();
        $fineService = new AttendanceFineService();
        $selfieService = new SelfieStorageService();

        $this->auditService = new DailyAttendanceAuditService($geoService, $fineService, $selfieService);

        $this->officeMalang = Office::create([
            'name' => 'Kantor Malang',
            'latitude' => -7.966620,
            'longitude' => 112.632632,
            'attendance_radius_meter' => 100,
            'timezone' => 'Asia/Jakarta',
            'is_active' => true,
        ]);

        $this->settingMalang = AttendanceSetting::create([
            'office_id' => $this->officeMalang->id,
            'regular_check_in_start' => '08:00:00',
            'regular_check_out_end' => '17:00:00',
            'minimum_regular_minutes' => 360,
            'regular_allowance_amount' => 15000.00,
            'absence_fine_amount' => 50000.00,
            'effective_from' => '2026-01-01',
            'is_active' => true,
        ]);

        $this->staffHadir = User::factory()->create([
            'name' => 'Staff Hadir',
            'office_id' => $this->officeMalang->id,
        ]);

        $this->staffCuti = User::factory()->create([
            'name' => 'Staff Cuti',
            'office_id' => $this->officeMalang->id,
        ]);

        $this->staffAlpha = User::factory()->create([
            'name' => 'Staff Alpha',
            'office_id' => $this->officeMalang->id,
        ]);
    }

    public function test_get_daily_audit_data_identifies_checked_in_checked_out_and_breaks()
    {
        // Staff Hadir attended on 2026-09-22 (Tuesday)
        $att = Attendance::create([
            'user_id' => $this->staffHadir->id,
            'office_id' => $this->officeMalang->id,
            'attendance_setting_id' => $this->settingMalang->id,
            'attendance_date' => '2026-09-22',
            'check_in_at' => '2026-09-22 08:00:00',
            'check_out_at' => '2026-09-22 17:00:00',
            'check_in_latitude' => -7.966620,
            'check_in_longitude' => 112.632632,
            'check_out_latitude' => -7.966620,
            'check_out_longitude' => 112.632632,
            'working_minutes' => 480,
            'allowance_eligible' => true,
            'allowance_amount' => 15000.00,
        ]);

        // Break record
        AttendanceBreak::create([
            'attendance_id' => $att->id,
            'user_id' => $this->staffHadir->id,
            'paused_at' => '2026-09-22 12:00:00',
            'resumed_at' => '2026-09-22 13:00:00',
            'duration_minutes' => 60,
            'reason' => 'lunch',
            'notes' => 'Makan siang',
        ]);

        // Staff Cuti has approved leave
        LeaveRequest::create([
            'user_id' => $this->staffCuti->id,
            'office_id' => $this->officeMalang->id,
            'leave_type' => 'sick',
            'start_date' => '2026-09-21',
            'end_date' => '2026-09-23',
            'total_days' => 3,
            'normal_fine_amount' => 150000.00,
            'adjusted_fine_amount' => 0.00,
            'reason' => 'Sakit demam',
            'status' => 'approved',
        ]);

        $audit = $this->auditService->getDailyAuditData('2026-09-22', $this->officeMalang->id);

        $this->assertEquals('2026-09-22', $audit['date']);
        $this->assertEquals(3, $audit['summary']['total_staff']);
        $this->assertEquals(1, $audit['summary']['total_checked_in']);
        $this->assertEquals(1, $audit['summary']['total_checked_out']);
        $this->assertEquals(1, $audit['summary']['total_leaves']);
        $this->assertEquals(1, $audit['summary']['total_alpha']);
        $this->assertEquals(60, $audit['summary']['total_break_minutes']);

        $hadirLog = collect($audit['staff_logs'])->firstWhere('user_id', $this->staffHadir->id);
        $this->assertEquals('completed', $hadirLog['status']);
        $this->assertEquals(480, $hadirLog['working_minutes']);
        $this->assertEquals(60, $hadirLog['total_break_minutes']);
        $this->assertTrue($hadirLog['check_in_within_radius']);
        $this->assertCount(1, $hadirLog['breaks']);

        $cutiLog = collect($audit['staff_logs'])->firstWhere('user_id', $this->staffCuti->id);
        $this->assertEquals('leave', $cutiLog['status']);
        $this->assertNotNull($cutiLog['leave_request']);

        $alphaLog = collect($audit['staff_logs'])->firstWhere('user_id', $this->staffAlpha->id);
        $this->assertEquals('alpha', $alphaLog['status']);
    }

    public function test_get_daily_audit_data_flags_gps_out_of_radius()
    {
        // Far away coordinates (e.g. Surabaya ~80km away)
        Attendance::create([
            'user_id' => $this->staffHadir->id,
            'office_id' => $this->officeMalang->id,
            'attendance_setting_id' => $this->settingMalang->id,
            'attendance_date' => '2026-09-22',
            'check_in_at' => '2026-09-22 08:00:00',
            'check_in_latitude' => -7.257472,
            'check_in_longitude' => 112.752090,
            'working_minutes' => 0,
            'allowance_eligible' => false,
            'allowance_amount' => 0.00,
        ]);

        $audit = $this->auditService->getDailyAuditData('2026-09-22', $this->officeMalang->id, $this->staffHadir->id);

        $hadirLog = $audit['staff_logs'][0];
        $this->assertEquals('working', $hadirLog['status']);
        $this->assertFalse($hadirLog['check_in_within_radius']);
        $this->assertGreaterThan(50000, $hadirLog['check_in_distance']); // > 50km
    }
}
