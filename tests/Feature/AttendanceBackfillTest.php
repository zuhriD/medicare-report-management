<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Filament\Pages\AttendanceRecapReport;
use App\Filament\Resources\AttendanceMonitoringResource\Pages\ListAttendanceMonitorings;
use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\Office;
use App\Models\Overtime;
use App\Models\User;
use App\Services\AttendanceBackfillService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AttendanceBackfillTest extends TestCase
{
    use RefreshDatabase;

    protected Office $office;
    protected AttendanceSetting $policy;
    protected User $adminUser;
    protected User $staffUser;

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
            'regular_allowance_amount' => 50000.00,
            'absence_fine_amount' => 50000.00,
            'overtime_check_in_start' => '18:00:00',
            'overtime_check_out_end' => '22:00:00',
            'minimum_overtime_minutes' => 120,
            'overtime_allowance_amount' => 25000.00,
            'effective_from' => '2026-01-01',
            'is_active' => true,
        ]);

        $adminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $this->adminUser = User::create([
            'office_id' => $this->office->id,
            'name' => 'Admin User',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->adminUser->assignRole($adminRole);

        $this->staffUser = User::create([
            'office_id' => $this->office->id,
            'name' => 'Staff Member',
            'username' => 'staff1',
            'email' => 'staff1@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_bulk_backfill_service_creates_attendance_and_skips_sundays(): void
    {
        /** @var AttendanceBackfillService $service */
        $service = app(AttendanceBackfillService::class);

        // 16 Sep (Wed) to 24 Sep (Thu) 2026 is 9 calendar days, with 1 Sunday (20 Sep).
        // 9 - 1 = 8 working days.
        $result = $service->backfill([
            'user_ids' => [$this->staffUser->id],
            'start_date' => '2026-09-16',
            'end_date' => '2026-09-24',
            'check_in_time' => '08:00:00',
            'check_out_time' => '17:00:00',
            'skip_sundays' => true,
            'skip_holidays' => true,
            'overwrite_existing' => false,
            'include_overtime' => false,
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(8, $result['attendances_created']);
        $this->assertEquals(1, $result['dates_skipped']); // 2026-09-20 (Sunday)
        $this->assertEquals(0, $result['overtimes_created']);

        $attendances = Attendance::where('user_id', $this->staffUser->id)->get();
        $this->assertCount(8, $attendances);

        // Sunday should not exist
        $this->assertNull(Attendance::where('user_id', $this->staffUser->id)->whereDate('attendance_date', '2026-09-20')->first());

        // Check Wednesday 16 Sep details
        $firstRecord = Attendance::where('user_id', $this->staffUser->id)->whereDate('attendance_date', '2026-09-16')->first();
        $this->assertNotNull($firstRecord);
        $this->assertEquals(AttendanceStatus::ON_TIME, $firstRecord->status);
        $this->assertEquals(0, $firstRecord->late_minutes);
        $this->assertEquals(540, $firstRecord->working_minutes); // 08:00 to 17:00 is 9h
        $this->assertTrue($firstRecord->allowance_eligible);
        $this->assertEquals(50000.00, (float) $firstRecord->allowance_amount);
    }

    public function test_bulk_backfill_creates_overtime_records_for_selected_dates(): void
    {
        /** @var AttendanceBackfillService $service */
        $service = app(AttendanceBackfillService::class);

        $result = $service->backfill([
            'user_ids' => [$this->staffUser->id],
            'start_date' => '2026-09-16',
            'end_date' => '2026-09-24',
            'check_in_time' => '08:00:00',
            'check_out_time' => '17:00:00',
            'skip_sundays' => true,
            'skip_holidays' => true,
            'overwrite_existing' => false,
            'include_overtime' => true,
            'overtime_scope' => 'dates',
            'overtime_dates' => ['2026-09-18', '2026-09-22'],
            'overtime_check_in_time' => '18:00:00',
            'overtime_check_out_time' => '21:00:00',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(8, $result['attendances_created']);
        $this->assertEquals(2, $result['overtimes_created']);

        $overtimes = Overtime::whereHas('attendance', fn ($q) => $q->where('user_id', $this->staffUser->id))->get();
        $this->assertCount(2, $overtimes);

        $ot18 = Overtime::whereDate('overtime_date', '2026-09-18')->first();
        $this->assertNotNull($ot18);
        $this->assertEquals(180, $ot18->overtime_minutes); // 18:00 - 21:00
        $this->assertTrue($ot18->allowance_eligible);
        $this->assertEquals(25000.00, (float) $ot18->allowance_amount);
    }

    public function test_bulk_backfill_does_not_overwrite_existing_when_disabled(): void
    {
        // Existing attendance on 2026-09-24
        Attendance::create([
            'user_id' => $this->staffUser->id,
            'office_id' => $this->office->id,
            'attendance_setting_id' => $this->policy->id,
            'attendance_date' => '2026-09-24',
            'status' => AttendanceStatus::LATE,
            'late_minutes' => 30,
            'check_in_at' => Carbon::parse('2026-09-24 08:30:00'),
            'check_out_at' => Carbon::parse('2026-09-24 17:00:00'),
            'working_minutes' => 510,
            'allowance_eligible' => true,
            'allowance_amount' => 50000.00,
            'notes' => 'Existing self attendance',
        ]);

        /** @var AttendanceBackfillService $service */
        $service = app(AttendanceBackfillService::class);

        $result = $service->backfill([
            'user_ids' => [$this->staffUser->id],
            'start_date' => '2026-09-16',
            'end_date' => '2026-09-24',
            'check_in_time' => '08:00:00',
            'check_out_time' => '17:00:00',
            'skip_sundays' => true,
            'skip_holidays' => true,
            'overwrite_existing' => false,
        ]);

        $this->assertTrue($result['success']);
        // 8 working days minus 1 existing = 7 created, 2 skipped (1 Sunday + 1 existing)
        $this->assertEquals(7, $result['attendances_created']);
        $this->assertEquals(2, $result['dates_skipped']);

        // Check that 2026-09-24 was preserved
        $record24 = Attendance::where('user_id', $this->staffUser->id)->whereDate('attendance_date', '2026-09-24')->first();
        $this->assertEquals('Existing self attendance', $record24->notes);
        $this->assertEquals(AttendanceStatus::LATE, $record24->status);
    }

    public function test_action_can_be_executed_via_livewire_on_list_attendance_monitorings(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(ListAttendanceMonitorings::class)
            ->callAction('bulkBackfillAttendance', [
                'user_ids' => [$this->staffUser->id],
                'start_date' => '2026-09-16',
                'end_date' => '2026-09-24',
                'check_in_time' => '08:00:00',
                'check_out_time' => '17:00:00',
                'skip_sundays' => true,
                'skip_holidays' => true,
                'overwrite_existing' => false,
                'include_overtime' => false,
                'notes' => 'Backfilled via Filament action',
            ])
            ->assertHasNoActionErrors();

        $this->assertEquals(8, Attendance::where('user_id', $this->staffUser->id)->count());
    }
}
