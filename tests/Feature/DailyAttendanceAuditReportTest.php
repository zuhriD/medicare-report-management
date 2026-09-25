<?php

namespace Tests\Feature;

use App\Filament\Pages\DailyAttendanceAuditReport;
use App\Models\Office;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DailyAttendanceAuditReportTest extends TestCase
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
            'name' => 'Admin Audit Test',
        ]);
    }

    public function test_guest_cannot_access_audit_export_endpoint()
    {
        $response = $this->get('/reports/daily-attendance-audit/export?date=2026-09-22&format=pdf');
        $response->assertRedirect('/login');
    }

    public function test_daily_attendance_audit_report_page_can_be_rendered()
    {
        $this->actingAs($this->user);
        Livewire::test(DailyAttendanceAuditReport::class)
            ->assertSuccessful();
    }

    public function test_authenticated_user_can_download_audit_pdf()
    {
        $response = $this->actingAs($this->user)
            ->get('/reports/daily-attendance-audit/export?date=2026-09-22&format=pdf');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_authenticated_user_can_download_audit_csv()
    {
        $response = $this->actingAs($this->user)
            ->get('/reports/daily-attendance-audit/export?date=2026-09-22&format=csv');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
