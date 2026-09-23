<?php

namespace App\Services;

use App\Models\AttendanceSetting;
use Carbon\Carbon;

class AttendanceCalculationService
{
    /**
     * Calculate working duration in full minutes between check-in and check-out,
     * deducting total paused/break minutes.
     */
    public function calculateWorkingMinutes(Carbon $checkInAt, Carbon $checkOutAt, int $totalBreakMinutes = 0): int
    {
        $diffSeconds = $checkOutAt->getTimestamp() - $checkInAt->getTimestamp();
        if ($diffSeconds <= 0) {
            return 0;
        }

        $grossMinutes = (int) floor($diffSeconds / 60);
        $netMinutes = max(0, $grossMinutes - max(0, $totalBreakMinutes));

        return $netMinutes;
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
     */
    public function calculateOvertimeMinutes(Carbon $checkInAt, Carbon $checkOutAt): int
    {
        $diffSeconds = $checkOutAt->getTimestamp() - $checkInAt->getTimestamp();
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
