<?php

namespace Tests\Feature;

use App\Models\AttendanceSetting;
use App\Models\LeaveRequest;
use App\Models\Office;
use App\Models\User;
use App\Services\AttendanceFineService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveRequestTest extends TestCase
{
    use RefreshDatabase;

    protected Office $office;
    protected AttendanceSetting $policy;
    protected User $staff;
    protected User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->office = Office::create([
            'name' => 'Kantor Malang',
            'latitude' => -7.966620,
            'longitude' => 112.632632,
            'attendance_radius_meter' => 100,
            'timezone' => 'Asia/Jakarta',
            'is_active' => true,
        ]);

        $this->policy = AttendanceSetting::create([
            'office_id' => $this->office->id,
            'regular_check_in_start' => '08:00:00',
            'regular_check_out_end' => '17:00:00',
            'minimum_regular_minutes' => 360,
            'regular_allowance_amount' => 15000.00,
            'absence_fine_amount' => 50000.00,
            'effective_from' => '2026-01-01',
            'is_active' => true,
        ]);

        $this->staff = User::factory()->create([
            'office_id' => $this->office->id,
            'name' => 'Staff Ahmad',
        ]);

        $this->manager = User::factory()->create([
            'office_id' => $this->office->id,
            'name' => 'Manager Budi',
        ]);
    }

    public function test_staff_can_create_leave_request_and_calculates_working_days_and_normal_fine()
    {
        $fineService = app(AttendanceFineService::class);
        $start = Carbon::parse('2026-09-23'); // Wed
        $end = Carbon::parse('2026-09-25');   // Fri (3 days)

        $calc = $fineService->calculateLeaveNormalFine($this->office, $start, $end);

        $leave = LeaveRequest::create([
            'user_id' => $this->staff->id,
            'office_id' => $this->office->id,
            'leave_type' => 'permission',
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'total_days' => $calc['total_days'],
            'normal_fine_amount' => $calc['normal_fine_amount'],
            'reason' => 'Urusan keluarga mendadak di luar kota',
            'status' => 'pending',
            'adjusted_fine_amount' => 0.00,
        ]);

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leave->id,
            'user_id' => $this->staff->id,
            'total_days' => 3,
            'normal_fine_amount' => 150000.00,
            'status' => 'pending',
        ]);

        $this->assertTrue($leave->isPending());
        $this->assertFalse($leave->isApproved());
    }

    public function test_supervisor_can_approve_leave_request_with_fine_relief()
    {
        $leave = LeaveRequest::create([
            'user_id' => $this->staff->id,
            'office_id' => $this->office->id,
            'leave_type' => 'permission',
            'start_date' => '2026-09-23',
            'end_date' => '2026-09-25',
            'total_days' => 3,
            'normal_fine_amount' => 150000.00,
            'reason' => 'Urusan keluarga mendadak di luar kota',
            'status' => 'pending',
            'adjusted_fine_amount' => 0.00,
        ]);

        // Manager approves with fine relief to Rp 50.000 instead of Rp 150.000
        $leave->update([
            'status' => 'approved',
            'approved_by' => $this->manager->id,
            'approved_at' => now(),
            'adjusted_fine_amount' => 50000.00,
            'approval_notes' => 'Disetujui dengan keringanan denda menjadi Rp 50.000',
        ]);

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leave->id,
            'status' => 'approved',
            'approved_by' => $this->manager->id,
            'adjusted_fine_amount' => 50000.00,
        ]);

        $this->assertTrue($leave->isApproved());
        $this->assertEquals('Manager Budi', $leave->approver->name);
    }

    public function test_supervisor_can_reject_leave_request()
    {
        $leave = LeaveRequest::create([
            'user_id' => $this->staff->id,
            'office_id' => $this->office->id,
            'leave_type' => 'permission',
            'start_date' => '2026-09-23',
            'end_date' => '2026-09-25',
            'total_days' => 3,
            'normal_fine_amount' => 150000.00,
            'reason' => 'Tanpa keterangan yang jelas',
            'status' => 'pending',
        ]);

        // Manager rejects
        $leave->update([
            'status' => 'rejected',
            'approved_by' => $this->manager->id,
            'approved_at' => now(),
            'approval_notes' => 'Alasan tidak mencukupi, harap koordinasikan langsung.',
        ]);

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leave->id,
            'status' => 'rejected',
            'approved_by' => $this->manager->id,
        ]);

        $this->assertTrue($leave->isRejected());
    }
}
