<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Filament\Pages\MyAttendance;
use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\Office;
use App\Models\User;
use App\Services\AttendanceWhatsAppNotificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StaffAttendancePunctualityTest extends TestCase
{
    use RefreshDatabase;

    protected Office $office;
    protected AttendanceSetting $policy;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'team_member', 'guard_name' => 'web']);

        $this->office = Office::create([
            'name' => 'Kuala Lumpur HQ',
            'latitude' => 3.1340000,
            'longitude' => 101.6860000,
            'attendance_radius_meter' => 100,
            'timezone' => 'Asia/Kuala_Lumpur',
            'is_active' => true,
        ]);

        $this->policy = AttendanceSetting::create([
            'office_id' => $this->office->id,
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

        $this->staff = User::factory()->create([
            'name' => 'John Staff',
            'office_id' => $this->office->id,
        ]);
        $this->staff->assignRole('team_member');

        \Spatie\Permission\Models\Permission::findOrCreate('page_MyAttendance', 'web');
        $this->staff->givePermissionTo('page_MyAttendance');
    }

    public function test_early_check_in_records_on_time_status_and_snaps_working_duration()
    {
        Carbon::setTestNow(Carbon::parse('2026-10-12 07:45:00', 'Asia/Kuala_Lumpur'));
        $this->actingAs($this->staff);

        $sampleSelfie = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

        Livewire::test(MyAttendance::class)
            ->call('updateCoordinates', 3.1340100, 101.6860100, 10.0)
            ->set('selfie', $sampleSelfie)
            ->call('doRegularCheckIn')
            ->assertHasNoErrors();

        $attendance = Attendance::where('user_id', $this->staff->id)->first();
        $this->assertNotNull($attendance);
        $this->assertEquals(AttendanceStatus::ON_TIME, $attendance->status);
        $this->assertFalse($attendance->isLate());
        $this->assertEquals(0, $attendance->late_minutes);

        // Check out at 16:00.
        // Duration: 08:00 to 16:00 = 8h (480 minutes), NOT from 07:45 (8h 15m)
        Carbon::setTestNow(Carbon::parse('2026-10-12 16:00:00', 'Asia/Kuala_Lumpur'));

        Livewire::test(MyAttendance::class)
            ->call('updateCoordinates', 3.1340100, 101.6860100, 10.0)
            ->set('selfie', $sampleSelfie)
            ->call('doRegularCheckOut')
            ->assertHasNoErrors();

        $attendance->refresh();
        $this->assertEquals(480, $attendance->working_minutes);

        Carbon::setTestNow();
    }

    public function test_late_check_in_records_late_status_and_late_minutes()
    {
        // Staff checks in at 08:25 (25 minutes late past 08:00)
        Carbon::setTestNow(Carbon::parse('2026-10-12 08:25:00', 'Asia/Kuala_Lumpur'));
        $this->actingAs($this->staff);

        $sampleSelfie = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

        Livewire::test(MyAttendance::class)
            ->call('updateCoordinates', 3.1340100, 101.6860100, 10.0)
            ->set('selfie', $sampleSelfie)
            ->call('doRegularCheckIn')
            ->assertHasNoErrors();

        $attendance = Attendance::where('user_id', $this->staff->id)->first();
        $this->assertNotNull($attendance);
        $this->assertEquals(AttendanceStatus::LATE, $attendance->status);
        $this->assertTrue($attendance->isLate());
        $this->assertEquals(25, $attendance->late_minutes);
        $this->assertEquals('Terlambat (25m)', $attendance->punctuality_description);

        // WhatsApp notification message check
        $waService = app(AttendanceWhatsAppNotificationService::class);
        $message = $waService->formatCheckIn($attendance);
        $this->assertStringContainsString('*Status Kehadiran:* ⚠️ Terlambat (25 Menit)', $message);

        Carbon::setTestNow();
    }

    public function test_check_in_with_late_tolerance_minute_respects_grace_period()
    {
        // Update policy to have 30 minutes late tolerance
        $this->policy->update(['late_tolerance_minute' => 30]);

        // Staff checks in at 08:20:00 (past 08:00, but within 30 min tolerance) -> ON_TIME
        Carbon::setTestNow(Carbon::parse('2026-10-12 08:20:00', 'Asia/Kuala_Lumpur'));
        $this->actingAs($this->staff);

        $sampleSelfie = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

        Livewire::test(MyAttendance::class)
            ->call('updateCoordinates', 3.1340100, 101.6860100, 10.0)
            ->set('selfie', $sampleSelfie)
            ->call('doRegularCheckIn')
            ->assertHasNoErrors();

        $attendance = Attendance::where('user_id', $this->staff->id)->first();
        $this->assertNotNull($attendance);
        $this->assertEquals(AttendanceStatus::ON_TIME, $attendance->status);
        $this->assertFalse($attendance->isLate());
        $this->assertEquals(0, $attendance->late_minutes);

        Carbon::setTestNow();
    }
}
