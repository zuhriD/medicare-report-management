<?php

namespace App\Filament\Pages;

use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\Office;
use App\Models\Overtime;
use App\Services\AttendanceCalculationService;
use App\Services\AttendancePolicyService;
use App\Services\GeoLocationService;
use App\Services\SelfieStorageService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class MyAttendance extends Page
{
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

    public string $actionType = 'regular_check_in'; // regular_check_in, regular_check_out, ot_check_in, ot_check_out

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
            $this->selfie,
            $this->user->id,
            'check_in',
            $now
        );

        Attendance::create([
            'user_id' => $this->user->id,
            'office_id' => $office->id,
            'attendance_setting_id' => $policy->id,
            'attendance_date' => $todayDate,
            'check_in_at' => $now,
            'check_in_latitude' => $this->latitude,
            'check_in_longitude' => $this->longitude,
            'check_in_accuracy' => $this->accuracy,
            'check_in_selfie' => $selfiePath,
            'notes' => $this->notes,
        ]);

        $this->reset(['selfie', 'notes']);
        $this->evaluateCurrentState();

        Notification::make()
            ->title('Check-In Successful!')
            ->body('Your check-in has been recorded at ' . $now->setTimezone($office->timezone)->format('H:i:s') . '.')
            ->success()
            ->send();
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
        if (!$geo['is_valid']) {
            Notification::make()
                ->title('Outside Geofence')
                ->body($geo['message'])
                ->danger()
                ->send();
            return;
        }

        $now = Carbon::now();
        $policy = $attendance->attendanceSetting ?? $this->policy;

        // Store selfie
        $selfieService = app(SelfieStorageService::class);
        $selfiePath = $selfieService->storeSelfie(
            $this->selfie,
            $this->user->id,
            'check_out',
            $now
        );

        // Calculate working duration & allowance
        $calcService = app(AttendanceCalculationService::class);
        $workingMinutes = $calcService->calculateWorkingMinutes($attendance->check_in_at, $now);
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
            'notes' => $this->notes ?? $attendance->notes,
        ]);

        $durationText = $calcService->formatMinutesToDuration($workingMinutes);
        $this->reset(['selfie', 'notes']);
        $this->evaluateCurrentState();

        Notification::make()
            ->title('Check-Out Successful!')
            ->body("Working duration: {$durationText}. Allowance: " . ($allowanceData['allowance_eligible'] ? "Qualified (RM/Rp " . number_format($allowanceData['allowance_amount'], 2) . ")" : "Not qualified") . ".")
            ->success()
            ->send();
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

        $selfieService = app(SelfieStorageService::class);
        $selfiePath = $selfieService->storeSelfie(
            $this->selfie,
            $this->user->id,
            'ot_check_in',
            $now
        );

        Overtime::create([
            'attendance_id' => $attendance->id,
            'overtime_date' => $todayDate,
            'check_in_at' => $now,
            'check_in_latitude' => $this->latitude,
            'check_in_longitude' => $this->longitude,
            'check_in_accuracy' => $this->accuracy,
            'check_in_selfie' => $selfiePath,
            'notes' => $this->notes,
        ]);

        $this->reset(['selfie', 'notes']);
        $this->evaluateCurrentState();

        Notification::make()
            ->title('Overtime Check-In Successful!')
            ->body('Overtime session started at ' . $now->setTimezone($office->timezone)->format('H:i:s') . '.')
            ->success()
            ->send();
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

        $selfieService = app(SelfieStorageService::class);
        $selfiePath = $selfieService->storeSelfie(
            $this->selfie,
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
            'notes' => $this->notes ?? $overtime->notes,
        ]);

        $durationText = $calcService->formatMinutesToDuration($otMinutes);
        $this->reset(['selfie', 'notes']);
        $this->evaluateCurrentState();

        Notification::make()
            ->title('Overtime Check-Out Successful!')
            ->body("Overtime duration: {$durationText}. OT Allowance: " . ($allowanceData['allowance_eligible'] ? "Qualified (RM/Rp " . number_format($allowanceData['allowance_amount'], 2) . ")" : "Not qualified") . ".")
            ->success()
            ->send();
    }

    /**
     * Get recent attendance history for the authenticated user.
     */
    public function getRecentAttendancesProperty()
    {
        return Attendance::with(['office', 'overtime'])
            ->where('user_id', $this->user?->id)
            ->latest('attendance_date')
            ->take(15)
            ->get();
    }
}
