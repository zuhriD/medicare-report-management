<?php

namespace Tests\Unit;

use App\Models\Holiday;
use App\Models\Office;
use App\Models\User;
use App\Services\AttendanceFineService;
use App\Services\HolidayService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HolidayServiceTest extends TestCase
{
    use RefreshDatabase;

    protected HolidayService $holidayService;
    protected AttendanceFineService $fineService;
    protected Office $officeMalang;
    protected Office $officeMalaysia;

    protected function setUp(): void
    {
        parent::setUp();

        $this->holidayService = new HolidayService();
        $this->fineService = new AttendanceFineService($this->holidayService);

        $this->officeMalang = Office::create([
            'name' => 'Kantor Malang',
            'latitude' => -7.966620,
            'longitude' => 112.632632,
            'attendance_radius_meter' => 100,
            'timezone' => 'Asia/Jakarta',
            'is_active' => true,
        ]);

        $this->officeMalaysia = Office::create([
            'name' => 'Kantor Malaysia',
            'latitude' => 3.139003,
            'longitude' => 101.686855,
            'attendance_radius_meter' => 100,
            'timezone' => 'Asia/Kuala_Lumpur',
            'is_active' => true,
        ]);
    }

    public function test_is_holiday_detects_global_and_office_specific_holidays()
    {
        // Global holiday: 2026-08-17 (Kemerdekaan RI)
        Holiday::create([
            'holiday_date' => '2026-08-17',
            'name' => 'Hari Kemerdekaan RI',
            'office_id' => null,
            'country_code' => 'ID',
        ]);

        // Specific holiday for Malaysia only: 2026-08-31 (Hari Kebangsaan Malaysia)
        Holiday::create([
            'holiday_date' => '2026-08-31',
            'name' => 'Hari Kebangsaan Malaysia',
            'office_id' => $this->officeMalaysia->id,
            'country_code' => 'MY',
        ]);

        // 2026-08-17 should be holiday for Malang and Malaysia
        $this->assertNotNull($this->holidayService->isHoliday('2026-08-17', $this->officeMalang->id));
        $this->assertNotNull($this->holidayService->isHoliday('2026-08-17', $this->officeMalaysia->id));

        // 2026-08-31 should be holiday for Malaysia, but NOT for Malang
        $this->assertNotNull($this->holidayService->isHoliday('2026-08-31', $this->officeMalaysia->id));
        $this->assertNull($this->holidayService->isHoliday('2026-08-31', $this->officeMalang->id));
    }

    public function test_count_working_days_excludes_sundays_and_holidays()
    {
        // 2026-08-17 (Monday) to 2026-08-23 (Sunday): 7 calendar days
        // Mon (17): Holiday
        // Tue (18), Wed (19), Thu (20), Fri (21), Sat (22): 5 working days
        // Sun (23): Sunday (Libur)
        // Total working days should be 5
        Holiday::create([
            'holiday_date' => '2026-08-17',
            'name' => 'Hari Kemerdekaan RI',
            'office_id' => null,
            'country_code' => 'ID',
        ]);

        $start = Carbon::parse('2026-08-17');
        $end = Carbon::parse('2026-08-23');

        $workingDays = $this->holidayService->countWorkingDays($start, $end, $this->officeMalang->id);
        $this->assertEquals(5, $workingDays);
    }

    public function test_sync_holidays_from_api_fetches_and_persists_data()
    {
        Http::fake([
            '*' => Http::response([
                [
                    'date' => '2026-01-01',
                    'localName' => 'Tahun Baru Masehi',
                    'name' => 'New Year\'s Day',
                    'countryCode' => 'ID',
                ],
                [
                    'date' => '2026-08-17',
                    'localName' => 'Hari Kemerdekaan RI',
                    'name' => 'Independence Day',
                    'countryCode' => 'ID',
                ],
            ], 200),
        ]);

        $result = $this->holidayService->syncHolidaysFromApi(2026, 'ID', $this->officeMalang->id);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['synced']);
        $this->assertDatabaseHas('holidays', [
            'name' => 'Hari Kemerdekaan RI',
            'office_id' => $this->officeMalang->id,
            'country_code' => 'ID',
        ]);
    }
}
