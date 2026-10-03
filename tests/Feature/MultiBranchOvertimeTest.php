<?php

namespace Tests\Feature;

use App\Filament\Pages\MyAttendance;
use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\Office;
use App\Models\Overtime;
use App\Models\User;
use App\Services\AttendanceWhatsAppNotificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MultiBranchOvertimeTest extends TestCase
{
    use RefreshDatabase;

    protected Office $officeA;
    protected Office $officeB;
    protected AttendanceSetting $policyA;
    protected AttendanceSetting $policyB;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('gcs');

        // Office A (Kuala Lumpur HQ)
        $this->officeA = Office::create([
            'name' => 'Kuala Lumpur HQ',
            'latitude' => 3.1340000,
            'longitude' => 101.6860000,
            'attendance_radius_meter' => 100,
            'timezone' => 'Asia/Kuala_Lumpur',
            'whatsapp_group_link' => 'https://chat.whatsapp.com/OfficeAGroup',
            'is_active' => true,
            'is_geofence_enabled' => true,
        ]);

        $this->policyA = AttendanceSetting::create([
            'office_id' => $this->officeA->id,
            'regular_check_in_start' => '08:00:00',
            'regular_check_out_end' => '17:00:00',
            'minimum_regular_minutes' => 360,
            'regular_allowance_amount' => 50.00,
            'overtime_check_in_start' => '18:00:00',
            'overtime_check_out_end' => '23:00:00',
            'minimum_overtime_minutes' => 60,
            'overtime_allowance_amount' => 30.00,
            'effective_from' => '2026-01-01',
            'is_active' => true,
        ]);

        // Office B (Penang Branch)
        $this->officeB = Office::create([
            'name' => 'Penang Branch',
            'latitude' => 5.4141000,
            'longitude' => 100.3288000,
            'attendance_radius_meter' => 100,
            'timezone' => 'Asia/Kuala_Lumpur',
            'whatsapp_group_link' => 'https://chat.whatsapp.com/OfficeBGroup',
            'is_active' => true,
            'is_geofence_enabled' => true,
        ]);

        $this->policyB = AttendanceSetting::create([
            'office_id' => $this->officeB->id,
            'regular_check_in_start' => '08:00:00',
            'regular_check_out_end' => '17:00:00',
            'minimum_regular_minutes' => 360,
            'regular_allowance_amount' => 50.00,
            'overtime_check_in_start' => '18:00:00',
            'overtime_check_out_end' => '23:00:00',
            'minimum_overtime_minutes' => 60,
            'overtime_allowance_amount' => 35.00,
            'effective_from' => '2026-01-01',
            'is_active' => true,
        ]);

        // Staff assigned to primary Office A and cross-assigned to Office B
        $this->staff = User::create([
            'office_id' => $this->officeA->id,
            'name' => 'Ahmad Staff',
            'username' => 'ahmadstaff',
            'email' => 'ahmad@medicare.com',
            'password' => bcrypt('password'),
        ]);

        // Attach Office B to staff's assigned offices
        $this->staff->offices()->sync([$this->officeA->id, $this->officeB->id]);

        Permission::findOrCreate('page_MyAttendance', 'web');
        $this->staff->givePermissionTo('page_MyAttendance');
    }

    public function test_staff_can_view_all_assigned_offices(): void
    {
        $assignedOffices = $this->staff->getAllAssignedOffices();
        $this->assertCount(2, $assignedOffices);
        $this->assertTrue($assignedOffices->contains('id', $this->officeA->id));
        $this->assertTrue($assignedOffices->contains('id', $this->officeB->id));
    }

    public function test_staff_can_perform_overtime_at_office_b_with_office_b_geofence(): void
    {
        $this->actingAs($this->staff);

        // 1. Regular attendance at Office A is completed earlier today
        $now = Carbon::parse('2026-10-02 18:30:00', 'Asia/Kuala_Lumpur');
        Carbon::setTestNow($now);

        $attendance = Attendance::create([
            'user_id' => $this->staff->id,
            'office_id' => $this->officeA->id,
            'attendance_setting_id' => $this->policyA->id,
            'attendance_date' => '2026-10-02',
            'check_in_at' => Carbon::parse('2026-10-02 08:00:00', 'Asia/Kuala_Lumpur'),
            'check_out_at' => Carbon::parse('2026-10-02 17:00:00', 'Asia/Kuala_Lumpur'),
            'working_minutes' => 540,
            'allowance_eligible' => true,
            'allowance_amount' => 50.00,
        ]);

        $sampleSelfie = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

        // 2. Staff selects Office B for Overtime
        $testable = Livewire::test(MyAttendance::class)
            ->set('selectedOvertimeOfficeId', $this->officeB->id);

        // If staff is at Office A GPS (3.1340, 101.6860), it should be outside Office B geofence
        $testable->call('updateCoordinates', 3.1340, 101.6860, 5.0)
            ->assertSet('isWithinRadius', false);

        // When staff moves to Office B GPS (5.4141, 100.3288), geofence is valid!
        $testable->call('updateCoordinates', 5.4141, 100.3288, 5.0)
            ->assertSet('isWithinRadius', true)
            ->set('selfie', $sampleSelfie)
            ->call('doOvertimeCheckIn')
            ->assertHasNoErrors();

        // 3. Verify Overtime recorded with office_id = Office B
        $overtime = Overtime::where('attendance_id', $attendance->id)->first();
        $this->assertNotNull($overtime);
        $this->assertEquals($this->officeB->id, $overtime->office_id);
        $this->assertEquals($this->officeB->id, $overtime->actual_office->id);

        // 4. Verify WhatsApp message formatting
        $waService = app(AttendanceWhatsAppNotificationService::class);
        $waMessage = $waService->formatOvertimeCheckIn($overtime);

        $this->assertStringContainsString('Penang Branch (Lembur Cabang)', $waMessage);
        $this->assertStringContainsString('Kuala Lumpur HQ', $waMessage);

        // Verify group link stays as Office A's group link
        $groupLink = $waService->getGroupLink('https://chat.whatsapp.com/OfficeAGroup');
        $this->assertEquals('https://chat.whatsapp.com/OfficeAGroup', $groupLink);

        // 5. Complete Overtime at Office B
        Carbon::setTestNow(Carbon::parse('2026-10-02 20:30:00', 'Asia/Kuala_Lumpur')); // 2 hours OT

        Livewire::test(MyAttendance::class)
            ->call('updateCoordinates', 5.4141, 100.3288, 5.0)
            ->set('selfie', $sampleSelfie)
            ->call('doOvertimeCheckOut')
            ->assertHasNoErrors();

        $overtime->refresh();
        $this->assertNotNull($overtime->check_out_at);
        $this->assertEquals(120, $overtime->overtime_minutes);
        $this->assertTrue($overtime->allowance_eligible);
        $this->assertEquals(35.00, (float) $overtime->allowance_amount);

        // Verify WhatsApp Checkout message
        $waCheckoutMsg = $waService->formatOvertimeCheckOut($overtime);
        $this->assertStringContainsString('Penang Branch (Lembur Cabang)', $waCheckoutMsg);
        $this->assertStringContainsString('Kuala Lumpur HQ', $waCheckoutMsg);

        Carbon::setTestNow();
    }
}
