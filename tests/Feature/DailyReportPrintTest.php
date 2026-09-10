<?php

namespace Tests\Feature;

use App\Models\DailyReport;
use App\Models\Module;
use App\Models\SubModule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyReportPrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_print_page(): void
    {
        $response = $this->get(route('daily-reports.print', ['date' => '2026-08-15']));

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_daily_report_print_page(): void
    {
        $user = User::factory()->create(['name' => 'John Developer']);
        $module = Module::query()->create(['name' => 'Core Module']);
        $subModule = SubModule::query()->create(['name' => 'Web App', 'module_id' => $module->id]);

        DailyReport::query()->create([
            'user_id' => $user->id,
            'sub_module_id' => $subModule->id,
            'report_date' => '2026-08-15',
            'description' => '<p>Implemented authentication features.</p>',
        ]);

        $response = $this->actingAs($user)
            ->get(route('daily-reports.print', ['date' => '2026-08-15']));

        $response->assertStatus(200);
        $response->assertSee('Daily Progress Report');
        $response->assertSee('John Developer');
        $response->assertSee('Core Module');
        $response->assertSee('Implemented authentication features.');
    }

    public function test_authenticated_user_can_download_daily_report_pdf(): void
    {
        $user = User::factory()->create(['name' => 'Jane Admin']);
        $module = Module::query()->create(['name' => 'API Module']);
        $subModule = SubModule::query()->create(['name' => 'REST Endpoint', 'module_id' => $module->id]);

        DailyReport::query()->create([
            'user_id' => $user->id,
            'sub_module_id' => $subModule->id,
            'report_date' => '2026-08-15',
            'description' => '<p>Created report API.</p>',
        ]);

        $response = $this->actingAs($user)
            ->get(route('daily-reports.print', ['date' => '2026-08-15', 'pdf' => 1]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
