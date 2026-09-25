<?php

namespace App\Filament\Pages;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\AttendanceSetting;
use App\Models\Office;
use App\Models\Overtime;
use App\Services\AttendanceCalculationService;
use App\Services\AttendancePolicyService;
use App\Services\AttendanceWhatsAppNotificationService;
use App\Services\GeoLocationService;
use App\Services\SelfieStorageService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class MyAttendance extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-camera';

    protected static ?string $navigationGroup = 'HR & Attendance';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'My Attendance';

    protected static ?string $navigationLabel = 'My Attendance';

    protected static string $view = 'filament.pages.my-attendance';

    // State properties for live capture & GPS
    public ?float $latitude = null;
    public ?float $longitude = null;
    public ?float $accuracy = null;
    public ?string $selfie = null; // Base64 data url from webcam
    public ?string $notes = null;

    public bool $isGpsDetected = false;
    public ?float $distanceFromOffice = null;
    public ?bool $isWithinRadius = null;
    public ?string $geofenceMessage = null;

    // Pause / Break state properties
    public string $pauseReason = '';
    public ?string $pauseNotes = null;
    public bool $showPauseModal = false;

    public string $actionType = 'regular_check_in'; // regular_check_in, paused, regular_check_out, ot_check_in, ot_check_out

    public function mount(): void
    {
        $this->evaluateCurrentState();
    }

    /**
     * Get the authenticated user.
     */
    public function getUserProperty()
    {
        return Auth::user();
    }

    /**
     * Get the assigned office of the staff.
     */
    public function getOfficeProperty(): ?Office
    {
        return $this->user?->office;
    }

    /**
     * Get the active policy for the office today.
     */
    public function getPolicyProperty(): ?AttendanceSetting
    {
        if (!$this->office) {
            return null;
        }

        return app(AttendancePolicyService::class)->getActivePolicy($this->office);
    }

    /**
     * Get the office's current local time.
     */
    public function getOfficeNowProperty(): Carbon
    {
        if (!$this->office) {
            return Carbon::now();
        }

        return app(AttendancePolicyService::class)->getOfficeNow($this->office);
    }

    /**
     * Get today's attendance record for the staff.
     */
    public function getTodayAttendanceProperty(): ?Attendance
    {
        $todayDate = $this->officeNow->toDateString();

        return Attendance::where('user_id', $this->user?->id)
            ->whereDate('attendance_date', $todayDate)
            ->first();
    }

    /**
     * Get today's overtime record for the staff.
     */
    public function getTodayOvertimeProperty(): ?Overtime
    {
        return $this->todayAttendance?->overtime;
    }

    /**
     * Evaluate default action and state.
     */
    public function evaluateCurrentState(): void
    {
        if (!$this->todayAttendance) {
            $this->actionType = 'regular_check_in';
        } elseif ($this->todayAttendance->isPaused()) {
            $this->actionType = 'paused';
        } elseif (!$this->todayAttendance->isCheckedOut()) {
            $this->actionType = 'regular_check_out';
        } elseif (!$this->todayOvertime) {
            $this->actionType = 'ot_check_in';
        } elseif (!$this->todayOvertime->isCheckedOut()) {
            $this->actionType = 'ot_check_out';
        } else {
            $this->actionType = 'completed';
        }
    }

    /**
     * Update GPS coordinates received from browser client.
     */
    public function updateCoordinates($lat, $lng, $accuracy = null): void
    {
        $this->latitude = (float) $lat;
        $this->longitude = (float) $lng;
        $this->accuracy = $accuracy ? (float) $accuracy : null;
        $this->isGpsDetected = true;

        if ($this->office) {
            $geoService = app(GeoLocationService::class);
            $validation = $geoService->validateOfficeGeofence($this->office, $this->latitude, $this->longitude);

            $this->isWithinRadius = $validation['is_valid'];
            $this->distanceFromOffice = $validation['distance_meters'];
            $this->geofenceMessage = $validation['message'];
        }
    }

    /**
     * Perform Regular Check-In.
     */
    public function doRegularCheckIn(): void
    {
        $office = $this->office;
        if (!$office || !$office->is_active) {
            Notification::make()
                ->title('Cannot Check-In')
                ->body('You are not assigned to an active office. Please contact HR.')
                ->danger()
                ->send();
            return;
        }

        $policy = $this->policy;
        if (!$policy) {
            Notification::make()
                ->title('Cannot Check-In')
                ->body('No active attendance policy configured for your office today.')
                ->danger()
                ->send();
            return;
        }

        $policyService = app(AttendancePolicyService::class);
        $check = $policyService->canRegularCheckIn($office, $policy);
        if (!$check['allowed']) {
            Notification::make()
                ->title('Check-In Not Allowed')
                ->body($check['reason'])
                ->warning()
                ->send();
            return;
        }

        if (!$this->selfie) {
            Notification::make()
                ->title('Selfie Required')
                ->body('Please take a live selfie photo using the camera before checking in.')
                ->warning()
                ->send();
            return;
        }

        $geoService = app(GeoLocationService::class);
        $geo = $geoService->validateOfficeGeofence($office, $this->latitude, $this->longitude);
        if (!$geo['is_valid']) {
            Notification::make()
                ->title('Outside Geofence')
                ->body($geo['message'])
                ->danger()
                ->send();
            return;
        }

        $now = Carbon::now();
        $todayDate = $this->officeNow->toDateString();
        $rawSelfie = $this->selfie;
        $currentAccuracy = $this->accuracy;
        $currentNotes = $this->notes;

        // Check duplicate
        if (Attendance::where('user_id', $this->user->id)->whereDate('attendance_date', $todayDate)->exists()) {
            Notification::make()
                ->title('Already Checked In')
                ->body('You have already recorded attendance for today.')
                ->warning()
                ->send();
            return;
        }

        // Store selfie to GCS/storage
        $selfieService = app(SelfieStorageService::class);
        $selfiePath = $selfieService->storeSelfie(
            $rawSelfie,
            $this->user->id,
            'check_in',
            $now
        );

        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'office_id' => $office->id,
            'attendance_setting_id' => $policy->id,
            'attendance_date' => $todayDate,
            'check_in_at' => $now,
            'check_in_latitude' => $this->latitude,
            'check_in_longitude' => $this->longitude,
            'check_in_accuracy' => $this->accuracy,
            'check_in_selfie' => $selfiePath,
            'notes' => $currentNotes,
        ]);

        $this->reset(['selfie', 'notes']);
        $this->evaluateCurrentState();

        Notification::make()
            ->title('Check-In Successful!')
            ->body('Your check-in has been recorded at ' . $now->setTimezone($office->timezone)->format('H:i:s') . '.')
            ->success()
            ->send();

        // Dispatch WhatsApp Share Modal
        $waService = app(AttendanceWhatsAppNotificationService::class);
        $groupLink = $waService->getGroupLink($office->whatsapp_group_link ?? null);
        $photoPublicUrl = $waService->resolvePhotoUrl(null, $selfiePath);
        $waMessage = $waService->formatCheckIn($attendance, $currentNotes, $currentAccuracy, $photoPublicUrl);

        $this->dispatch('open-whatsapp-share-modal', [
            'title' => 'Check-In Masuk Berhasil!',
            'action_type' => 'check_in',
            'action_label' => 'Check-In Masuk',
            'message' => $waMessage,
            'photo_data_url' => $rawSelfie,
            'photo_url' => $photoPublicUrl,
            'group_link' => $groupLink,
        ]);
    }

    /**
     * Open the pause attendance modal dialog.
     */
    public function openPauseModal(): void
    {
        $this->pauseReason = '';
        $this->pauseNotes = null;
        $this->showPauseModal = true;
    }

    /**
     * Close the pause attendance modal dialog.
     */
    public function closePauseModal(): void
    {
        $this->showPauseModal = false;
    }

    /**
     * Perform Pause Attendance (Izin Keluar).
     */
    public function pauseAttendance(): void
    {
        $attendance = $this->todayAttendance;
        $policyService = app(AttendancePolicyService::class);
        $check = $policyService->canPauseAttendance($attendance);
        if (!$check['allowed']) {
            Notification::make()
                ->title('Cannot Pause Attendance')
                ->body($check['reason'])
                ->warning()
                ->send();
            return;
        }

        if (empty(trim($this->pauseReason))) {
            Notification::make()
                ->title('Alasan Izin Wajib Diisi')
                ->body('Mohon tuliskan alasan izin keluar kantor sebelum menjeda absensi.')
                ->warning()
                ->send();
            return;
        }

        $now = Carbon::now();
        $rawSelfie = $this->selfie;
        $selfiePath = null;
        if ($rawSelfie) {
            $selfieService = app(SelfieStorageService::class);
            $selfiePath = $selfieService->storeSelfie(
                $rawSelfie,
                $this->user->id,
                'pause',
                $now
            );
        }

        $break = AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'user_id' => $this->user->id,
            'reason' => trim($this->pauseReason),
            'paused_at' => $now,
            'paused_latitude' => $this->latitude,
            'paused_longitude' => $this->longitude,
            'paused_accuracy' => $this->accuracy,
            'paused_selfie' => $selfiePath,
            'notes' => $this->pauseNotes,
        ]);

        $this->reset(['pauseReason', 'pauseNotes', 'selfie', 'showPauseModal']);
        $this->evaluateCurrentState();

        $timeStr = $now->setTimezone($this->office?->timezone ?? config('app.timezone'))->format('H:i:s');
        Notification::make()
            ->title('Izin Keluar Tercatat')
            ->body("Absensi Anda dijeda pada pukul {$timeStr}. Jangan lupa tekan tombol 'Kembali ke Kantor' saat Anda kembali.")
            ->success()
            ->send();

        // Dispatch WhatsApp Share Modal
        $waService = app(AttendanceWhatsAppNotificationService::class);
        $groupLink = $waService->getGroupLink($this->office?->whatsapp_group_link ?? null);
        $photoPublicUrl = $waService->resolvePhotoUrl(null, $selfiePath);
        $waMessage = $waService->formatPause($attendance, $break, $photoPublicUrl);

        $this->dispatch('open-whatsapp-share-modal', [
            'title' => 'Izin Keluar Tercatat',
            'action_type' => 'pause',
            'action_label' => 'Izin Keluar (Jeda)',
            'message' => $waMessage,
            'photo_data_url' => $rawSelfie,
            'photo_url' => $photoPublicUrl,
            'group_link' => $groupLink,
        ]);
    }

    /**
     * Perform Resume Attendance (Kembali ke Kantor).
     */
    public function resumeAttendance(): void
    {
        $attendance = $this->todayAttendance;
        $policyService = app(AttendancePolicyService::class);
        $check = $policyService->canResumeAttendance($attendance);
        if (!$check['allowed']) {
            Notification::make()
                ->title('Cannot Resume Attendance')
                ->body($check['reason'])
                ->warning()
                ->send();
            return;
        }

        $office = $this->office;
        if (!$office) {
            Notification::make()
                ->title('Office Required')
                ->body('Office information is missing.')
                ->danger()
                ->send();
            return;
        }

        $geoService = app(GeoLocationService::class);
        $geo = $geoService->validateOfficeGeofence($office, $this->latitude, $this->longitude);
        if (!$geo['is_valid']) {
            Notification::make()
                ->title('Outside Office Radius')
                ->body('Anda harus berada di dalam radius kantor (' . $office->attendance_radius_meter . 'm) untuk melanjutkan absensi kembali.')
                ->danger()
                ->send();
            return;
        }

        $activeBreak = $attendance->activeBreak();
        if (!$activeBreak) {
            Notification::make()
                ->title('No Active Break')
                ->body('Tidak ada sesi izin keluar aktif yang dapat dilanjutkan.')
                ->warning()
                ->send();
            return;
        }

        $now = Carbon::now();
        $rawSelfie = $this->selfie;
        $selfiePath = null;
        if ($rawSelfie) {
            $selfieService = app(SelfieStorageService::class);
            $selfiePath = $selfieService->storeSelfie(
                $rawSelfie,
                $this->user->id,
                'resume',
                $now
            );
        }

        $calcService = app(AttendanceCalculationService::class);
        $breakMinutes = $calcService->calculateBreakMinutes($activeBreak->paused_at, $now);

        $activeBreak->update([
            'resumed_at' => $now,
            'resumed_latitude' => $this->latitude,
            'resumed_longitude' => $this->longitude,
            'resumed_accuracy' => $this->accuracy,
            'resumed_selfie' => $selfiePath,
            'duration_minutes' => $breakMinutes,
        ]);

        $this->reset(['selfie', 'notes']);
        $this->evaluateCurrentState();

        $durationText = $calcService->formatMinutesToDuration($breakMinutes);
        $timeStr = $now->setTimezone($office->timezone ?? config('app.timezone'))->format('H:i:s');
        Notification::make()
            ->title('Absensi Dilanjutkan')
            ->body("Selamat datang kembali di kantor ({$timeStr})! Durasi izin keluar: {$durationText}.")
            ->success()
            ->send();

        // Dispatch WhatsApp Share Modal
        $waService = app(AttendanceWhatsAppNotificationService::class);
        $groupLink = $waService->getGroupLink($office->whatsapp_group_link ?? null);
        $photoPublicUrl = $waService->resolvePhotoUrl(null, $selfiePath);
        $waMessage = $waService->formatResume($attendance, $activeBreak, $photoPublicUrl);

        $this->dispatch('open-whatsapp-share-modal', [
            'title' => 'Kembali ke Kantor Berhasil',
            'action_type' => 'resume',
            'action_label' => 'Kembali ke Kantor',
            'message' => $waMessage,
            'photo_data_url' => $rawSelfie,
            'photo_url' => $photoPublicUrl,
            'group_link' => $groupLink,
        ]);
    }

    /**
     * Perform Regular Check-Out.
     */
    public function doRegularCheckOut(): void
    {
        $attendance = $this->todayAttendance;
        if (!$attendance || !$attendance->isCheckedIn()) {
            Notification::make()
                ->title('Cannot Check-Out')
                ->body('No active check-in session found for today.')
                ->danger()
                ->send();
            return;
        }

        if ($attendance->isCheckedOut()) {
            Notification::make()
                ->title('Already Checked Out')
                ->body('You have already checked out today.')
                ->warning()
                ->send();
            return;
        }

        if (!$this->selfie) {
            Notification::make()
                ->title('Selfie Required')
                ->body('Please take a live selfie photo before checking out.')
                ->warning()
                ->send();
            return;
        }

        $office = $attendance->office;
        $geoService = app(GeoLocationService::class);
        $geo = $geoService->validateOfficeGeofence($office, $this->latitude, $this->longitude);
        $isPausedSession = $attendance->isPaused();

        // Regular check-out requires geofence unless staff is directly checking out from an active leave/pause session
        if (!$isPausedSession && !$geo['is_valid']) {
            Notification::make()
                ->title('Outside Geofence')
                ->body($geo['message'])
                ->danger()
                ->send();
            return;
        }

        $now = Carbon::now();
        $policy = $attendance->attendanceSetting ?? $this->policy;
        $calcService = app(AttendanceCalculationService::class);
        $rawSelfie = $this->selfie;
        $currentNotes = $this->notes;

        // If there is still an active break open, automatically close it at checkout timestamp
        if ($activeBreak = $attendance->activeBreak()) {
            $breakMinutes = $calcService->calculateBreakMinutes($activeBreak->paused_at, $now);
            $activeBreak->update([
                'resumed_at' => $now,
                'resumed_latitude' => $this->latitude,
                'resumed_longitude' => $this->longitude,
                'resumed_accuracy' => $this->accuracy,
                'duration_minutes' => $breakMinutes,
            ]);
        }

        // Store selfie
        $selfieService = app(SelfieStorageService::class);
        $selfiePath = $selfieService->storeSelfie(
            $rawSelfie,
            $this->user->id,
            'check_out',
            $now
        );

        // Calculate working duration deducting total break minutes & evaluate allowance
        $totalBreakMinutes = $attendance->totalBreakMinutes();
        $workingMinutes = $calcService->calculateWorkingMinutes($attendance->check_in_at, $now, $totalBreakMinutes);
        $allowanceData = $calcService->evaluateRegularAllowance($workingMinutes, $policy);

        $attendance->update([
            'check_out_at' => $now,
            'check_out_latitude' => $this->latitude,
            'check_out_longitude' => $this->longitude,
            'check_out_accuracy' => $this->accuracy,
            'check_out_selfie' => $selfiePath,
            'working_minutes' => $workingMinutes,
            'allowance_eligible' => $allowanceData['allowance_eligible'],
            'allowance_amount' => $allowanceData['allowance_amount'],
            'notes' => $currentNotes ?? $attendance->notes,
        ]);

        $durationText = $calcService->formatMinutesToDuration($workingMinutes);
        $breakInfoText = $totalBreakMinutes > 0 ? " (Istirahat/Izin: " . $calcService->formatMinutesToDuration($totalBreakMinutes) . ")" : "";
        $this->reset(['selfie', 'notes']);
        $this->evaluateCurrentState();

        Notification::make()
            ->title('Check-Out Successful!')
            ->body("Working duration: {$durationText}{$breakInfoText}. Allowance: " . ($allowanceData['allowance_eligible'] ? "Qualified (RM/Rp " . number_format($allowanceData['allowance_amount'], 2) . ")" : "Not qualified") . ".")
            ->success()
            ->send();

        // Dispatch WhatsApp Share Modal
        $waService = app(AttendanceWhatsAppNotificationService::class);
        $groupLink = $waService->getGroupLink($office->whatsapp_group_link ?? null);
        $photoPublicUrl = $waService->resolvePhotoUrl(null, $selfiePath);
        $waMessage = $waService->formatCheckOut($attendance, $currentNotes, $photoPublicUrl);

        $this->dispatch('open-whatsapp-share-modal', [
            'title' => 'Check-Out Pulang Berhasil!',
            'action_type' => 'check_out',
            'action_label' => 'Check-Out Pulang',
            'message' => $waMessage,
            'photo_data_url' => $rawSelfie,
            'photo_url' => $photoPublicUrl,
            'group_link' => $groupLink,
        ]);
    }

    /**
     * Perform Overtime Check-In.
     */
    public function doOvertimeCheckIn(): void
    {
        $attendance = $this->todayAttendance;
        $office = $this->office;
        $policy = $this->policy;

        if (!$office || !$policy || !$attendance) {
            Notification::make()
                ->title('Overtime Not Available')
                ->body('Cannot initiate overtime. Make sure you have completed regular attendance today.')
                ->danger()
                ->send();
            return;
        }

        $policyService = app(AttendancePolicyService::class);
        $check = $policyService->canOvertimeCheckIn($office, $policy, $attendance);
        if (!$check['allowed']) {
            Notification::make()
                ->title('Overtime Not Allowed')
                ->body($check['reason'])
                ->warning()
                ->send();
            return;
        }

        if (!$this->selfie) {
            Notification::make()
                ->title('Selfie Required')
                ->body('Please take a live selfie photo before starting overtime.')
                ->warning()
                ->send();
            return;
        }

        $geoService = app(GeoLocationService::class);
        $geo = $geoService->validateOfficeGeofence($office, $this->latitude, $this->longitude);
        if (!$geo['is_valid']) {
            Notification::make()
                ->title('Outside Geofence')
                ->body($geo['message'])
                ->danger()
                ->send();
            return;
        }

        $now = Carbon::now();
        $todayDate = $this->officeNow->toDateString();
        $rawSelfie = $this->selfie;
        $currentAccuracy = $this->accuracy;
        $currentNotes = $this->notes;

        $selfieService = app(SelfieStorageService::class);
        $selfiePath = $selfieService->storeSelfie(
            $rawSelfie,
            $this->user->id,
            'ot_check_in',
            $now
        );

        $overtime = Overtime::create([
            'attendance_id' => $attendance->id,
            'overtime_date' => $todayDate,
            'check_in_at' => $now,
            'check_in_latitude' => $this->latitude,
            'check_in_longitude' => $this->longitude,
            'check_in_accuracy' => $this->accuracy,
            'check_in_selfie' => $selfiePath,
            'notes' => $currentNotes,
        ]);

        $this->reset(['selfie', 'notes']);
        $this->evaluateCurrentState();

        Notification::make()
            ->title('Overtime Check-In Successful!')
            ->body('Overtime session started at ' . $now->setTimezone($office->timezone)->format('H:i:s') . '.')
            ->success()
            ->send();

        // Dispatch WhatsApp Share Modal
        $waService = app(AttendanceWhatsAppNotificationService::class);
        $groupLink = $waService->getGroupLink($office->whatsapp_group_link ?? null);
        $photoPublicUrl = $waService->resolvePhotoUrl(null, $selfiePath);
        $waMessage = $waService->formatOvertimeCheckIn($overtime, $currentNotes, $currentAccuracy, $photoPublicUrl);

        $this->dispatch('open-whatsapp-share-modal', [
            'title' => 'Mulai Lembur (OT) Berhasil!',
            'action_type' => 'ot_check_in',
            'action_label' => 'Mulai Lembur (OT)',
            'message' => $waMessage,
            'photo_data_url' => $rawSelfie,
            'photo_url' => $photoPublicUrl,
            'group_link' => $groupLink,
        ]);
    }

    /**
     * Perform Overtime Check-Out.
     */
    public function doOvertimeCheckOut(): void
    {
        $overtime = $this->todayOvertime;
        if (!$overtime || !$overtime->isCheckedIn()) {
            Notification::make()
                ->title('Cannot Check-Out Overtime')
                ->body('No active overtime session found for today.')
                ->danger()
                ->send();
            return;
        }

        if ($overtime->isCheckedOut()) {
            Notification::make()
                ->title('Already Checked Out')
                ->body('You have already completed overtime today.')
                ->warning()
                ->send();
            return;
        }

        if (!$this->selfie) {
            Notification::make()
                ->title('Selfie Required')
                ->body('Please take a live selfie photo before ending overtime.')
                ->warning()
                ->send();
            return;
        }

        $office = $this->office;
        $geoService = app(GeoLocationService::class);
        $geo = $geoService->validateOfficeGeofence($office, $this->latitude, $this->longitude);
        if (!$geo['is_valid']) {
            Notification::make()
                ->title('Outside Geofence')
                ->body($geo['message'])
                ->danger()
                ->send();
            return;
        }

        $now = Carbon::now();
        $policy = $this->policy;
        $rawSelfie = $this->selfie;
        $currentNotes = $this->notes;

        $selfieService = app(SelfieStorageService::class);
        $selfiePath = $selfieService->storeSelfie(
            $rawSelfie,
            $this->user->id,
            'ot_check_out',
            $now
        );

        $calcService = app(AttendanceCalculationService::class);
        $otMinutes = $calcService->calculateOvertimeMinutes($overtime->check_in_at, $now);
        $allowanceData = $calcService->evaluateOvertimeAllowance($otMinutes, $policy);

        $overtime->update([
            'check_out_at' => $now,
            'check_out_latitude' => $this->latitude,
            'check_out_longitude' => $this->longitude,
            'check_out_accuracy' => $this->accuracy,
            'check_out_selfie' => $selfiePath,
            'overtime_minutes' => $otMinutes,
            'allowance_eligible' => $allowanceData['allowance_eligible'],
            'allowance_amount' => $allowanceData['allowance_amount'],
            'notes' => $currentNotes ?? $overtime->notes,
        ]);

        $durationText = $calcService->formatMinutesToDuration($otMinutes);
        $this->reset(['selfie', 'notes']);
        $this->evaluateCurrentState();

        Notification::make()
            ->title('Overtime Check-Out Successful!')
            ->body("Overtime duration: {$durationText}. OT Allowance: " . ($allowanceData['allowance_eligible'] ? "Qualified (RM/Rp " . number_format($allowanceData['allowance_amount'], 2) . ")" : "Not qualified") . ".")
            ->success()
            ->send();

        // Dispatch WhatsApp Share Modal
        $waService = app(AttendanceWhatsAppNotificationService::class);
        $groupLink = $waService->getGroupLink($office->whatsapp_group_link ?? null);
        $photoPublicUrl = $waService->resolvePhotoUrl(null, $selfiePath);
        $waMessage = $waService->formatOvertimeCheckOut($overtime, $currentNotes, $photoPublicUrl);

        $this->dispatch('open-whatsapp-share-modal', [
            'title' => 'Selesai Lembur (OT) Berhasil!',
            'action_type' => 'ot_check_out',
            'action_label' => 'Selesai Lembur (OT)',
            'message' => $waMessage,
            'photo_data_url' => $rawSelfie,
            'photo_url' => $photoPublicUrl,
            'group_link' => $groupLink,
        ]);
    }

    /**
     * Get recent attendance history for the authenticated user.
     */
    public function getRecentAttendancesProperty()
    {
        return Attendance::with(['office', 'overtime', 'breaks'])
            ->where('user_id', $this->user?->id)
            ->latest('attendance_date')
            ->take(15)
            ->get();
    }
}
