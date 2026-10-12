<?php

namespace Tests\Unit;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\Office;
use App\Models\User;
use App\Services\AttendanceCalculationService;
use App\Services\AttendancePolicyService;
use App\Services\GeoLocationService;
use App\Services\SelfieStorageService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttendanceServicesTest extends TestCase
{
    use RefreshDatabase;

    protected GeoLocationService $geoService;
    protected AttendanceCalculationService $calcService;
    protected AttendancePolicyService $policyService;
    protected SelfieStorageService $selfieService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->geoService = new GeoLocationService();
        $this->calcService = new AttendanceCalculationService();
        $this->policyService = new AttendancePolicyService();
        $this->selfieService = new SelfieStorageService();
    }

    public function test_geolocation_service_calculates_distance_accurately()
    {
        // Coordinates for Monas Jakarta (-6.175392, 106.827153) and nearby point (~100m away)
        $lat1 = -6.175392;
        $lon1 = 106.827153;
        $lat2 = -6.175900;
        $lon2 = 106.827500;

        $distance = $this->geoService->calculateDistance($lat1, $lon1, $lat2, $lon2);
        $this->assertGreaterThan(0, $distance);
        $this->assertLessThan(200, $distance);

        $this->assertTrue($this->geoService->isWithinRadius($lat1, $lon1, $lat2, $lon2, 150));
        $this->assertFalse($this->geoService->isWithinRadius($lat1, $lon1, $lat2, $lon2, 20));
    }

    public function test_attendance_calculation_service_evaluates_regular_allowance_threshold()
    {
        $policy = new AttendanceSetting([
            'minimum_regular_minutes' => 360,
            'regular_allowance_amount' => 50.00,
        ]);

        // Case 1: 5 hours 59 minutes (359 minutes) -> Not eligible
        $res1 = $this->calcService->evaluateRegularAllowance(359, $policy);
        $this->assertFalse($res1['allowance_eligible']);
        $this->assertEquals(0.0, $res1['allowance_amount']);

        // Case 2: Exactly 6 hours (360 minutes) -> Eligible
        $res2 = $this->calcService->evaluateRegularAllowance(360, $policy);
        $this->assertTrue($res2['allowance_eligible']);
        $this->assertEquals(50.0, $res2['allowance_amount']);

        // Case 3: 7 hours (420 minutes) -> Eligible
        $res3 = $this->calcService->evaluateRegularAllowance(420, $policy);
        $this->assertTrue($res3['allowance_eligible']);
        $this->assertEquals(50.0, (float) $res3['allowance_amount']);
    }

    public function test_attendance_calculation_service_deducts_break_minutes()
    {
        $checkIn = Carbon::parse('2026-09-22 08:00:00');
        $checkOut = Carbon::parse('2026-09-22 15:00:00'); // 7 hours = 420 minutes

        // 1. Without break -> 420 minutes
        $minutesWithoutBreak = $this->calcService->calculateWorkingMinutes($checkIn, $checkOut, 0);
        $this->assertEquals(420, $minutesWithoutBreak);

        // 2. With 60 minutes break -> 360 minutes
        $minutesWithBreak = $this->calcService->calculateWorkingMinutes($checkIn, $checkOut, 60);
        $this->assertEquals(360, $minutesWithBreak);

        // 3. Break calculation
        $pauseAt = Carbon::parse('2026-09-22 12:00:00');
        $resumeAt = Carbon::parse('2026-09-22 12:45:00');
        $breakMinutes = $this->calcService->calculateBreakMinutes($pauseAt, $resumeAt);
        $this->assertEquals(45, $breakMinutes);
    }

    public function test_attendance_and_overtime_calculation_caps_checkout_to_policy_end_times()
    {
        $policy = new AttendanceSetting([
            'regular_check_out_end' => '17:00:00',
            'overtime_check_out_end' => '22:00:00',
        ]);

        // Regular: Check in 08:00, Check out 18:00 -> Capped at 17:00 (9 hours = 540 minutes, NOT 10 hours = 600 minutes)
        $regularIn = Carbon::parse('2026-09-22 08:00:00');
        $regularOut = Carbon::parse('2026-09-22 18:00:00');
        $workingMinutes = $this->calcService->calculateWorkingMinutes($regularIn, $regularOut, 0, $policy);
        $this->assertEquals(540, $workingMinutes); // 17:00 - 08:00 = 9h = 540m

        // Regular: Check in 08:00, Check out 16:30 -> Not capped (8.5h = 510 minutes)
        $regularOutEarly = Carbon::parse('2026-09-22 16:30:00');
        $workingMinutesEarly = $this->calcService->calculateWorkingMinutes($regularIn, $regularOutEarly, 0, $policy);
        $this->assertEquals(510, $workingMinutesEarly);

        // Overtime: Check in 18:00, Check out 23:30 -> Capped at 22:00 (4 hours = 240 minutes, NOT 5.5h = 330 minutes)
        $otIn = Carbon::parse('2026-09-22 18:00:00');
        $otOut = Carbon::parse('2026-09-22 23:30:00');
        $otMinutes = $this->calcService->calculateOvertimeMinutes($otIn, $otOut, $policy);
        $this->assertEquals(240, $otMinutes); // 22:00 - 18:00 = 4h = 240m
    }

    public function test_attendance_calculation_handles_office_timezone_differences_correctly()
    {
        $office = Office::create([
            'name' => 'Jakarta Office',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'attendance_radius_meter' => 150,
            'timezone' => 'Asia/Jakarta',
            'is_active' => true,
        ]);

        $policy = AttendanceSetting::create([
            'office_id' => $office->id,
            'regular_check_in_start' => '08:00:00',
            'regular_check_out_end' => '17:00:00',
            'minimum_regular_minutes' => 360,
            'regular_allowance_amount' => 70000.00,
            'overtime_check_in_start' => '18:00:00',
            'overtime_check_out_end' => '22:00:00',
            'minimum_overtime_minutes' => 120,
            'overtime_allowance_amount' => 35000.00,
            'effective_from' => '2026-01-01',
            'is_active' => true,
        ]);

        // Jakarta staff: check-in 10:56:15 WIB, check-out 16:57:25 WIB
        // Even if Carbon timestamps are created in UTC (03:56:15 UTC & 09:57:25 UTC) or Asia/Kuala_Lumpur (11:56:15 & 17:57:25)
        $checkInUTC = Carbon::parse('2026-09-29 03:56:15', 'UTC');
        $checkOutUTC = Carbon::parse('2026-09-29 09:57:25', 'UTC');

        $workingMinutes = $this->calcService->calculateWorkingMinutes($checkInUTC, $checkOutUTC, 0, $policy);
        $this->assertEquals(361, $workingMinutes); // 6h 1m = 361 minutes, NOT capped to 303 minutes

        $allowance = $this->calcService->evaluateRegularAllowance($workingMinutes, $policy);
        $this->assertTrue($allowance['allowance_eligible']);
        $this->assertEquals(70000.00, $allowance['allowance_amount']);
    }

    public function test_policy_service_validates_pause_and_resume_transitions()
    {
        $office = Office::create([
            'name' => 'HQ Office',
            'latitude' => 3.134,
            'longitude' => 101.686,
            'attendance_radius_meter' => 100,
            'timezone' => 'Asia/Kuala_Lumpur',
            'is_active' => true,
        ]);

        $policy = AttendanceSetting::create([
            'office_id' => $office->id,
            'regular_check_in_start' => '08:00:00',
            'regular_check_out_end' => '17:00:00',
            'minimum_regular_minutes' => 360,
            'regular_allowance_amount' => 50.00,
            'overtime_check_in_start' => '18:00:00',
            'overtime_check_out_end' => '22:00:00',
            'minimum_overtime_minutes' => 120,
            'overtime_allowance_amount' => 30.00,
            'effective_from' => '2026-01-01',
            'is_active' => true,
        ]);

        $user = User::create([
            'office_id' => $office->id,
            'name' => 'Test User',
            'username' => 'testuser2',
            'email' => 'test2@medicare.com',
            'password' => bcrypt('password'),
        ]);

        // Case 1: No attendance -> cannot pause
        $check1 = $this->policyService->canPauseAttendance(null);
        $this->assertFalse($check1['allowed']);

        // Case 2: Checked in attendance -> can pause
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'office_id' => $office->id,
            'attendance_setting_id' => $policy->id,
            'attendance_date' => '2026-09-22',
            'check_in_at' => Carbon::now(),
        ]);
        $check2 = $this->policyService->canPauseAttendance($attendance);
        $this->assertTrue($check2['allowed']);

        // Case 3: Create active break -> cannot pause again, but can resume
        $break = $attendance->breaks()->create([
            'user_id' => $user->id,
            'reason' => 'Istirahat',
            'paused_at' => Carbon::now(),
        ]);
        $this->assertTrue($attendance->isPaused());
        $check3 = $this->policyService->canPauseAttendance($attendance);
        $this->assertFalse($check3['allowed']);
        $checkResume = $this->policyService->canResumeAttendance($attendance);
        $this->assertTrue($checkResume['allowed']);

        // Case 4: Resume break -> can pause again, cannot resume again
        $break->update(['resumed_at' => Carbon::now(), 'duration_minutes' => 30]);
        $attendance->refresh();
        $this->assertFalse($attendance->isPaused());
        $check4 = $this->policyService->canPauseAttendance($attendance);
        $this->assertTrue($check4['allowed']);
        $checkResume2 = $this->policyService->canResumeAttendance($attendance);
        $this->assertFalse($checkResume2['allowed']);
    }

    public function test_attendance_calculation_service_evaluates_overtime_allowance_threshold()
    {
        $policy = new AttendanceSetting([
            'minimum_overtime_minutes' => 120,
            'overtime_allowance_amount' => 30.00,
        ]);

        // Case 1: 119 minutes -> Not eligible
        $res1 = $this->calcService->evaluateOvertimeAllowance(119, $policy);
        $this->assertFalse($res1['allowance_eligible']);
        $this->assertEquals(0.0, $res1['allowance_amount']);

        // Case 2: 120 minutes -> Eligible
        $res2 = $this->calcService->evaluateOvertimeAllowance(120, $policy);
        $this->assertTrue($res2['allowance_eligible']);
        $this->assertEquals(30.0, $res2['allowance_amount']);
    }

    public function test_overtime_checkin_validation_enforces_prerequisites()
    {
        $office = Office::create([
            'name' => 'HQ Office',
            'latitude' => 3.134,
            'longitude' => 101.686,
            'attendance_radius_meter' => 100,
            'timezone' => 'Asia/Kuala_Lumpur',
            'is_active' => true,
        ]);

        $policy = AttendanceSetting::create([
            'office_id' => $office->id,
            'regular_check_in_start' => '08:00:00',
            'regular_check_out_end' => '17:00:00',
            'minimum_regular_minutes' => 360,
            'regular_allowance_amount' => 50.00,
            'overtime_check_in_start' => '18:00:00',
            'overtime_check_out_end' => '22:00:00',
            'minimum_overtime_minutes' => 120,
            'overtime_allowance_amount' => 30.00,
            'effective_from' => '2026-01-01',
            'is_active' => true,
        ]);

        $user = User::create([
            'office_id' => $office->id,
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@medicare.com',
            'password' => bcrypt('password'),
        ]);

        $nowInWindow = Carbon::parse('2026-09-21 18:30:00', 'Asia/Kuala_Lumpur');

        // Case 1: Attendance not checked out yet -> OT Rejected
        $attendanceOngoing = Attendance::create([
            'user_id' => $user->id,
            'office_id' => $office->id,
            'attendance_setting_id' => $policy->id,
            'attendance_date' => '2026-09-21',
            'check_in_at' => '2026-09-21 08:00:00',
            'working_minutes' => 0,
        ]);
        $check1 = $this->policyService->canOvertimeCheckIn($office, $policy, $attendanceOngoing, $nowInWindow);
        $this->assertFalse($check1['allowed']);

        // Case 2: Checked out but duration < 360 minutes -> OT Rejected
        $attendanceOngoing->update([
            'check_out_at' => '2026-09-21 13:00:00',
            'working_minutes' => 300,
        ]);
        $check2 = $this->policyService->canOvertimeCheckIn($office, $policy, $attendanceOngoing, $nowInWindow);
        $this->assertFalse($check2['allowed']);

        // Case 3: Checked out with 360 minutes in OT window -> OT Allowed
        $attendanceOngoing->update([
            'check_out_at' => '2026-09-21 14:00:00',
            'working_minutes' => 360,
        ]);
        $check3 = $this->policyService->canOvertimeCheckIn($office, $policy, $attendanceOngoing, $nowInWindow);
        $this->assertTrue($check3['allowed']);
    }

    public function test_selfie_storage_service_stores_and_converts_live_selfie()
    {
        Storage::fake('local');

        // 1x1 transparent GIF Base64 data url
        $base64Sample = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

        $storedPath = $this->selfieService->storeSelfie(
            $base64Sample,
            userId: 99,
            type: 'check_in',
            date: Carbon::parse('2026-09-21')
        );

        $this->assertStringStartsWith('attendance-selfies/2026/09/21/99/check_in_', $storedPath);
        $this->assertStringEndsWith('.webp', $storedPath);
        $this->assertTrue($this->selfieService->exists($storedPath));
    }

    public function test_punctuality_evaluation_identifies_on_time_and_late_attendances()
    {
        $policy = new AttendanceSetting([
            'regular_check_in_start' => '08:00:00',
        ]);

        // Case 1: Early check-in (07:45:00) -> ON_TIME, late_minutes = 0
        $early = Carbon::parse('2026-10-12 07:45:00');
        $evalEarly = $this->calcService->evaluatePunctuality($early, $policy);
        $this->assertEquals(AttendanceStatus::ON_TIME, $evalEarly['status']);
        $this->assertFalse($evalEarly['is_late']);
        $this->assertEquals(0, $evalEarly['late_minutes']);

        // Case 2: Exactly on schedule (08:00:00) -> ON_TIME, late_minutes = 0
        $exact = Carbon::parse('2026-10-12 08:00:00');
        $evalExact = $this->calcService->evaluatePunctuality($exact, $policy);
        $this->assertEquals(AttendanceStatus::ON_TIME, $evalExact['status']);
        $this->assertFalse($evalExact['is_late']);
        $this->assertEquals(0, $evalExact['late_minutes']);

        // Case 3: Late check-in (08:15:30) -> LATE, late_minutes = 16 (ceil)
        $late = Carbon::parse('2026-10-12 08:15:30');
        $evalLate = $this->calcService->evaluatePunctuality($late, $policy);
        $this->assertEquals(AttendanceStatus::LATE, $evalLate['status']);
        $this->assertTrue($evalLate['is_late']);
        $this->assertEquals(16, $evalLate['late_minutes']);

        // Case 4: Late check-in (08:15:00) -> LATE, late_minutes = 15
        $late15 = Carbon::parse('2026-10-12 08:15:00');
        $evalLate15 = $this->calcService->evaluatePunctuality($late15, $policy);
        $this->assertEquals(AttendanceStatus::LATE, $evalLate15['status']);
        $this->assertTrue($evalLate15['is_late']);
        $this->assertEquals(15, $evalLate15['late_minutes']);

        // Case 5: Policy with 30-minute late tolerance
        $policyWithTolerance = new AttendanceSetting([
            'regular_check_in_start' => '08:00:00',
            'late_tolerance_minute' => 30,
        ]);

        // Check in at 08:15 (within tolerance) -> ON_TIME, late_minutes = 0
        $eval815 = $this->calcService->evaluatePunctuality(Carbon::parse('2026-10-12 08:15:00'), $policyWithTolerance);
        $this->assertEquals(AttendanceStatus::ON_TIME, $eval815['status']);
        $this->assertEquals(0, $eval815['late_minutes']);

        // Check in at 08:30 (exactly at tolerance cutoff) -> ON_TIME, late_minutes = 0
        $eval830 = $this->calcService->evaluatePunctuality(Carbon::parse('2026-10-12 08:30:00'), $policyWithTolerance);
        $this->assertEquals(AttendanceStatus::ON_TIME, $eval830['status']);
        $this->assertEquals(0, $eval830['late_minutes']);

        // Check in at 08:35 (5 minutes past 30m tolerance) -> LATE, late_minutes = 5
        $eval835 = $this->calcService->evaluatePunctuality(Carbon::parse('2026-10-12 08:35:00'), $policyWithTolerance);
        $this->assertEquals(AttendanceStatus::LATE, $eval835['status']);
        $this->assertTrue($eval835['is_late']);
        $this->assertEquals(5, $eval835['late_minutes']);
    }

    public function test_early_check_in_working_duration_starts_from_regular_check_in_start()
    {
        $policy = new AttendanceSetting([
            'regular_check_in_start' => '08:00:00',
            'regular_check_out_end' => '17:00:00',
        ]);

        // Check in early at 07:45, check out at 15:00.
        // Gross time without policy = 7h 15m (435 minutes).
        // With policy snapping to 08:00:00 = 7h 00m (420 minutes).
        $checkIn = Carbon::parse('2026-10-12 07:45:00');
        $checkOut = Carbon::parse('2026-10-12 15:00:00');

        $workingMinutes = $this->calcService->calculateWorkingMinutes($checkIn, $checkOut, 0, $policy);
        $this->assertEquals(420, $workingMinutes);
    }

    public function test_early_check_in_is_allowed_in_policy_service()
    {
        $office = Office::create([
            'name' => 'HQ Office',
            'latitude' => 3.134,
            'longitude' => 101.686,
            'attendance_radius_meter' => 100,
            'timezone' => 'Asia/Kuala_Lumpur',
            'is_active' => true,
        ]);

        $policy = AttendanceSetting::create([
            'office_id' => $office->id,
            'regular_check_in_start' => '08:00:00',
            'regular_check_out_end' => '17:00:00',
            'minimum_regular_minutes' => 360,
            'regular_allowance_amount' => 50.00,
            'overtime_check_in_start' => '18:00:00',
            'overtime_check_out_end' => '22:00:00',
            'minimum_overtime_minutes' => 120,
            'overtime_allowance_amount' => 30.00,
            'effective_from' => '2026-01-01',
            'is_active' => true,
        ]);

        // Checking in early at 07:30 (before 08:00) is allowed
        $earlyMorning = Carbon::parse('2026-10-12 07:30:00', 'Asia/Kuala_Lumpur');
        $check = $this->policyService->canRegularCheckIn($office, $policy, $earlyMorning);
        $this->assertTrue($check['allowed']);

        // Checking in late is also allowed (recorded as late status)
        $afterHours = Carbon::parse('2026-10-12 17:30:00', 'Asia/Kuala_Lumpur');
        $checkAfter = $this->policyService->canRegularCheckIn($office, $policy, $afterHours);
        $this->assertTrue($checkAfter['allowed']);
    }
}
