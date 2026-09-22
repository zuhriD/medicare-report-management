<?php

namespace Tests\Unit;

use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\LeaveRequest;
use App\Models\Office;
use App\Models\Overtime;
use App\Models\User;
use App\Services\AttendanceFineService;
use App\Services\AttendanceRecapService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceRecapServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AttendanceRecapService $recapService;
    protected Office $officeMalang;
    protected Office $officeMalaysia;
    protected AttendanceSetting $settingMalang;
    protected AttendanceSetting $settingMalaysia;
    protected User $staffMalang;
    protected User $staffMalaysia;

    protected function setUp(): void
    {
        parent::setUp();

        $fineService = new AttendanceFineService();
        $this->recapService = new AttendanceRecapService($fineService);

        // Office Malang
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
            'overtime_check_in_start' => '18:00:00',
            'overtime_check_out_end' => '22:00:00',
            'minimum_overtime_minutes' => 120,
            'overtime_allowance_amount' => 20000.00,
            'effective_from' => '2026-01-01',
            'is_active' => true,
        ]);

        // Office Malaysia (Zero regular allowance, zero fine, overtime active)
        $this->officeMalaysia = Office::create([
            'name' => 'Kantor Malaysia',
            'latitude' => 3.139003,
            'longitude' => 101.686855,
            'attendance_radius_meter' => 100,
            'timezone' => 'Asia/Kuala_Lumpur',
            'is_active' => true,
        ]);

        $this->settingMalaysia = AttendanceSetting::create([
            'office_id' => $this->officeMalaysia->id,
            'regular_check_in_start' => '08:00:00',
            'regular_check_out_end' => '17:00:00',
            'minimum_regular_minutes' => 360,
            'regular_allowance_amount' => 0.00,
            'absence_fine_amount' => 0.00,
            'overtime_check_in_start' => '18:00:00',
            'overtime_check_out_end' => '22:00:00',
            'minimum_overtime_minutes' => 120,
            'overtime_allowance_amount' => 25000.00,
            'effective_from' => '2026-01-01',
            'is_active' => true,
        ]);

        $this->staffMalang = User::factory()->create([
            'name' => 'Staff Malang',
            'office_id' => $this->officeMalang->id,
        ]);

        $this->staffMalaysia = User::factory()->create([
            'name' => 'Staff Malaysia',
            'office_id' => $this->officeMalaysia->id,
        ]);
    }

    public function test_get_monthly_recap_calculates_attendance_allowances_and_fines_properly()
    {
        // Setup attendance for Staff Malang in August 2026 (2026-08)
        // 2026-08-03 (Monday): Present
        $att = Attendance::create([
            'user_id' => $this->staffMalang->id,
            'office_id' => $this->officeMalang->id,
            'attendance_setting_id' => $this->settingMalang->id,
            'attendance_date' => '2026-08-03',
            'check_in_at' => '2026-08-03 08:00:00',
            'check_out_at' => '2026-08-03 17:00:00',
            'working_minutes' => 480,
            'allowance_eligible' => true,
            'allowance_amount' => 15000.00,
        ]);

        // 2026-08-03 Overtime
        Overtime::create([
            'attendance_id' => $att->id,
            'overtime_date' => '2026-08-03',
            'check_in_at' => '2026-08-03 18:00:00',
            'check_out_at' => '2026-08-03 21:00:00',
            'overtime_minutes' => 180,
            'allowance_eligible' => true,
            'allowance_amount' => 20000.00,
        ]);

        // 2026-08-04 to 2026-08-06 (3 days): Approved Leave with fine relief Rp 50.000
        LeaveRequest::create([
            'user_id' => $this->staffMalang->id,
            'office_id' => $this->officeMalang->id,
            'leave_type' => 'sick',
            'start_date' => '2026-08-04',
            'end_date' => '2026-08-06',
            'total_days' => 3,
            'normal_fine_amount' => 150000.00,
            'adjusted_fine_amount' => 50000.00,
            'reason' => 'Sakit',
            'status' => 'approved',
        ]);

        $recap = $this->recapService->getMonthlyRecap(8, 2026, $this->officeMalang->id, $this->staffMalang->id);

        $this->assertEquals(8, $recap['period']['month']);
        $this->assertEquals(2026, $recap['period']['year']);
        $this->assertCount(1, $recap['staff_data']);

        $staffRecap = $recap['staff_data'][0];
        $this->assertEquals('Staff Malang', $staffRecap['name']);
        $this->assertEquals(1, $staffRecap['present_days']);
        $this->assertEquals(3, $staffRecap['leave_days']);
        $this->assertEquals(15000.00, $staffRecap['regular_allowance']);
        $this->assertEquals(20000.00, $staffRecap['overtime_allowance']);
        $this->assertEquals(35000.00, $staffRecap['gross_allowance']);
        $this->assertEquals(50000.00, $staffRecap['leave_fine']);
        $this->assertGreaterThan(0, $staffRecap['total_fine']);
    }

    public function test_get_recap_by_date_range_calculates_cutoff_period_properly()
    {
        // Custom cutoff: 16 Aug 2026 to 15 Sept 2026
        $start = Carbon::parse('2026-08-16');
        $end = Carbon::parse('2026-09-15');

        // Present on 2026-08-18 (Tuesday)
        $att = Attendance::create([
            'user_id' => $this->staffMalang->id,
            'office_id' => $this->officeMalang->id,
            'attendance_setting_id' => $this->settingMalang->id,
            'attendance_date' => '2026-08-18',
            'check_in_at' => '2026-08-18 08:00:00',
            'check_out_at' => '2026-08-18 17:00:00',
            'working_minutes' => 480,
            'allowance_eligible' => true,
            'allowance_amount' => 15000.00,
        ]);

        // Overtime on 2026-08-18
        Overtime::create([
            'attendance_id' => $att->id,
            'overtime_date' => '2026-08-18',
            'check_in_at' => '2026-08-18 18:00:00',
            'check_out_at' => '2026-08-18 20:30:00',
            'overtime_minutes' => 150,
            'allowance_eligible' => true,
            'allowance_amount' => 20000.00,
        ]);

        $recap = $this->recapService->getRecapByDateRange($start, $end, $this->officeMalang->id, $this->staffMalang->id);

        $this->assertEquals('2026-08-16', $recap['period']['start_date']);
        $this->assertEquals('2026-09-15', $recap['period']['end_date']);
        $this->assertCount(1, $recap['staff_data']);

        $staffRecap = $recap['staff_data'][0];
        $this->assertEquals(1, $staffRecap['present_days']);
        $this->assertEquals(15000.00, $staffRecap['regular_allowance']);
        $this->assertEquals(20000.00, $staffRecap['overtime_allowance']);
    }

    public function test_malaysia_office_staff_has_zero_regular_allowance_and_zero_fine()
    {
        // 2026-08-03: Present in Malaysia
        $att = Attendance::create([
            'user_id' => $this->staffMalaysia->id,
            'office_id' => $this->officeMalaysia->id,
            'attendance_setting_id' => $this->settingMalaysia->id,
            'attendance_date' => '2026-08-03',
            'check_in_at' => '2026-08-03 08:00:00',
            'check_out_at' => '2026-08-03 17:00:00',
            'working_minutes' => 480,
            'allowance_eligible' => false,
            'allowance_amount' => 0.00,
        ]);

        // Overtime 2 hours in Malaysia -> 25.000
        Overtime::create([
            'attendance_id' => $att->id,
            'overtime_date' => '2026-08-03',
            'check_in_at' => '2026-08-03 18:00:00',
            'check_out_at' => '2026-08-03 20:00:00',
            'overtime_minutes' => 120,
            'allowance_eligible' => true,
            'allowance_amount' => 25000.00,
        ]);

        $recap = $this->recapService->getMonthlyRecap(8, 2026, $this->officeMalaysia->id, $this->staffMalaysia->id);

        $staffRecap = $recap['staff_data'][0];
        $this->assertEquals(0.00, $staffRecap['regular_allowance']);
        $this->assertEquals(25000.00, $staffRecap['overtime_allowance']);
        $this->assertEquals(0.00, $staffRecap['alpha_fine']);
        $this->assertEquals(0.00, $staffRecap['total_fine']);
        $this->assertEquals(25000.00, $staffRecap['net_allowance']);
    }
}
