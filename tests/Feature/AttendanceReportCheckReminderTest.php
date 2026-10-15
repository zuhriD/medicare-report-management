<?php

namespace Tests\Feature;

use App\Filament\Pages\MyAttendance;
use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\DailyReport;
use App\Models\Module;
use App\Models\Office;
use App\Models\PlanOfAction;
use App\Models\SubModule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AttendanceReportCheckReminderTest extends TestCase
{
    use RefreshDatabase;

    protected Office $office;
    protected AttendanceSetting $policy;
    protected User $user;
    protected string $sampleSelfie;

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
            'name' => 'Jane Doe',
            'username' => 'janedoe',
            'email' => 'jane@medicare.com',
            'password' => bcrypt('password'),
        ]);

        Permission::findOrCreate('page_MyAttendance', 'web');
        $this->user->givePermissionTo('page_MyAttendance');

        $this->sampleSelfie = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
    }

    public function test_modal_pops_up_when_staff_checks_out_without_poa_and_daily_report()
    {
        $this->actingAs($this->user);
        $officeTimezone = 'Asia/Kuala_Lumpur';
        Carbon::setTestNow(Carbon::parse('2026-10-15 17:05:00', $officeTimezone));

        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'attendance_setting_id' => $this->policy->id,
            'attendance_date' => Carbon::now($officeTimezone)->toDateString(),
            'check_in_at' => Carbon::now($officeTimezone)->subHours(8),
            'check_in_latitude' => 3.1340,
            'check_in_longitude' => 101.6860,
            'check_in_accuracy' => 5.0,
            'check_in_selfie' => 'attendance-selfies/sample.webp',
        ]);

        // Attempt checkout without PoA or DailyReport
        Livewire::test(MyAttendance::class)
            ->call('updateCoordinates', 3.1340, 101.6860, 5.0)
            ->set('selfie', $this->sampleSelfie)
            ->call('doRegularCheckOut')
            ->assertSet('showIncompleteReportModal', true)
            ->assertSet('hasPoaToday', false)
            ->assertSet('hasDailyReportToday', false);

        $attendance->refresh();
        $this->assertNull($attendance->check_out_at, 'Check out should not be finalized while modal is triggered');

        Carbon::setTestNow();
    }

    public function test_staff_can_close_incomplete_report_modal()
    {
        $this->actingAs($this->user);
        $officeTimezone = 'Asia/Kuala_Lumpur';
        Carbon::setTestNow(Carbon::parse('2026-10-15 17:05:00', $officeTimezone));

        Attendance::create([
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'attendance_setting_id' => $this->policy->id,
            'attendance_date' => Carbon::now($officeTimezone)->toDateString(),
            'check_in_at' => Carbon::now($officeTimezone)->subHours(8),
            'check_in_latitude' => 3.1340,
            'check_in_longitude' => 101.6860,
            'check_in_accuracy' => 5.0,
            'check_in_selfie' => 'attendance-selfies/sample.webp',
        ]);

        Livewire::test(MyAttendance::class)
            ->call('updateCoordinates', 3.1340, 101.6860, 5.0)
            ->set('selfie', $this->sampleSelfie)
            ->call('doRegularCheckOut')
            ->assertSet('showIncompleteReportModal', true)
            ->call('closeIncompleteReportModal')
            ->assertSet('showIncompleteReportModal', false);

        Carbon::setTestNow();
    }

    public function test_staff_can_confirm_checkout_despite_missing_reports()
    {
        $this->actingAs($this->user);
        $officeTimezone = 'Asia/Kuala_Lumpur';
        Carbon::setTestNow(Carbon::parse('2026-10-15 17:05:00', $officeTimezone));

        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'attendance_setting_id' => $this->policy->id,
            'attendance_date' => Carbon::now($officeTimezone)->toDateString(),
            'check_in_at' => Carbon::now($officeTimezone)->subHours(8),
            'check_in_latitude' => 3.1340,
            'check_in_longitude' => 101.6860,
            'check_in_accuracy' => 5.0,
            'check_in_selfie' => 'attendance-selfies/sample.webp',
        ]);

        // Trigger warning then confirm checkout
        Livewire::test(MyAttendance::class)
            ->call('updateCoordinates', 3.1340, 101.6860, 5.0)
            ->set('selfie', $this->sampleSelfie)
            ->call('doRegularCheckOut')
            ->assertSet('showIncompleteReportModal', true)
            ->call('confirmCheckOut')
            ->assertSet('showIncompleteReportModal', false)
            ->assertHasNoErrors();

        $attendance->refresh();
        $this->assertNotNull($attendance->check_out_at);

        Carbon::setTestNow();
    }

    public function test_checkout_completes_directly_when_both_reports_are_filled()
    {
        $this->actingAs($this->user);
        $officeTimezone = 'Asia/Kuala_Lumpur';
        $today = Carbon::parse('2026-10-15 17:05:00', $officeTimezone);
        Carbon::setTestNow($today);

        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'attendance_setting_id' => $this->policy->id,
            'attendance_date' => $today->toDateString(),
            'check_in_at' => $today->copy()->subHours(8),
            'check_in_latitude' => 3.1340,
            'check_in_longitude' => 101.6860,
            'check_in_accuracy' => 5.0,
            'check_in_selfie' => 'attendance-selfies/sample.webp',
        ]);

        $module = Module::create(['name' => 'Development']);
        $subModule = SubModule::create([
            'module_id' => $module->id,
            'name' => 'Backend',
        ]);

        // Create Plan of Action for today
        PlanOfAction::create([
            'user_id' => $this->user->id,
            'module_id' => $module->id,
            'sub_module_id' => $subModule->id,
            'start_date' => $today->toDateString(),
            'description' => ['Menyelesaikan fitur absensi'],
        ]);

        // Create Daily Report for today
        DailyReport::create([
            'user_id' => $this->user->id,
            'sub_module_id' => $subModule->id,
            'report_date' => $today->toDateString(),
            'description' => ['Fitur absensi selesai dan sudah di-test'],
        ]);

        Livewire::test(MyAttendance::class)
            ->call('updateCoordinates', 3.1340, 101.6860, 5.0)
            ->set('selfie', $this->sampleSelfie)
            ->call('doRegularCheckOut')
            ->assertSet('showIncompleteReportModal', false)
            ->assertHasNoErrors();

        $attendance->refresh();
        $this->assertNotNull($attendance->check_out_at);

        Carbon::setTestNow();
    }
}
