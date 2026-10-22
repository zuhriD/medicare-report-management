<?php

namespace App\Filament\Pages;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Office;
use App\Models\Overtime;
use App\Models\User;
use App\Services\AttendanceCalculationService;
use App\Services\AttendanceFineService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class MyEarnings extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'HR & Attendance';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'My Earnings';

    protected static ?string $navigationLabel = 'My Earnings';

    protected static string $view = 'filament.pages.my-earnings';

    public string $startDate = '';
    public string $endDate = '';
    public string $periodPreset = 'this_month'; // 'this_month', 'last_month', 'cutoff_16_15', 'cutoff_21_20', 'custom'
    public ?int $selectedMonth = null;
    public ?int $selectedYear = null;
    public string $activeTab = 'summary'; // 'summary', 'attendances', 'overtimes', 'leaves_fines'

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->endOfMonth()->toDateString();
        $this->selectedMonth = (int) now()->month;
        $this->selectedYear = (int) now()->year;
    }

    public function updatedPeriodPreset(): void
    {
        $today = now();
        if ($this->periodPreset === 'this_month') {
            $this->startDate = $today->copy()->startOfMonth()->toDateString();
            $this->endDate = $today->copy()->endOfMonth()->toDateString();
        } elseif ($this->periodPreset === 'last_month') {
            $this->startDate = $today->copy()->subMonth()->startOfMonth()->toDateString();
            $this->endDate = $today->copy()->subMonth()->endOfMonth()->toDateString();
        } elseif ($this->periodPreset === 'cutoff_16_15') {
            $end = Carbon::create($today->year, $today->month, 15);
            $start = $end->copy()->subMonth()->day(16);
            $this->startDate = $start->toDateString();
            $this->endDate = $end->toDateString();
        } elseif ($this->periodPreset === 'cutoff_21_20') {
            $end = Carbon::create($today->year, $today->month, 20);
            $start = $end->copy()->subMonth()->day(21);
            $this->startDate = $start->toDateString();
            $this->endDate = $end->toDateString();
        }
    }

    public function updatedStartDate(): void
    {
        $this->periodPreset = 'custom';
    }

    public function updatedEndDate(): void
    {
        $this->periodPreset = 'custom';
    }

    public function updatedSelectedMonth(): void
    {
        if ($this->selectedMonth && $this->selectedYear) {
            $this->startDate = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->startOfDay()->toDateString();
            $this->endDate = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->endOfMonth()->toDateString();
            $this->periodPreset = 'custom';
        }
    }

    public function updatedSelectedYear(): void
    {
        if ($this->selectedMonth && $this->selectedYear) {
            $this->startDate = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->startOfDay()->toDateString();
            $this->endDate = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->endOfMonth()->toDateString();
            $this->periodPreset = 'custom';
        }
    }

    public function getUserProperty(): ?User
    {
        return Auth::user();
    }

    public function getOfficeProperty(): ?Office
    {
        return $this->user?->office;
    }

    /**
     * Get months list for filter dropdown.
     *
     * @return array<int, string>
     */
    public function getMonthsProperty(): array
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];
    }

    /**
     * Get years list for filter dropdown.
     *
     * @return array<int, int>
     */
    public function getYearsProperty(): array
    {
        $current = (int) now()->format('Y');
        $years = [];
        for ($y = $current - 2; $y <= $current + 1; $y++) {
            $years[$y] = $y;
        }
        return $years;
    }

    /**
     * Compute full earnings and breakdown data for the logged-in staff member.
     */
    public function getEarningsDataProperty(): array
    {
        $user = $this->user;
        if (!$user) {
            return [];
        }

        if ($this->startDate && $this->endDate) {
            $startDate = Carbon::parse($this->startDate)->startOfDay();
            $endDate = Carbon::parse($this->endDate)->endOfDay();
        } elseif ($this->selectedMonth && $this->selectedYear) {
            $startDate = Carbon::createFromDate($this->selectedYear, $this->selectedMonth, 1)->startOfDay();
            $endDate = $startDate->copy()->endOfMonth()->endOfDay();
        } else {
            $startDate = now()->startOfMonth()->startOfDay();
            $endDate = now()->endOfMonth()->endOfDay();
        }

        if ($endDate->lt($startDate)) {
            $endDate = $startDate->copy()->endOfDay();
        }

        $office = $this->office;
        $fineService = app(AttendanceFineService::class);
        $calcService = app(AttendanceCalculationService::class);

        // 1. Total working days in selected range
        $workingDays = $fineService->calculateWorkingDays($startDate, $endDate, $office?->id);

        // 2. Fetch attendances in period
        $attendances = Attendance::with(['office', 'overtime', 'breaks'])
            ->where('user_id', $user->id)
            ->whereDate('attendance_date', '>=', $startDate->toDateString())
            ->whereDate('attendance_date', '<=', $endDate->toDateString())
            ->orderBy('attendance_date', 'desc')
            ->get();

        // 3. Fetch overtime records in period
        $overtimes = Overtime::whereHas('attendance', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->whereDate('overtime_date', '>=', $startDate->toDateString())
            ->whereDate('overtime_date', '<=', $endDate->toDateString())
            ->orderBy('overtime_date', 'desc')
            ->get();

        // 4. Fetch leave requests overlapping period
        $leaveRequests = LeaveRequest::with('approver')
            ->where('user_id', $user->id)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->orWhereBetween('end_date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->orWhere(function ($sub) use ($startDate, $endDate) {
                        $sub->where('start_date', '<=', $startDate->toDateString())
                            ->where('end_date', '>=', $endDate->toDateString());
                    });
            })
            ->orderBy('start_date', 'desc')
            ->get();

        // 5. Calculate fines using fine service
        $finesSummary = $fineService->calculateUserFinesForPeriod($user, $startDate, $endDate);

        // 6. Aggregate metrics
        $presentDays = $attendances->filter(fn ($a) => $a->isCheckedIn())->count();
        $regularAllowanceEligibleDays = $attendances->where('allowance_eligible', true)->count();
        $regularAllowanceTotal = (float) $attendances->sum('allowance_amount');
        $totalWorkingMinutes = (int) $attendances->sum('working_minutes');

        $overtimeCount = $overtimes->count();
        $overtimeEligibleCount = $overtimes->where('allowance_eligible', true)->count();
        $overtimeAllowanceTotal = (float) $overtimes->sum('allowance_amount');
        $totalOvertimeMinutes = (int) $overtimes->sum('overtime_minutes');

        $totalAlphaDays = (int) ($finesSummary['total_alpha_days'] ?? 0);
        $totalAlphaFine = (float) ($finesSummary['total_alpha_fine'] ?? 0);
        $totalLeaveFine = (float) ($finesSummary['total_approved_leave_fine'] ?? 0);
        $totalFines = (float) ($finesSummary['total_fine'] ?? 0);

        $grossAllowance = $regularAllowanceTotal + $overtimeAllowanceTotal;
        // NET is attendance allowance + overtime allowance (fines are deducted directly from base salary)
        $netEarnings = $grossAllowance;

        // Currency label based on office
        $currency = ($office && str_contains(strtolower($office->timezone ?? ''), 'kuala_lumpur')) ? 'RM' : 'Rp';

        if ($startDate->isSameMonth($endDate) && $startDate->day === 1 && $startDate->copy()->endOfMonth()->isSameDay($endDate)) {
            $periodLabel = $startDate->translatedFormat('F Y');
        } elseif ($startDate->isSameDay($endDate)) {
            $periodLabel = $startDate->translatedFormat('d F Y');
        } elseif ($startDate->isSameMonth($endDate)) {
            $periodLabel = $startDate->format('d') . ' - ' . $endDate->translatedFormat('d F Y');
        } else {
            $periodLabel = $startDate->translatedFormat('d M Y') . ' - ' . $endDate->translatedFormat('d M Y');
        }

        return [
            'period_label' => $periodLabel,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'currency' => $currency,
            'working_days' => $workingDays,
            'present_days' => $presentDays,
            'regular_allowance_eligible_days' => $regularAllowanceEligibleDays,
            'regular_allowance_total' => $regularAllowanceTotal,
            'total_working_minutes' => $totalWorkingMinutes,
            'total_working_duration' => $calcService->formatMinutesToDuration($totalWorkingMinutes),
            'overtime_count' => $overtimeCount,
            'overtime_eligible_count' => $overtimeEligibleCount,
            'overtime_allowance_total' => $overtimeAllowanceTotal,
            'total_overtime_minutes' => $totalOvertimeMinutes,
            'total_overtime_duration' => $calcService->formatMinutesToDuration($totalOvertimeMinutes),
            'total_alpha_days' => $totalAlphaDays,
            'total_alpha_fine' => $totalAlphaFine,
            'total_leave_fine' => $totalLeaveFine,
            'total_fines' => $totalFines,
            'gross_allowance' => $grossAllowance,
            'net_earnings' => $netEarnings,
            'attendances' => $attendances,
            'overtimes' => $overtimes,
            'leave_requests' => $leaveRequests,
            'fines_breakdown' => $finesSummary['breakdown'] ?? [],
        ];
    }
}
