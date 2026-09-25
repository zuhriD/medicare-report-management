<?php

namespace Tests\Feature;

use App\Filament\Pages\MyAttendance;
use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\Office;
use App\Models\User;
use App\Services\AttendanceWhatsAppNotificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AttendanceWhatsAppNotificationTest extends TestCase
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

        Permission::findOrCreate('page_MyAttendance', 'web');
        $this->user->givePermissionTo('page_MyAttendance');
    }

    public function test_service_formats_check_in_message_with_photo_link()
    {
        $checkInTime = Carbon::create(2026, 9, 25, 8, 5, 0, 'Asia/Kuala_Lumpur');
        $photoUrl = 'https://storage.googleapis.com/medicare-report/attendance-selfies/2026/09/25/16/check_in_1790308445_9jqodg.webp';

        $att = Attendance::create([
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'attendance_setting_id' => $this->policy->id,
            'attendance_date' => $checkInTime->toDateString(),
            'check_in_at' => $checkInTime,
            'check_in_latitude' => 3.1340000,
            'check_in_longitude' => 101.6860000,
            'check_in_accuracy' => 10.5,
        ]);

        $service = app(AttendanceWhatsAppNotificationService::class);
        $message = $service->formatCheckIn($att, 'Siap bertugas', 10.5, $photoUrl);

        $this->assertStringContainsString('LAPORAN ABSENSI MASUK', $message);
        $this->assertStringContainsString('John Doe', $message);
        $this->assertStringContainsString('Kuala Lumpur HQ', $message);
        $this->assertStringContainsString('Siap bertugas', $message);
        $this->assertStringContainsString('Akurasi ±11m', $message);
        $this->assertStringContainsString('📸 *Foto Selfie:* ' . $photoUrl, $message);
    }

    public function test_service_resolves_static_group_link()
    {
        $service = app(AttendanceWhatsAppNotificationService::class);
        $groupLink = $service->getGroupLink(null);

        $this->assertStringStartsWith('https://chat.whatsapp.com/', $groupLink);
    }

    public function test_livewire_dispatches_share_modal_on_check_in()
    {
        $this->actingAs($this->user);
        Carbon::setTestNow('2026-09-25 08:30:00');

        $fakeSelfie = 'data:image/jpeg;base64,' . base64_encode('fake-image-content');

        Livewire::test(MyAttendance::class)
            ->set('latitude', 3.1340000)
            ->set('longitude', 101.6860000)
            ->set('accuracy', 12.0)
            ->set('selfie', $fakeSelfie)
            ->set('notes', 'Checkin harian')
            ->call('doRegularCheckIn')
            ->assertDispatched('open-whatsapp-share-modal');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->user->id,
            'check_in_accuracy' => 12.0,
        ]);
    }
}
