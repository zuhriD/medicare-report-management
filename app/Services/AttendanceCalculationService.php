<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceSetting;
use Carbon\Carbon;

class AttendanceCalculationService
{
    /**
     * Calculate working duration in full minutes between check-in and check-out,
     * deducting total paused/break minutes.
     * If check-in is earlier than regular_check_in_start from policy, calculation begins at regular_check_in_start.
     * If check-out exceeds regular_check_out_end from policy, it is capped at regular_check_out_end.
     */
    public function calculateWorkingMinutes(
        Carbon $checkInAt,
        Carbon $checkOutAt,
        int $totalBreakMinutes = 0,
        ?AttendanceSetting $policy = null
    ): int {
        $effectiveCheckInAt = $checkInAt->copy();
        $effectiveCheckOutAt = $checkOutAt->copy();

        if ($policy) {
            $tz = $policy->office?->timezone ?? $checkInAt->timezone;

            // If checked in earlier than regular_check_in_start, duration starts from regular_check_in_start
            if (!empty($policy->regular_check_in_start)) {
                $officeCheckInAt = $checkInAt->copy()->setTimezone($tz);
                $cutoffStart = Carbon::parse($officeCheckInAt->format('Y-m-d') . ' ' . $policy->regular_check_in_start, $tz);
                if ($officeCheckInAt->lt($cutoffStart)) {
                    $effectiveCheckInAt = $cutoffStart;
                }
            }

            // If checked out later than regular_check_out_end, capped at regular_check_out_end
            if (!empty($policy->regular_check_out_end)) {
                $officeCheckOutAt = $checkOutAt->copy()->setTimezone($tz);
                $cutoffEnd = Carbon::parse($officeCheckOutAt->format('Y-m-d') . ' ' . $policy->regular_check_out_end, $tz);
                if ($officeCheckOutAt->gt($cutoffEnd)) {
                    $effectiveCheckOutAt = $cutoffEnd;
                }
            }
        }

        $diffSeconds = $effectiveCheckOutAt->getTimestamp() - $effectiveCheckInAt->getTimestamp();
        if ($diffSeconds <= 0) {
            return 0;
        }

        $grossMinutes = (int) floor($diffSeconds / 60);
        $netMinutes = max(0, $grossMinutes - max(0, $totalBreakMinutes));

        return $netMinutes;
    }

    /**
     * Evaluate check-in punctuality against office policy.
     *
     * @return array{status: AttendanceStatus, is_late: bool, late_minutes: int}
     */
    public function evaluatePunctuality(Carbon $checkInAt, AttendanceSetting $policy): array
    {
        $tz = $policy->office?->timezone ?? $checkInAt->timezone;
        $officeCheckInAt = $checkInAt->copy()->setTimezone($tz);
        $scheduledStart = Carbon::parse($officeCheckInAt->format('Y-m-d') . ' ' . $policy->regular_check_in_start, $tz);
        $toleranceMinutes = (int) ($policy->late_tolerance_minute ?? 0);
        $lateThreshold = $scheduledStart->copy()->addMinutes($toleranceMinutes);

        $diffSeconds = $officeCheckInAt->getTimestamp() - $lateThreshold->getTimestamp();

        if ($diffSeconds > 0) {
            $lateMinutes = (int) ceil($diffSeconds / 60);
            return [
                'status' => AttendanceStatus::LATE,
                'is_late' => true,
                'late_minutes' => $lateMinutes,
            ];
        }

        return [
            'status' => AttendanceStatus::ON_TIME,
            'is_late' => false,
            'late_minutes' => 0,
        ];
    }

    /**
     * Calculate break duration in full minutes between pause and resume.
     */
    public function calculateBreakMinutes(Carbon $pausedAt, Carbon $resumedAt): int
    {
        $diffSeconds = $resumedAt->getTimestamp() - $pausedAt->getTimestamp();
        if ($diffSeconds <= 0) {
            return 0;
        }

        return (int) floor($diffSeconds / 60);
    }

    /**
     * Evaluate eligibility and snapshot amount for regular attendance allowance.
     *
     * @return array{allowance_eligible: bool, allowance_amount: float}
     */
    public function evaluateRegularAllowance(int $workingMinutes, AttendanceSetting $policy): array
    {
        $isEligible = $workingMinutes >= $policy->minimum_regular_minutes;

        return [
            'allowance_eligible' => $isEligible,
            'allowance_amount' => $isEligible ? (float) $policy->regular_allowance_amount : 0.00,
        ];
    }

    /**
     * Calculate overtime duration in full minutes between overtime check-in and check-out.
     * If check-out exceeds overtime_check_out_end from policy, it is capped at overtime_check_out_end.
     */
    public function calculateOvertimeMinutes(
        Carbon $checkInAt,
        Carbon $checkOutAt,
        ?AttendanceSetting $policy = null
    ): int {
        $effectiveCheckOutAt = $checkOutAt->copy();

        if ($policy && !empty($policy->overtime_check_out_end)) {
            $tz = $policy->office?->timezone ?? $checkOutAt->timezone;
            $officeCheckOutAt = $checkOutAt->copy()->setTimezone($tz);
            $cutoffEnd = Carbon::parse($officeCheckOutAt->format('Y-m-d') . ' ' . $policy->overtime_check_out_end, $tz);
            if ($officeCheckOutAt->gt($cutoffEnd)) {
                $effectiveCheckOutAt = $cutoffEnd;
            }
        }

        $diffSeconds = $effectiveCheckOutAt->getTimestamp() - $checkInAt->getTimestamp();
        if ($diffSeconds <= 0) {
            return 0;
        }

        return (int) floor($diffSeconds / 60);
    }

    /**
     * Evaluate eligibility and snapshot amount for overtime allowance.
     *
     * @return array{allowance_eligible: bool, allowance_amount: float}
     */
    public function evaluateOvertimeAllowance(int $overtimeMinutes, AttendanceSetting $policy): array
    {
        $isEligible = $overtimeMinutes >= $policy->minimum_overtime_minutes;

        return [
            'allowance_eligible' => $isEligible,
            'allowance_amount' => $isEligible ? (float) $policy->overtime_allowance_amount : 0.00,
        ];
    }

    /**
     * Format minutes to human readable string (e.g. "6h 30m" or "45m").
     */
    public function formatMinutesToDuration(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        if ($hours > 0 && $remainingMinutes > 0) {
            return "{$hours}h {$remainingMinutes}m";
        }

        if ($hours > 0) {
            return "{$hours}h";
        }

        return "{$remainingMinutes}m";
    }
}
