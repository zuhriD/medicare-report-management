<?php

namespace Tests\Feature;

use App\Filament\Pages\MyAttendance;
use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\Office;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class StaffAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected Office $office;
    protected AttendanceSetting $policy;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('gcs');

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

        $this->user = User::create([
            'office_id' => $this->office->id,
            'name' => 'John Doe',
            'username' => 'johndoe',
            'email' => 'john@medicare.com',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_staff_attendance_page_can_be_rendered()
    {
        $this->actingAs($this->user);

        Livewire::test(MyAttendance::class)
            ->assertSuccessful()
            ->assertSee('Kuala Lumpur HQ')
            ->assertSee('Absensi Reguler');
    }

    public function test_staff_can_perform_regular_check_in_within_geofence()
    {
        $this->actingAs($this->user);

        // 1x1 transparent gif base64
        $sampleSelfie = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

        Livewire::test(MyAttendance::class)
            ->call('updateCoordinates', 3.1340100, 101.6860100, 10.0)
            ->assertSet('isWithinRadius', true)
            ->set('selfie', $sampleSelfie)
            ->call('doRegularCheckIn')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
        ]);
    }

    public function test_staff_cannot_check_in_outside_geofence()
    {
        $this->actingAs($this->user);

        $sampleSelfie = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

        // Coordinates in Singapore (~350km away)
        Livewire::test(MyAttendance::class)
            ->call('updateCoordinates', 1.3521, 103.8198, 10.0)
            ->assertSet('isWithinRadius', false)
            ->set('selfie', $sampleSelfie)
            ->call('doRegularCheckIn');

        $this->assertDatabaseMissing('attendances', [
            'user_id' => $this->user->id,
        ]);
    }

    public function test_staff_can_perform_check_out_and_overtime_flow()
    {
        $this->actingAs($this->user);
        $sampleSelfie = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

        $officeTimezone = 'Asia/Kuala_Lumpur';
        // Simulate current time at 18:30:00 in KL timezone (inside OT window)
        Carbon::setTestNow(Carbon::parse('2026-09-21 18:30:00', $officeTimezone));

        // 1. Check in occurred 7 hours ago (at 11:30:00)
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'attendance_setting_id' => $this->policy->id,
            'attendance_date' => Carbon::now()->toDateString(),
            'check_in_at' => Carbon::now()->subHours(7),
            'check_in_latitude' => 3.1340,
            'check_in_longitude' => 101.6860,
            'check_in_accuracy' => 5.0,
            'check_in_selfie' => 'attendance-selfies/sample.webp',
        ]);

        // 2. Check out
        Livewire::test(MyAttendance::class)
            ->call('updateCoordinates', 3.1340, 101.6860, 5.0)
            ->set('selfie', $sampleSelfie)
            ->call('doRegularCheckOut')
            ->assertHasNoErrors();

        $attendance->refresh();
        $this->assertNotNull($attendance->check_out_at);
        $this->assertGreaterThanOrEqual(360, $attendance->working_minutes);
        $this->assertTrue($attendance->allowance_eligible);
        $this->assertEquals(50.0, (float) $attendance->allowance_amount);

        // 3. Overtime Check in
        Livewire::test(MyAttendance::class)
            ->call('updateCoordinates', 3.1340, 101.6860, 5.0)
            ->set('selfie', $sampleSelfie)
            ->call('doOvertimeCheckIn')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('overtimes', [
            'attendance_id' => $attendance->id,
        ]);

        Carbon::setTestNow(); // Reset mock
    }
}
