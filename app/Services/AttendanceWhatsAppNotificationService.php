<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\Overtime;
use Carbon\Carbon;

class AttendanceWhatsAppNotificationService
{
    /**
     * Default static WhatsApp group link for testing / dummy flow.
     */
    public const DEFAULT_STATIC_GROUP_LINK = 'https://chat.whatsapp.com/I7yWf83d9H938ZkmEXAMPLE';

    /**
     * Get the active WhatsApp Group link (uses office setting or static dummy link).
     */
    public function getGroupLink(?string $officeGroupLink = null): string
    {
        return !empty($officeGroupLink) ? $officeGroupLink : self::DEFAULT_STATIC_GROUP_LINK;
    }

    /**
     * Resolve public photo URL for real selfie upload.
     */
    public function resolvePhotoUrl(?string $providedUrl = null, ?string $selfiePath = null): ?string
    {
        $url = !empty($providedUrl) ? $providedUrl : (!empty($selfiePath) ? app(SelfieStorageService::class)->getSelfieUrl($selfiePath) : null);

        if (empty($url)) {
            return null;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        // Convert relative URL / path to full absolute URL
        return url($url);
    }

    /**
     * Format WhatsApp notification message for Regular Check-In.
     */
    public function formatCheckIn(Attendance $attendance, ?string $notes = null, ?float $accuracy = null, ?string $photoUrl = null): string
    {
        $user = $attendance->user;
        $office = $attendance->office;
        $tz = $office?->timezone ?? config('app.timezone');
        $timeStr = $attendance->check_in_at ? $attendance->check_in_at->setTimezone($tz)->format('H:i:s') : Carbon::now($tz)->format('H:i:s');
        $dateStr = $attendance->attendance_date ? Carbon::parse($attendance->attendance_date)->format('d M Y') : Carbon::now($tz)->format('d M Y');

        $gpsInfo = 'Verified Location (At Office)';
        if ($accuracy !== null) {
            $gpsInfo .= ' [Accuracy ±' . round($accuracy) . 'm]';
        }

        $photoLink = $this->resolvePhotoUrl($photoUrl, $attendance->check_in_selfie);

        $lines = [
            '━━━━━━━━━━━━━━━━━━━━━',
            '*ATTENDANCE CHECK-IN REPORT*',
            '━━━━━━━━━━━━━━━━━━━━━',
            '*Name:* ' . ($user?->name ?? 'Staff'),
            '*Office:* ' . ($office?->name ?? 'Office'),
            '*Date:* ' . $dateStr,
            '*Check-In Time:* ' . $timeStr . ' (' . $tz . ')',
            '*Location Status:* ' . $gpsInfo,
        ];

        if ($photoLink) {
            $lines[] = '*Selfie Photo:* ' . $photoLink;
        }

        if ($notes) {
            $lines[] = '*Notes:* ' . $notes;
        }

        $lines[] = '━━━━━━━━━━━━━━━━━━━━━';
        $lines[] = '_Medicare HR Attendance System_';

        return implode("\n", $lines);
    }

    /**
     * Format WhatsApp notification message for Break / Pause.
     */
    public function formatPause(Attendance $attendance, AttendanceBreak $break, ?string $photoUrl = null): string
    {
        $user = $attendance->user;
        $office = $attendance->office;
        $tz = $office?->timezone ?? config('app.timezone');
        $timeStr = $break->paused_at ? $break->paused_at->setTimezone($tz)->format('H:i:s') : Carbon::now($tz)->format('H:i:s');
        $photoLink = $this->resolvePhotoUrl($photoUrl, $break->paused_selfie);

        $lines = [
            '━━━━━━━━━━━━━━━━━━━━━',
            '*BREAK / OUT OF OFFICE NOTICE*',
            '━━━━━━━━━━━━━━━━━━━━━',
            '*Name:* ' . ($user?->name ?? 'Staff'),
            '*Office:* ' . ($office?->name ?? 'Office'),
            '*Break Time:* ' . $timeStr . ' (' . $tz . ')',
            '*Reason:* ' . ($break->reason ?: 'Break / Personal Duty'),
        ];

        if ($photoLink) {
            $lines[] = '*Selfie Photo:* ' . $photoLink;
        }

        if ($break->notes) {
            $lines[] = '*Notes:* ' . $break->notes;
        }

        $lines[] = '━━━━━━━━━━━━━━━━━━━━━';
        $lines[] = '_Attendance status is paused until returning to the office_';

        return implode("\n", $lines);
    }

    /**
     * Format WhatsApp notification message for Resume / Return to office.
     */
    public function formatResume(Attendance $attendance, AttendanceBreak $break, ?string $photoUrl = null): string
    {
        $user = $attendance->user;
        $office = $attendance->office;
        $tz = $office?->timezone ?? config('app.timezone');
        $timeStr = $break->resumed_at ? $break->resumed_at->setTimezone($tz)->format('H:i:s') : Carbon::now($tz)->format('H:i:s');
        $calcService = app(AttendanceCalculationService::class);
        $durationStr = $calcService->formatMinutesToDuration($break->duration_minutes ?? 0);
        $photoLink = $this->resolvePhotoUrl($photoUrl, $break->resumed_selfie);

        $lines = [
            '━━━━━━━━━━━━━━━━━━━━━',
            '*RETURN TO OFFICE REPORT*',
            '━━━━━━━━━━━━━━━━━━━━━',
            '*Name:* ' . ($user?->name ?? 'Staff'),
            '*Office:* ' . ($office?->name ?? 'Office'),
            '*Return Time:* ' . $timeStr . ' (' . $tz . ')',
            '*Break Duration:* ' . $durationStr,
            '*Location Status:* Verified at Office',
        ];

        if ($photoLink) {
            $lines[] = '*Selfie Photo:* ' . $photoLink;
        }

        $lines[] = '━━━━━━━━━━━━━━━━━━━━━';
        $lines[] = '_Attendance resumed_';

        return implode("\n", $lines);
    }

    /**
     * Format WhatsApp notification message for Regular Check-Out.
     */
    public function formatCheckOut(Attendance $attendance, ?string $notes = null, ?string $photoUrl = null): string
    {
        $user = $attendance->user;
        $office = $attendance->office;
        $tz = $office?->timezone ?? config('app.timezone');
        $checkInStr = $attendance->check_in_at ? $attendance->check_in_at->setTimezone($tz)->format('H:i:s') : '—';
        $checkOutStr = $attendance->check_out_at ? $attendance->check_out_at->setTimezone($tz)->format('H:i:s') : Carbon::now($tz)->format('H:i:s');
        
        $calcService = app(AttendanceCalculationService::class);
        $workingDuration = $calcService->formatMinutesToDuration($attendance->working_minutes ?? 0);
        $breakDuration = $calcService->formatMinutesToDuration($attendance->totalBreakMinutes());

        $allowanceStatus = $attendance->allowance_eligible ? 'Qualified' : 'Not Qualified';
        $photoLink = $this->resolvePhotoUrl($photoUrl, $attendance->check_out_selfie);

        $lines = [
            '━━━━━━━━━━━━━━━━━━━━━',
            '*ATTENDANCE CHECK-OUT REPORT*',
            '━━━━━━━━━━━━━━━━━━━━━',
            '*Name:* ' . ($user?->name ?? 'Staff'),
            '*Office:* ' . ($office?->name ?? 'Office'),
            '*Check-In Time:* ' . $checkInStr,
            '*Check-Out Time:* ' . $checkOutStr . ' (' . $tz . ')',
            '*Total Break Time:* ' . $breakDuration,
            '*Effective Working Duration:* ' . $workingDuration,
            '*Attendance Allowance:* ' . $allowanceStatus,
        ];

        if ($photoLink) {
            $lines[] = '*Selfie Photo:* ' . $photoLink;
        }

        if ($notes) {
            $lines[] = '*Notes:* ' . $notes;
        }

        $lines[] = '━━━━━━━━━━━━━━━━━━━━━';
        $lines[] = '_Medicare HR Attendance System_';

        return implode("\n", $lines);
    }

    /**
     * Format WhatsApp notification message for Overtime Check-In.
     */
    public function formatOvertimeCheckIn(Overtime $overtime, ?string $notes = null, ?float $accuracy = null, ?string $photoUrl = null): string
    {
        $attendance = $overtime->attendance;
        $user = $attendance?->user;
        $homeOffice = $attendance?->office;
        $otOffice = $overtime->actual_office ?? $homeOffice;
        $tz = $otOffice?->timezone ?? $homeOffice?->timezone ?? config('app.timezone');
        $timeStr = $overtime->check_in_at ? $overtime->check_in_at->setTimezone($tz)->format('H:i:s') : Carbon::now($tz)->format('H:i:s');
        $photoLink = $this->resolvePhotoUrl($photoUrl, $overtime->check_in_selfie);

        $lines = [
            '━━━━━━━━━━━━━━━━━━━━━',
            '*OVERTIME CHECK-IN NOTICE*',
            '━━━━━━━━━━━━━━━━━━━━━',
            '*Name:* ' . ($user?->name ?? 'Staff'),
        ];

        if ($otOffice && $homeOffice && $otOffice->id !== $homeOffice->id) {
            $lines[] = '*Overtime Office:* ' . $otOffice->name . ' (Lembur Cabang)';
            $lines[] = '*Home Office:* ' . $homeOffice->name;
        } else {
            $lines[] = '*Office:* ' . ($otOffice?->name ?? $homeOffice?->name ?? 'Office');
        }

        $lines[] = '*Start Time:* ' . $timeStr . ' (' . $tz . ')';
        $lines[] = '*Location Status:* At Overtime Location';

        if ($photoLink) {
            $lines[] = '*Selfie Photo:* ' . $photoLink;
        }

        if ($notes) {
            $lines[] = '*Task Notes:* ' . $notes;
        }

        $lines[] = '━━━━━━━━━━━━━━━━━━━━━';
        $lines[] = '_Medicare HR Attendance System_';

        return implode("\n", $lines);
    }

    /**
     * Format WhatsApp notification message for Overtime Check-Out.
     */
    public function formatOvertimeCheckOut(Overtime $overtime, ?string $notes = null, ?string $photoUrl = null): string
    {
        $attendance = $overtime->attendance;
        $user = $attendance?->user;
        $homeOffice = $attendance?->office;
        $otOffice = $overtime->actual_office ?? $homeOffice;
        $tz = $otOffice?->timezone ?? $homeOffice?->timezone ?? config('app.timezone');
        $inStr = $overtime->check_in_at ? $overtime->check_in_at->setTimezone($tz)->format('H:i:s') : '—';
        $outStr = $overtime->check_out_at ? $overtime->check_out_at->setTimezone($tz)->format('H:i:s') : Carbon::now($tz)->format('H:i:s');

        $calcService = app(AttendanceCalculationService::class);
        $otDuration = $calcService->formatMinutesToDuration($overtime->overtime_minutes ?? 0);
        $allowanceStatus = $overtime->allowance_eligible ? 'Qualified' : 'Not Qualified';
        $photoLink = $this->resolvePhotoUrl($photoUrl, $overtime->check_out_selfie);

        $lines = [
            '━━━━━━━━━━━━━━━━━━━━━',
            '*OVERTIME CHECK-OUT REPORT*',
            '━━━━━━━━━━━━━━━━━━━━━',
            '*Name:* ' . ($user?->name ?? 'Staff'),
        ];

        if ($otOffice && $homeOffice && $otOffice->id !== $homeOffice->id) {
            $lines[] = '*Overtime Office:* ' . $otOffice->name . ' (Lembur Cabang)';
            $lines[] = '*Home Office:* ' . $homeOffice->name;
        } else {
            $lines[] = '*Office:* ' . ($otOffice?->name ?? $homeOffice?->name ?? 'Office');
        }

        $lines[] = '*Start Time:* ' . $inStr;
        $lines[] = '*End Time:* ' . $outStr . ' (' . $tz . ')';
        $lines[] = '*Overtime Duration:* ' . $otDuration;
        $lines[] = '*Overtime Allowance:* ' . $allowanceStatus;

        if ($photoLink) {
            $lines[] = '*Selfie Photo:* ' . $photoLink;
        }

        if ($notes) {
            $lines[] = '*Result Notes:* ' . $notes;
        }

        $lines[] = '━━━━━━━━━━━━━━━━━━━━━';
        $lines[] = '_Medicare HR Attendance System_';

        return implode("\n", $lines);
    }
}
