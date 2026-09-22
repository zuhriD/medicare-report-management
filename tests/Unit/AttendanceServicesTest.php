<?php

namespace Tests\Unit;

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
        $this->assertEquals(50.0, $res3['allowance_amount']);
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
}
