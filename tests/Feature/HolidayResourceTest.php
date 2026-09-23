<?php

namespace Tests\Feature;

use App\Filament\Resources\HolidayResource;
use App\Filament\Resources\HolidayResource\Pages\CreateHoliday;
use App\Filament\Resources\HolidayResource\Pages\ListHolidays;
use App\Models\Holiday;
use App\Models\Office;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HolidayResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Office $office;

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

        $this->user = User::factory()->create([
            'office_id' => $this->office->id,
            'name' => 'Admin Holiday Test',
        ]);
    }

    public function test_holiday_list_page_can_be_rendered()
    {
        $this->actingAs($this->user);

        Holiday::create([
            'holiday_date' => '2026-08-17',
            'name' => 'Hari Kemerdekaan RI',
            'country_code' => 'ID',
        ]);

        Livewire::test(ListHolidays::class)
            ->assertSuccessful()
            ->assertSee('Hari Kemerdekaan RI');
    }

    public function test_user_can_create_manual_holiday()
    {
        $this->actingAs($this->user);

        Livewire::test(CreateHoliday::class)
            ->fillForm([
                'holiday_date' => '2026-10-15',
                'name' => 'Libur Ulang Tahun Kantor',
                'office_id' => $this->office->id,
                'country_code' => 'ID',
                'is_national_holiday' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('holidays', [
            'name' => 'Libur Ulang Tahun Kantor',
            'office_id' => $this->office->id,
            'is_national_holiday' => 0,
        ]);
    }
}
