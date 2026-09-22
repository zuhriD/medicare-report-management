<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\Office;
use App\Models\Overtime;
use Carbon\Carbon;

class AttendancePolicyService
{
    /**
     * Get the active policy for an office on a given date in office timezone.
     */
    public function getActivePolicy(Office $office, ?Carbon $date = null): ?AttendanceSetting
    {
        $timezone = $office->timezone ?? config('app.timezone', 'Asia/Kuala_Lumpur');
        $date = $date ? $date->copy()->setTimezone($timezone) : Carbon::now($timezone);

        return $office->getActiveSettingForDate($date->toDateString());
    }

    /**
     * Get the current Carbon instance in the office's timezone.
     */
    public function getOfficeNow(Office $office): Carbon
    {
        $timezone = $office->timezone ?? config('app.timezone', 'Asia/Kuala_Lumpur');
        return Carbon::now()->setTimezone($timezone);
    }

    /**
     * Validate whether regular check-in is currently allowed for the office policy.
     *
     * @return array{allowed: bool, reason: ?string, current_time: string, start_time: string, end_time: string}
     */
    public function canRegularCheckIn(Office $office, AttendanceSetting $policy, ?Carbon $now = null): array
    {
        $timezone = $office->timezone ?? config('app.timezone', 'Asia/Kuala_Lumpur');
        $now = $now ? $now->copy()->setTimezone($timezone) : Carbon::now($timezone);

        $currentTimeStr = $now->format('H:i:s');
        $startTimeStr = Carbon::parse($policy->regular_check_in_start)->format('H:i:s');
        $endTimeStr = Carbon::parse($policy->regular_check_out_end)->format('H:i:s');

        // Check if too early
        if ($currentTimeStr < $startTimeStr) {
            return [
                'allowed' => false,
                'reason' => "Check-in has not opened yet. Regular check-in starts at {$startTimeStr} ({$timezone}).",
                'current_time' => $currentTimeStr,
                'start_time' => $startTimeStr,
                'end_time' => $endTimeStr,
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
            'current_time' => $currentTimeStr,
            'start_time' => $startTimeStr,
            'end_time' => $endTimeStr,
        ];
    }

    /**
     * Validate whether regular check-out is currently allowed.
     *
     * @return array{allowed: bool, reason: ?string}
     */
    public function canRegularCheckOut(Attendance $attendance): array
    {
        if (!$attendance->isCheckedIn()) {
            return [
                'allowed' => false,
                'reason' => 'You have not checked in today.',
            ];
        }

        if ($attendance->isCheckedOut()) {
            return [
                'allowed' => false,
                'reason' => 'You have already checked out today.',
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
        ];
    }

    /**
     * Validate whether staff can pause attendance (izin keluar).
     *
     * @return array{allowed: bool, reason: ?string}
     */
    public function canPauseAttendance(?Attendance $attendance): array
    {
        if (!$attendance || !$attendance->isCheckedIn()) {
            return [
                'allowed' => false,
                'reason' => 'You must check in first before requesting leave permission / pause.',
            ];
        }

        if ($attendance->isCheckedOut()) {
            return [
                'allowed' => false,
                'reason' => 'You have already checked out for today.',
            ];
        }

        if ($attendance->isPaused()) {
            return [
                'allowed' => false,
                'reason' => 'You are already in leave permission / pause status.',
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
        ];
    }

    /**
     * Validate whether staff can resume attendance (kembali ke kantor).
     *
     * @return array{allowed: bool, reason: ?string}
     */
    public function canResumeAttendance(?Attendance $attendance): array
    {
        if (!$attendance || !$attendance->isCheckedIn() || $attendance->isCheckedOut()) {
            return [
                'allowed' => false,
                'reason' => 'No active attendance session found.',
            ];
        }

        if (!$attendance->isPaused()) {
            return [
                'allowed' => false,
                'reason' => 'Attendance is not currently paused.',
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
        ];
    }

    /**
     * Validate whether overtime check-in is currently allowed.
     *
     * @return array{allowed: bool, reason: ?string, current_time: string, start_time: string, end_time: string}
     */
    public function canOvertimeCheckIn(
        Office $office,
        AttendanceSetting $policy,
        ?Attendance $attendance,
        ?Carbon $now = null
    ): array {
        $timezone = $office->timezone ?? config('app.timezone', 'Asia/Kuala_Lumpur');
        $now = $now ? $now->copy()->setTimezone($timezone) : Carbon::now($timezone);

        $currentTimeStr = $now->format('H:i:s');
        $startTimeStr = Carbon::parse($policy->overtime_check_in_start)->format('H:i:s');
        $endTimeStr = Carbon::parse($policy->overtime_check_out_end)->format('H:i:s');

        // 1. Regular attendance must exist
        if (!$attendance || !$attendance->isCheckedIn()) {
            return [
                'allowed' => false,
                'reason' => 'Overtime requires a valid regular attendance record for today.',
                'current_time' => $currentTimeStr,
                'start_time' => $startTimeStr,
                'end_time' => $endTimeStr,
            ];
        }

        // 2. Regular attendance must be checked out
        if (!$attendance->isCheckedOut()) {
            return [
                'allowed' => false,
                'reason' => 'You must check out from regular attendance before starting overtime.',
                'current_time' => $currentTimeStr,
                'start_time' => $startTimeStr,
                'end_time' => $endTimeStr,
            ];
        }

        // 3. Regular working duration must meet minimum requirement
        if ($attendance->working_minutes < $policy->minimum_regular_minutes) {
            $neededHours = round($policy->minimum_regular_minutes / 60, 1);
            return [
                'allowed' => false,
                'reason' => "You are not eligible for overtime. Minimum regular working time is {$policy->minimum_regular_minutes} minutes ({$neededHours} hours).",
                'current_time' => $currentTimeStr,
                'start_time' => $startTimeStr,
                'end_time' => $endTimeStr,
            ];
        }

        // 4. Overtime record must not already exist
        if ($attendance->overtime()->exists()) {
            return [
                'allowed' => false,
                'reason' => 'An overtime session has already been recorded for today.',
                'current_time' => $currentTimeStr,
                'start_time' => $startTimeStr,
                'end_time' => $endTimeStr,
            ];
        }

        // 5. Must be within OT window
        if ($currentTimeStr < $startTimeStr) {
            return [
                'allowed' => false,
                'reason' => "Overtime check-in has not opened yet. Window starts at {$startTimeStr} ({$timezone}).",
                'current_time' => $currentTimeStr,
                'start_time' => $startTimeStr,
                'end_time' => $endTimeStr,
            ];
        }

        if ($currentTimeStr > $endTimeStr) {
            return [
                'allowed' => false,
                'reason' => "Overtime check-in window closed at {$endTimeStr} ({$timezone}).",
                'current_time' => $currentTimeStr,
                'start_time' => $startTimeStr,
                'end_time' => $endTimeStr,
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
            'current_time' => $currentTimeStr,
            'start_time' => $startTimeStr,
            'end_time' => $endTimeStr,
        ];
    }

    /**
     * Validate whether overtime check-out is currently allowed.
     *
     * @return array{allowed: bool, reason: ?string}
     */
    public function canOvertimeCheckOut(?Overtime $overtime): array
    {
        if (!$overtime || !$overtime->isCheckedIn()) {
            return [
                'allowed' => false,
                'reason' => 'You have not checked in for overtime today.',
            ];
        }

        if ($overtime->isCheckedOut()) {
            return [
                'allowed' => false,
                'reason' => 'You have already checked out from overtime today.',
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
        ];
    }
}
