<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Office;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AttendanceFineService
{
    protected ?HolidayService $holidayService = null;

    public function __construct(?HolidayService $holidayService = null)
    {
        $this->holidayService = $holidayService ?? app(HolidayService::class);
    }

    /**
     * Get the active absence daily fine rate for an office on a given date.
     */
    public function getDailyFineRate(Office $office, ?string $date = null): float
    {
        $setting = $office->getActiveSettingForDate($date);

        if (!$setting) {
            return 50000.00;
        }

        return (float) ($setting->absence_fine_amount ?? 50000.00);
    }

    /**
     * Calculate working days count between start and end date (inclusive, excluding Sundays and registered holidays).
     */
    public function calculateWorkingDays(Carbon $startDate, Carbon $endDate, ?int $officeId = null): int
    {
        if ($this->holidayService) {
            return $this->holidayService->countWorkingDays($startDate, $endDate, $officeId);
        }

        if ($startDate->gt($endDate)) {
            return 0;
        }

        $period = CarbonPeriod::create($startDate->copy()->startOfDay(), $endDate->copy()->startOfDay());
        $count = 0;

        foreach ($period as $date) {
            if (!$date->isSunday()) {
                $count++;
            }
        }

        return max(1, $count);
    }

    /**
     * Calculate normal fine estimation for a leave request based on office daily rate.
     *
     * @return array{total_days: int, daily_fine_rate: float, normal_fine_amount: float}
     */
    public function calculateLeaveNormalFine(Office $office, Carbon $startDate, Carbon $endDate): array
    {
        $totalDays = $this->calculateWorkingDays($startDate, $endDate, $office->id);
        $dailyRate = $this->getDailyFineRate($office, $startDate->toDateString());
        $normalFine = $totalDays * $dailyRate;

        return [
            'total_days' => $totalDays,
            'daily_fine_rate' => $dailyRate,
            'normal_fine_amount' => (float) $normalFine,
        ];
    }

    /**
     * Calculate user attendance and absence fines summary for a given period.
     */
    public function calculateUserFinesForPeriod(User $user, Carbon $startDate, Carbon $endDate): array
    {
        $office = $user->office;
        $period = CarbonPeriod::create($startDate->copy()->startOfDay(), $endDate->copy()->startOfDay());

        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get()
            ->keyBy(fn ($item) => $item->attendance_date->toDateString());

        $approvedLeaves = LeaveRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->orWhereBetween('end_date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->orWhere(function ($sub) use ($startDate, $endDate) {
                        $sub->where('start_date', '<=', $startDate->toDateString())
                            ->where('end_date', '>=', $endDate->toDateString());
                    });
            })
            ->get();

        $daysBreakdown = [];
        $totalAlphaDays = 0;
        $totalAlphaFine = 0.00;
        $totalApprovedLeaveFine = 0.00;
        $processedLeaveIds = [];

        foreach ($period as $date) {
            $dateStr = $date->toDateString();

            if ($date->isSunday()) {
                continue;
            }

            // Check if date is a registered holiday
            $holiday = $this->holidayService ? $this->holidayService->isHoliday($dateStr, $office?->id) : null;
            if ($holiday) {
                $daysBreakdown[$dateStr] = [
                    'date' => $dateStr,
                    'status' => 'holiday',
                    'fine_amount' => 0.00,
                    'description' => 'Libur: ' . $holiday->name,
                ];
                continue;
            }

            $dailyRate = $office ? $this->getDailyFineRate($office, $dateStr) : 50000.00;

            // Check if user attended
            if ($attendances->has($dateStr) && $attendances->get($dateStr)->isCheckedIn()) {
                $daysBreakdown[$dateStr] = [
                    'date' => $dateStr,
                    'status' => 'present',
                    'fine_amount' => 0.00,
                    'description' => 'Hadir Kerja',
                ];
                continue;
            }

            // Check if user has approved leave
            $matchingLeave = $approvedLeaves->first(function ($leave) use ($dateStr) {
                return $dateStr >= $leave->start_date->toDateString() && $dateStr <= $leave->end_date->toDateString();
            });

            if ($matchingLeave) {
                if (!in_array($matchingLeave->id, $processedLeaveIds)) {
                    $totalApprovedLeaveFine += (float) $matchingLeave->adjusted_fine_amount;
                    $processedLeaveIds[] = $matchingLeave->id;
                }

                $daysBreakdown[$dateStr] = [
                    'date' => $dateStr,
                    'status' => 'approved_leave',
                    'leave_type' => $matchingLeave->leave_type,
                    'leave_request_id' => $matchingLeave->id,
                    'fine_amount' => (float) ($matchingLeave->total_days > 0 ? ($matchingLeave->adjusted_fine_amount / $matchingLeave->total_days) : 0),
                    'description' => 'Izin Disetujui (' . ucfirst($matchingLeave->leave_type) . ')',
                ];
                continue;
            }

            // If past date or ended day with no attendance and no approved leave -> Alpha
            if ($date->isPast()) {
                $totalAlphaDays++;
                $totalAlphaFine += $dailyRate;

                $daysBreakdown[$dateStr] = [
                    'date' => $dateStr,
                    'status' => 'alpha',
                    'fine_amount' => $dailyRate,
                    'description' => 'Tidak Masuk / Alpha',
                ];
            }
        }

        $totalFine = $totalAlphaFine + $totalApprovedLeaveFine;

        return [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'office_name' => $office?->name ?? '-',
            'total_alpha_days' => $totalAlphaDays,
            'total_alpha_fine' => $totalAlphaFine,
            'total_approved_leave_fine' => $totalApprovedLeaveFine,
            'total_fine' => $totalFine,
            'breakdown' => $daysBreakdown,
        ];
    }
}
