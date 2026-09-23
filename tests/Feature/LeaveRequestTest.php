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

    public function test_leave_request_generates_chat_template_format()
    {
        $staff = User::factory()->create([
            'office_id' => $this->office->id,
            'name' => 'Ridho Aulia Rahman',
        ]);

        // Monday to Thursday
        $leave = LeaveRequest::create([
            'user_id' => $staff->id,
            'office_id' => $this->office->id,
            'leave_type' => 'permission',
            'start_date' => '2026-09-21', // Monday
            'end_date' => '2026-09-24',   // Thursday
            'total_days' => 4,
            'normal_fine_amount' => 200000.00,
            'reason' => 'pulang kampung',
            'status' => 'pending',
        ]);

        $chat = $leave->getFormattedChatTemplate('Dr. Adnan');

        $this->assertStringContainsString('*Kepada Yth.*', $chat);
        $this->assertStringContainsString('Dr. Adnan', $chat);
        $this->assertStringContainsString('*Nama: Ridho Aulia Rahman*', $chat);
        $this->assertStringContainsString('*Kantor: Kantor Malang*', $chat);
        $this->assertStringContainsString('*Jenis Izin: Izin Keperluan*', $chat);
        $this->assertStringContainsString('pulang kampung mulai hari Senin, 21 September 2026 hingga hari Kamis, 24 September 2026 (4 hari kerja)', $chat);
        $this->assertStringContainsString('Sehubungan dengan hal tersebut, saya memohon izin kepada Dr. Adnan', $chat);
        $this->assertStringContainsString('Hormat saya,', $chat);
        $this->assertStringContainsString('*Ridho Aulia Rahman*', $chat);
    }

    public function test_leave_request_generates_whatsapp_chat_template()
    {
        $staff = User::factory()->create([
            'office_id' => $this->office->id,
            'name' => 'Ridho Aulia Rahman',
        ]);

        $leave = LeaveRequest::create([
            'user_id' => $staff->id,
            'office_id' => $this->office->id,
            'leave_type' => 'permission',
            'start_date' => '2026-09-21',
            'end_date' => '2026-09-24',
            'total_days' => 4,
            'normal_fine_amount' => 200000.00,
            'reason' => 'pulang kampung',
            'status' => 'pending',
        ]);

        $waChat = $leave->getWhatsAppChatTemplate('Dr. Adnan');

        $this->assertStringContainsString('*Kepada Yth.*', $waChat);
        $this->assertStringContainsString('*Nama: Ridho Aulia Rahman*', $waChat);
        $this->assertStringContainsString('*Kantor: Kantor Malang*', $waChat);
        $this->assertStringContainsString('*Jenis Izin: Izin Keperluan*', $waChat);
        $this->assertStringContainsString('pulang kampung mulai hari Senin, 21 September 2026 hingga hari Kamis, 24 September 2026 (4 hari kerja)', $waChat);
        $this->assertStringContainsString('*Ridho Aulia Rahman*', $waChat);
    }

    public function test_authenticated_user_can_export_leave_request_pdf()
    {
        $leave = LeaveRequest::create([
            'user_id' => $this->staff->id,
            'office_id' => $this->office->id,
            'leave_type' => 'permission',
            'start_date' => '2026-09-21',
            'end_date' => '2026-09-24',
            'total_days' => 4,
            'normal_fine_amount' => 200000.00,
            'reason' => 'pulang kampung',
            'status' => 'approved',
            'approved_by' => $this->manager->id,
            'approved_at' => now(),
            'adjusted_fine_amount' => 0.00,
            'approval_notes' => 'Disetujui',
        ]);

        $response = $this->actingAs($this->staff)->get(route('leave-requests.pdf', $leave));

        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
    }
}
