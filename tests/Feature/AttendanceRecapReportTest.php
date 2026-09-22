<?php

namespace Tests\Feature;

use App\Models\Office;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceRecapReportTest extends TestCase
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
            'name' => 'Admin Test',
        ]);
    }

    public function test_guest_cannot_access_export_endpoint()
    {
        $response = $this->get('/reports/attendance-recap/export?month=9&year=2026&format=pdf');
        $response->assertRedirect('/login');
    }

    public function test_attendance_recap_report_page_can_be_rendered()
    {
        $this->actingAs($this->user);
        \Livewire\Livewire::test(\App\Filament\Pages\AttendanceRecapReport::class)
            ->assertSuccessful();
    }

    public function test_authenticated_user_can_download_pdf_export_by_month()
    {
        $response = $this->actingAs($this->user)
            ->get('/reports/attendance-recap/export?month=9&year=2026&format=pdf');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_authenticated_user_can_download_pdf_export_by_custom_date_range()
    {
        $response = $this->actingAs($this->user)
            ->get('/reports/attendance-recap/export?start_date=2026-08-16&end_date=2026-09-15&format=pdf');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_authenticated_user_can_download_csv_export_by_custom_date_range()
    {
        $response = $this->actingAs($this->user)
            ->get('/reports/attendance-recap/export?start_date=2026-08-16&end_date=2026-09-15&format=csv');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
