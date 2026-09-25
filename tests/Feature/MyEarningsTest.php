<?php

namespace Tests\Feature;

use App\Filament\Pages\MyEarnings;
use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\Office;
use App\Models\Overtime;
use App\Models\User;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MyEarningsTest extends TestCase
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
            'name' => 'Jane Doe',
            'username' => 'janedoe',
            'email' => 'jane@medicare.com',
            'password' => bcrypt('password'),
        ]);

        // Grant shield page permission if required
        Permission::findOrCreate('page_MyEarnings', 'web');
        $this->user->givePermissionTo('page_MyEarnings');
    }

    public function test_my_earnings_page_can_be_rendered()
    {
        $this->actingAs($this->user);

        Livewire::test(MyEarnings::class)
            ->assertSuccessful()
            ->assertSee('Jane Doe')
            ->assertSee('Kuala Lumpur HQ')
            ->assertSee('Tunjangan Hadir')
            ->assertSee('Uang Lembur (OT)')
            ->assertSee('Pendapatan Bersih (Net)');
    }

    public function test_my_earnings_computes_allowances_and_net_correctly()
    {
        $this->actingAs($this->user);

        $testMonth = 9;
        $testYear = 2026;

        // 1. Create a regular attendance with allowance
        $att = Attendance::create([
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'attendance_setting_id' => $this->policy->id,
            'attendance_date' => '2026-09-10',
            'check_in_at' => '2026-09-10 08:00:00',
            'check_out_at' => '2026-09-10 17:00:00',
            'working_minutes' => 540,
            'allowance_eligible' => true,
            'allowance_amount' => 50.00,
        ]);

        // 2. Create an overtime session with allowance
        Overtime::create([
            'attendance_id' => $att->id,
            'overtime_date' => '2026-09-10',
            'check_in_at' => '2026-09-10 18:00:00',
            'check_out_at' => '2026-09-10 20:30:00',
            'overtime_minutes' => 150,
            'allowance_eligible' => true,
            'allowance_amount' => 30.00,
        ]);

        // 3. Test Livewire component calculations
        $component = Livewire::test(MyEarnings::class)
            ->set('selectedMonth', $testMonth)
            ->set('selectedYear', $testYear)
            ->assertSuccessful()
            ->assertSee('RM 50')
            ->assertSee('RM 30')
            ->assertSee('RM 80')
            ->assertSee('Rincian Absensi Reguler (1)')
            ->assertSee('Rincian Lembur / OT (1)');

        $earningsData = $component->get('earningsData');
        $this->assertEquals(50.00, $earningsData['regular_allowance_total']);
        $this->assertEquals(30.00, $earningsData['overtime_allowance_total']);
        $this->assertEquals(80.00, $earningsData['net_earnings']);
    }
}
