<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Office;
use App\Models\Overtime;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AttendanceRecapService
{
    protected AttendanceFineService $fineService;
    protected HolidayService $holidayService;

    public function __construct(
        AttendanceFineService $fineService,
        ?HolidayService $holidayService = null
    ) {
        $this->fineService = $fineService;
        $this->holidayService = $holidayService ?? app(HolidayService::class);
    }

    /**
     * Generate monthly attendance & financial recap data (wrapper for date range).
     */
    public function getMonthlyRecap(int $month, int $year, ?int $officeId = null, ?int $userId = null): array
    {
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth()->endOfDay();

        return $this->getRecapByDateRange($startDate, $endDate, $officeId, $userId);
    }

    /**
     * Generate attendance & financial recap data for any custom date range.
     *
     * @return array{
     *   period: array{start_date: string, end_date: string, period_label: string, working_days: int},
     *   summary: array,
     *   staff_data: array,
     *   offices: array
     * }
     */
    public function getRecapByDateRange(Carbon $startDate, Carbon $endDate, ?int $officeId = null, ?int $userId = null): array
    {
        $startDate = $startDate->copy()->startOfDay();
        $endDate = $endDate->copy()->endOfDay();

        if ($endDate->lt($startDate)) {
            $endDate = $startDate->copy()->endOfDay();
        }

        // For alpha calculation, do not evaluate future dates beyond today
        $today = now()->endOfDay();
        $calcEndDate = $endDate->gt($today) ? $today : $endDate;

        $workingDays = $this->fineService->calculateWorkingDays($startDate, $endDate, $officeId);

        // Build User Query
        $userQuery = User::query()->with(['office.attendanceSettings']);

        if ($officeId) {
            $userQuery->where('office_id', $officeId);
        }

        if ($userId) {
            $userQuery->where('id', $userId);
        }

        $users = $userQuery->orderBy('name')->get();

        $staffData = [];
        $grandTotalPresent = 0;
        $grandTotalLeaves = 0;
        $grandTotalAlpha = 0;
        $grandTotalRegularAllowance = 0.00;
        $grandTotalOvertimeAllowance = 0.00;
        $grandTotalFine = 0.00;
        $grandTotalNetAllowance = 0.00;
        $grandTotalWorkingMinutes = 0;
        $grandTotalOvertimeMinutes = 0;

        foreach ($users as $user) {
            $office = $user->office;
            $dailyFineRate = $office ? $this->fineService->getDailyFineRate($office, $startDate->toDateString()) : 50000.00;

            // Fetch Attendances with Overtime
            $attendances = Attendance::with('overtime')
                ->where('user_id', $user->id)
                ->whereDate('attendance_date', '>=', $startDate->toDateString())
                ->whereDate('attendance_date', '<=', $endDate->toDateString())
                ->get()
                ->keyBy(fn ($item) => is_string($item->attendance_date) ? substr($item->attendance_date, 0, 10) : $item->attendance_date->toDateString());

            // Fetch Approved Leaves
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

            $presentDays = 0;
            $userWorkingMinutes = 0;
            $userRegularAllowance = 0.00;
            $userOvertimeMinutes = 0;
            $userOvertimeAllowance = 0.00;
            $userAlphaDays = 0;
            $userAlphaFine = 0.00;
            $userLeaveDays = 0;
            $userLeaveFine = 0.00;
            $processedLeaveIds = [];
            $dailyBreakdown = [];

            $period = CarbonPeriod::create($startDate->copy()->startOfDay(), $endDate->copy()->startOfDay());

            foreach ($period as $date) {
                $dateStr = $date->toDateString();
                $isSunday = $date->isSunday();

                $att = $attendances->get($dateStr);
                $dayOvertime = $att?->overtime;
                $dayWorkingMinutes = 0;
                $dayRegularAllowance = 0.00;
                $dayOtAllowance = $dayOvertime && $dayOvertime->allowance_eligible ? (float) $dayOvertime->allowance_amount : 0.00;

                if ($dayOvertime) {
                    $userOvertimeMinutes += (int) $dayOvertime->overtime_minutes;
                    $userOvertimeAllowance += $dayOtAllowance;
                }

                $userHoliday = $this->holidayService->isHoliday($dateStr, $office?->id);

                // Check attendance (including Sunday or Holiday work)
                if ($att && $att->isCheckedIn()) {
                    $presentDays++;
                    $dayWorkingMinutes = (int) $att->working_minutes;
                    $userWorkingMinutes += $dayWorkingMinutes;

                    if ($att->allowance_eligible) {
                        $dayRegularAllowance = (float) $att->allowance_amount;
                        $userRegularAllowance += $dayRegularAllowance;
                    }

                    $statusLabel = ($isSunday || $userHoliday) ? 'Hadir (Hari Libur)' : 'Hadir';

                    $dailyBreakdown[$dateStr] = [
                        'date' => $dateStr,
                        'day_name' => $date->translatedFormat('l'),
                        'status' => 'present',
                        'status_label' => $statusLabel,
                        'check_in_at' => $att->check_in_at?->format('H:i:s'),
                        'check_out_at' => $att->check_out_at?->format('H:i:s'),
                        'working_minutes' => $dayWorkingMinutes,
                        'regular_allowance' => $dayRegularAllowance,
                        'overtime_allowance' => $dayOtAllowance,
                        'fine_amount' => 0.00,
                    ];
                    continue;
                }

                if ($isSunday || $userHoliday) {
                    $holidayLabel = $userHoliday ? 'Hari Libur (' . $userHoliday->name . ')' : 'Hari Libur (Minggu)';
                    $dailyBreakdown[$dateStr] = [
                        'date' => $dateStr,
                        'day_name' => $date->translatedFormat('l'),
                        'status' => 'holiday',
                        'status_label' => $holidayLabel,
                        'working_minutes' => 0,
                        'regular_allowance' => 0.00,
                        'overtime_allowance' => $dayOtAllowance,
                        'fine_amount' => 0.00,
                    ];
                    continue;
                }

                // Check approved leave
                $matchingLeave = $approvedLeaves->first(function ($leave) use ($dateStr) {
                    return $dateStr >= $leave->start_date->toDateString() && $dateStr <= $leave->end_date->toDateString();
                });

                if ($matchingLeave) {
                    $userLeaveDays++;
                    if (!in_array($matchingLeave->id, $processedLeaveIds)) {
                        $userLeaveFine += (float) $matchingLeave->adjusted_fine_amount;
                        $processedLeaveIds[] = $matchingLeave->id;
                    }

                    $portionFine = $matchingLeave->total_days > 0 ? ((float) $matchingLeave->adjusted_fine_amount / $matchingLeave->total_days) : 0;

                    $dailyBreakdown[$dateStr] = [
                        'date' => $dateStr,
                        'day_name' => $date->translatedFormat('l'),
                        'status' => 'leave',
                        'status_label' => 'Izin: ' . ucfirst($matchingLeave->leave_type),
                        'leave_type' => $matchingLeave->leave_type,
                        'working_minutes' => 0,
                        'regular_allowance' => 0.00,
                        'overtime_allowance' => $dayOtAllowance,
                        'fine_amount' => $portionFine,
                    ];
                    continue;
                }

                // If not present and no leave on past/today working day -> Alpha
                if ($date->isPast() || $date->isToday()) {
                    $userAlphaDays++;
                    $userAlphaFine += $dailyFineRate;

                    $dailyBreakdown[$dateStr] = [
                        'date' => $dateStr,
                        'day_name' => $date->translatedFormat('l'),
                        'status' => 'alpha',
                        'status_label' => 'Alpha (Tanpa Izin)',
                        'working_minutes' => 0,
                        'regular_allowance' => 0.00,
                        'overtime_allowance' => $dayOtAllowance,
                        'fine_amount' => $dailyFineRate,
                    ];
                } else {
                    $dailyBreakdown[$dateStr] = [
                        'date' => $dateStr,
                        'day_name' => $date->translatedFormat('l'),
                        'status' => 'upcoming',
                        'status_label' => 'Belum Berjalan',
                        'working_minutes' => 0,
                        'regular_allowance' => 0.00,
                        'overtime_allowance' => $dayOtAllowance,
                        'fine_amount' => 0.00,
                    ];
                }
            }

            $userTotalFine = $userAlphaFine + $userLeaveFine;
            $userGrossAllowance = $userRegularAllowance + $userOvertimeAllowance;
            // Denda presensi dipotong langsung dari Gaji Pokok (bukan dari Net Allowance)
            $userNetAllowance = $userRegularAllowance + $userOvertimeAllowance;
            $attendanceRate = $workingDays > 0 ? round(($presentDays / $workingDays) * 100, 1) : 0;

            $staffData[] = [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'office_id' => $office?->id,
                'office_name' => $office?->name ?? 'Belum Ditugaskan',
                'working_days' => $workingDays,
                'present_days' => $presentDays,
                'leave_days' => $userLeaveDays,
                'alpha_days' => $userAlphaDays,
                'attendance_rate' => $attendanceRate,
                'working_minutes' => $userWorkingMinutes,
                'working_hours' => round($userWorkingMinutes / 60, 1),
                'overtime_minutes' => $userOvertimeMinutes,
                'overtime_hours' => round($userOvertimeMinutes / 60, 1),
                'regular_allowance' => $userRegularAllowance,
                'overtime_allowance' => $userOvertimeAllowance,
                'gross_allowance' => $userGrossAllowance,
                'alpha_fine' => $userAlphaFine,
                'leave_fine' => $userLeaveFine,
                'total_fine' => $userTotalFine,
                'net_allowance' => $userNetAllowance,
                'daily_breakdown' => $dailyBreakdown,
            ];

            $grandTotalPresent += $presentDays;
            $grandTotalLeaves += $userLeaveDays;
            $grandTotalAlpha += $userAlphaDays;
            $grandTotalRegularAllowance += $userRegularAllowance;
            $grandTotalOvertimeAllowance += $userOvertimeAllowance;
            $grandTotalFine += $userTotalFine;
            $grandTotalNetAllowance += $userNetAllowance;
            $grandTotalWorkingMinutes += $userWorkingMinutes;
            $grandTotalOvertimeMinutes += $userOvertimeMinutes;
        }

        $totalStaff = count($staffData);
        $avgAttendanceRate = $totalStaff > 0 ? round(collect($staffData)->avg('attendance_rate'), 1) : 0;

        $periodLabel = $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y');
        if ($startDate->isSameMonth($endDate)) {
            $monthName = $startDate->translatedFormat('F Y');
        } else {
            $monthName = $startDate->translatedFormat('d M') . ' - ' . $endDate->translatedFormat('d M Y');
        }

        return [
            'period' => [
                'month' => (int) $startDate->month,
                'year' => (int) $startDate->year,
                'month_name' => $monthName,
                'period_label' => $periodLabel,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'working_days' => $workingDays,
            ],
            'summary' => [
                'total_staff' => $totalStaff,
                'working_days' => $workingDays,
                'total_present' => $grandTotalPresent,
                'total_leaves' => $grandTotalLeaves,
                'total_alpha' => $grandTotalAlpha,
                'average_attendance_rate' => $avgAttendanceRate,
                'total_working_hours' => round($grandTotalWorkingMinutes / 60, 1),
                'total_overtime_hours' => round($grandTotalOvertimeMinutes / 60, 1),
                'total_regular_allowance' => $grandTotalRegularAllowance,
                'total_overtime_allowance' => $grandTotalOvertimeAllowance,
                'total_gross_allowance' => $grandTotalRegularAllowance + $grandTotalOvertimeAllowance,
                'total_fine' => $grandTotalFine,
                'total_net_allowance' => $grandTotalNetAllowance,
            ],
            'staff_data' => $staffData,
            'offices' => Office::orderBy('name')->get(['id', 'name'])->toArray(),
        ];
    }
}
