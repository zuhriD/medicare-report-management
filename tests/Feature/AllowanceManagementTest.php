<?php

namespace Tests\Feature;

use App\Models\AllowancePeriod;
use App\Models\AllowancePeriodStaff;
use App\Models\Attendance;
use App\Models\Office;
use App\Models\Overtime;
use App\Models\User;
use App\Services\AllowanceCalculationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AllowanceManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $hrUser;
    protected User $staffUser1;
    protected User $staffUser2;
    protected Office $office;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'team_member', 'guard_name' => 'web']);

        $this->office = Office::create([
            'name' => 'Headquarters',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'attendance_radius_meter' => 100,
            'is_active' => true,
        ]);

        $this->setting = \App\Models\AttendanceSetting::create([
            'office_id' => $this->office->id,
            'check_in_start' => '07:00:00',
            'check_in_end' => '09:00:00',
            'check_out_start' => '17:00:00',
            'check_out_end' => '19:00:00',
            'minimum_regular_minutes' => 480,
            'regular_allowance_amount' => 50000.00,
            'is_active' => true,
            'effective_from' => '2026-01-01',
        ]);

        $this->hrUser = User::factory()->create([
            'office_id' => $this->office->id,
            'name' => 'HR Admin',
            'email' => 'hr@medicare.test',
        ]);
        $this->hrUser->assignRole('super_admin');

        $this->staffUser1 = User::factory()->create([
            'office_id' => $this->office->id,
            'name' => 'Staff One',
            'email' => 'staff1@medicare.test',
        ]);
        $this->staffUser1->assignRole('team_member');

        $this->staffUser2 = User::factory()->create([
            'office_id' => $this->office->id,
            'name' => 'Staff Two',
            'email' => 'staff2@medicare.test',
        ]);
        $this->staffUser2->assignRole('team_member');
    }

    public function test_allowance_calculation_service_calculates_attendance_and_overtime(): void
    {
        // Create 2 eligible attendances for Staff 1
        $att1 = Attendance::create([
            'user_id' => $this->staffUser1->id,
            'office_id' => $this->office->id,
            'attendance_setting_id' => $this->setting->id,
            'attendance_date' => '2026-06-16',
            'check_in_at' => '2026-06-16 08:00:00',
            'check_out_at' => '2026-06-16 17:00:00',
            'working_minutes' => 540,
            'allowance_eligible' => true,
            'allowance_amount' => 50000.00,
        ]);

        $att2 = Attendance::create([
            'user_id' => $this->staffUser1->id,
            'office_id' => $this->office->id,
            'attendance_setting_id' => $this->setting->id,
            'attendance_date' => '2026-06-17',
            'check_in_at' => '2026-06-17 08:00:00',
            'check_out_at' => '2026-06-17 17:00:00',
            'working_minutes' => 540,
            'allowance_eligible' => true,
            'allowance_amount' => 50000.00,
        ]);

        // Create 1 eligible overtime on att1
        Overtime::create([
            'attendance_id' => $att1->id,
            'overtime_date' => '2026-06-16',
            'check_in_at' => '2026-06-16 17:30:00',
            'check_out_at' => '2026-06-16 19:30:00',
            'overtime_minutes' => 120,
            'allowance_eligible' => true,
            'allowance_amount' => 60000.00,
        ]);

        $service = app(AllowanceCalculationService::class);
        $result = $service->calculateForStaff(
            $this->staffUser1,
            Carbon::parse('2026-06-15'),
            Carbon::parse('2026-07-15')
        );

        $this->assertEquals(2, $result['total_attendance_days']);
        $this->assertEquals(100000.00, $result['total_attendance_amount']);
        $this->assertEquals(120, $result['total_overtime_minutes']);
        $this->assertEquals(60000.00, $result['total_overtime_amount']);
        $this->assertEquals(160000.00, $result['total_allowance']);
        $this->assertCount(3, $result['items']); // 2 attendances + 1 overtime
    }

    public function test_period_generation_and_payment_workflow(): void
    {
        // Create attendances for both staff
        Attendance::create([
            'user_id' => $this->staffUser1->id,
            'office_id' => $this->office->id,
            'attendance_setting_id' => $this->setting->id,
            'attendance_date' => '2026-06-16',
            'check_in_at' => '2026-06-16 08:00:00',
            'check_out_at' => '2026-06-16 17:00:00',
            'working_minutes' => 540,
            'allowance_eligible' => true,
            'allowance_amount' => 50000.00,
        ]);

        Attendance::create([
            'user_id' => $this->staffUser2->id,
            'office_id' => $this->office->id,
            'attendance_setting_id' => $this->setting->id,
            'attendance_date' => '2026-06-16',
            'check_in_at' => '2026-06-16 08:00:00',
            'check_out_at' => '2026-06-16 17:00:00',
            'working_minutes' => 540,
            'allowance_eligible' => true,
            'allowance_amount' => 50000.00,
        ]);

        $period = AllowancePeriod::create([
            'name' => 'Periode 15 Jun - 16 Jul HQ',
            'office_id' => $this->office->id,
            'start_date' => '2026-06-15',
            'end_date' => '2026-07-16',
            'status' => 'active',
            'created_by' => $this->hrUser->id,
        ]);

        $service = app(AllowanceCalculationService::class);
        $service->generatePeriodSummary($period, [$this->staffUser1->id, $this->staffUser2->id]);

        $period->refresh();
        $this->assertEquals(100000.00, (float) $period->total_amount);
        $this->assertEquals(0.00, (float) $period->total_paid_amount);
        $this->assertEquals(100000.00, (float) $period->total_unpaid_amount);
        $this->assertEquals(2, $period->staff_count);
        $this->assertEquals(0, $period->paid_staff_count);
        $this->assertEquals(2, $period->unpaid_staff_count);

        // Mark staff 1 as paid
        $periodStaff1 = $period->periodStaff()->where('user_id', $this->staffUser1->id)->first();
        $service->markStaffAsPaid($periodStaff1, [
            'paid_at' => now(),
            'paid_by' => $this->hrUser->id,
            'payment_method' => 'Transfer Bank',
            'payment_reference' => 'TRF-123456',
        ]);

        $period->refresh();
        $this->assertEquals(50000.00, (float) $period->total_paid_amount);
        $this->assertEquals(50000.00, (float) $period->total_unpaid_amount);
        $this->assertEquals('active', $period->status);

        // Mark staff 2 as paid
        $periodStaff2 = $period->periodStaff()->where('user_id', $this->staffUser2->id)->first();
        $service->markStaffAsPaid($periodStaff2, [
            'paid_at' => now(),
            'paid_by' => $this->hrUser->id,
            'payment_method' => 'Transfer Bank',
        ]);

        $period->refresh();
        $this->assertEquals(100000.00, (float) $period->total_paid_amount);
        $this->assertEquals(0.00, (float) $period->total_unpaid_amount);
        $this->assertEquals('completed', $period->status);

        // Revert staff 1 to unpaid
        $service->markStaffAsUnpaid($periodStaff1);
        $period->refresh();
        $this->assertEquals(50000.00, (float) $period->total_paid_amount);
        $this->assertEquals(50000.00, (float) $period->total_unpaid_amount);
        $this->assertEquals('active', $period->status);
    }

    public function test_overlap_detection(): void
    {
        $att = Attendance::create([
            'user_id' => $this->staffUser1->id,
            'office_id' => $this->office->id,
            'attendance_setting_id' => $this->setting->id,
            'attendance_date' => '2026-06-20',
            'check_in_at' => '2026-06-20 08:00:00',
            'check_out_at' => '2026-06-20 17:00:00',
            'working_minutes' => 540,
            'allowance_eligible' => true,
            'allowance_amount' => 50000.00,
        ]);

        $period1 = AllowancePeriod::create([
            'name' => 'Periode 1',
            'office_id' => $this->office->id,
            'start_date' => '2026-06-15',
            'end_date' => '2026-07-16',
            'status' => 'active',
            'created_by' => $this->hrUser->id,
        ]);

        $service = app(AllowanceCalculationService::class);
        $service->generatePeriodSummary($period1, [$this->staffUser1->id]);

        // Now check overlap for a second period spanning the same date
        $warnings = $service->checkOverlappingStaff(
            [$this->staffUser1->id],
            Carbon::parse('2026-06-01'),
            Carbon::parse('2026-06-30')
        );

        $this->assertNotEmpty($warnings);
        $this->assertStringContainsString('Staff One', $warnings[0]);
    }
}
