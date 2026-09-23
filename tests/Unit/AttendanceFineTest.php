<?php

namespace Tests\Unit;

use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\LeaveRequest;
use App\Models\Office;
use App\Models\User;
use App\Services\AttendanceFineService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceFineTest extends TestCase
{
    use RefreshDatabase;

    protected AttendanceFineService $fineService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fineService = new AttendanceFineService();
    }

    public function test_get_daily_fine_rate_returns_office_setting_or_default()
    {
        $office = Office::create([
            'name' => 'Kantor Malang',
            'latitude' => -7.966620,
            'longitude' => 112.632632,
            'attendance_radius_meter' => 100,
            'timezone' => 'Asia/Jakarta',
            'is_active' => true,
        ]);

        // When no active setting, default is 50.000
        $this->assertEquals(50000.00, $this->fineService->getDailyFineRate($office));

        // When policy exists with 50.000 fine
        AttendanceSetting::create([
            'office_id' => $office->id,
            'regular_check_in_start' => '08:00:00',
            'regular_check_out_end' => '17:00:00',
            'minimum_regular_minutes' => 360,
            'regular_allowance_amount' => 15000.00,
            'absence_fine_amount' => 50000.00,
            'effective_from' => '2026-01-01',
            'is_active' => true,
        ]);

        $this->assertEquals(50000.00, $this->fineService->getDailyFineRate($office, '2026-09-22'));
    }

    public function test_calculate_working_days_excludes_sundays()
    {
        // Friday to Monday: Fri (1), Sat (2), Sun (exclude), Mon (3) -> 3 days
        $start = Carbon::parse('2026-09-25'); // Friday
        $end = Carbon::parse('2026-09-28');   // Monday

        $workingDays = $this->fineService->calculateWorkingDays($start, $end);
        $this->assertEquals(3, $workingDays);
    }

    public function test_calculate_leave_normal_fine_multiplies_working_days_by_rate()
    {
        $office = Office::create([
            'name' => 'Kantor Malang',
            'latitude' => -7.966620,
            'longitude' => 112.632632,
            'attendance_radius_meter' => 100,
            'timezone' => 'Asia/Jakarta',
            'is_active' => true,
        ]);

        AttendanceSetting::create([
            'office_id' => $office->id,
            'regular_check_in_start' => '08:00:00',
            'regular_check_out_end' => '17:00:00',
            'minimum_regular_minutes' => 360,
            'regular_allowance_amount' => 15000.00,
            'absence_fine_amount' => 50000.00,
            'effective_from' => '2026-01-01',
            'is_active' => true,
        ]);

        // 3 working days (e.g. Wed to Fri)
        $start = Carbon::parse('2026-09-23'); // Wednesday
        $end = Carbon::parse('2026-09-25');   // Friday

        $result = $this->fineService->calculateLeaveNormalFine($office, $start, $end);

        $this->assertEquals(3, $result['total_days']);
        $this->assertEquals(50000.00, $result['daily_fine_rate']);
        $this->assertEquals(150000.00, $result['normal_fine_amount']);
    }

    public function test_office_with_zero_fine_rate_like_malaysia_calculates_zero_normal_fine()
    {
        $officeMalaysia = Office::create([
            'name' => 'Kantor Malaysia',
            'latitude' => 3.139003,
            'longitude' => 101.686855,
            'attendance_radius_meter' => 100,
            'timezone' => 'Asia/Kuala_Lumpur',
            'is_active' => true,
        ]);

        AttendanceSetting::create([
            'office_id' => $officeMalaysia->id,
            'regular_check_in_start' => '08:00:00',
            'regular_check_out_end' => '17:00:00',
            'minimum_regular_minutes' => 360,
            'regular_allowance_amount' => 0.00,
            'absence_fine_amount' => 0.00, // No fine in Malaysia
            'effective_from' => '2026-01-01',
            'is_active' => true,
        ]);

        $start = Carbon::parse('2026-09-23');
        $end = Carbon::parse('2026-09-25');

        $result = $this->fineService->calculateLeaveNormalFine($officeMalaysia, $start, $end);

        $this->assertEquals(3, $result['total_days']);
        $this->assertEquals(0.00, $result['daily_fine_rate']);
        $this->assertEquals(0.00, $result['normal_fine_amount']);
    }

    public function test_calculate_user_fines_for_period_with_present_approved_leave_and_alpha()
    {
        $office = Office::create([
            'name' => 'Kantor Malang',
            'latitude' => -7.966620,
            'longitude' => 112.632632,
            'attendance_radius_meter' => 100,
            'timezone' => 'Asia/Jakarta',
            'is_active' => true,
        ]);

        $setting = AttendanceSetting::create([
            'office_id' => $office->id,
            'regular_check_in_start' => '08:00:00',
            'regular_check_out_end' => '17:00:00',
            'minimum_regular_minutes' => 360,
            'regular_allowance_amount' => 15000.00,
            'absence_fine_amount' => 50000.00,
            'effective_from' => '2026-01-01',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'office_id' => $office->id,
            'name' => 'Staff Alpha Test',
        ]);

        // Day 1 (Monday 2026-09-07): Present
        Attendance::create([
            'user_id' => $user->id,
            'office_id' => $office->id,
            'attendance_setting_id' => $setting->id,
            'attendance_date' => '2026-09-07',
            'check_in_at' => '2026-09-07 08:00:00',
            'check_out_at' => '2026-09-07 17:00:00',
            'working_minutes' => 480,
            'allowance_eligible' => true,
            'allowance_amount' => 15000.00,
        ]);

        // Day 2-4 (Tue to Thu 2026-09-08 to 2026-09-10): Approved Leave (3 days) with relief fine Rp 50.000
        LeaveRequest::create([
            'user_id' => $user->id,
            'office_id' => $office->id,
            'leave_type' => 'sick',
            'start_date' => '2026-09-08',
            'end_date' => '2026-09-10',
            'total_days' => 3,
            'reason' => 'Sakit demam',
            'status' => 'approved',
            'normal_fine_amount' => 150000.00,
            'adjusted_fine_amount' => 50000.00, // Relief applied
            'approved_at' => '2026-09-08 07:00:00',
        ]);

        // Day 5 (Friday 2026-09-11): No attendance, no leave (Alpha -> Fine 50.000)

        $start = Carbon::parse('2026-09-07');
        $end = Carbon::parse('2026-09-11');

        $result = $this->fineService->calculateUserFinesForPeriod($user, $start, $end);

        $this->assertEquals(1, $result['total_alpha_days']);
        $this->assertEquals(50000.00, $result['total_alpha_fine']);
        $this->assertEquals(50000.00, $result['total_approved_leave_fine']);
        $this->assertEquals(100000.00, $result['total_fine']);
    }
}
