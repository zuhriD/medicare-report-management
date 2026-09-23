<?php

namespace App\Filament\Pages;

use App\Models\Office;
use App\Models\User;
use App\Services\AttendanceRecapService;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class AttendanceRecapReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'HR & Attendance';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Attendance & Financial Recap';

    protected static ?string $navigationLabel = 'Attendance & Financial Recap';

    protected static string $view = 'filament.pages.attendance-recap-report';

    public string $periodType = 'monthly'; // monthly, cutoff_16_15, cutoff_21_20, custom
    public int $selectedMonth = 9;
    public int $selectedYear = 2026;
    public string $startDate = '';
    public string $endDate = '';

    public ?int $selectedOfficeId = null;
    public ?int $selectedUserId = null;

    public ?array $selectedStaffDetail = null;
    public bool $showDetailModal = false;

    public static function canAccess(): bool
    {
        return true;
    }

    public function mount(): void
    {
        $this->selectedMonth = (int) now()->month;
        $this->selectedYear = (int) now()->year;
        $this->syncDatesFromPeriodType();

        // If regular user (non-admin), lock to self
        $user = auth()->user();
        if ($user && method_exists($user, 'hasRole') && !$user->hasRole(['admin', 'super_admin', 'lead', 'Admin', 'Super Admin', 'Manager'])) {
            $this->selectedUserId = $user->id;
            $this->selectedOfficeId = $user->office_id;
        }
    }

    public function updatedPeriodType(): void
    {
        $this->syncDatesFromPeriodType();
    }

    public function updatedSelectedMonth(): void
    {
        if ($this->periodType !== 'custom') {
            $this->syncDatesFromPeriodType();
        }
    }

    public function updatedSelectedYear(): void
    {
        if ($this->periodType !== 'custom') {
            $this->syncDatesFromPeriodType();
        }
    }

    protected function syncDatesFromPeriodType(): void
    {
        $year = $this->selectedYear ?: (int) now()->year;
        $month = $this->selectedMonth ?: (int) now()->month;

        if ($this->periodType === 'monthly') {
            $start = Carbon::create($year, $month, 1);
            $this->startDate = $start->startOfDay()->toDateString();
            $this->endDate = $start->copy()->endOfMonth()->toDateString();
        } elseif ($this->periodType === 'cutoff_16_15') {
            $currentPeriodEnd = Carbon::create($year, $month, 15);
            $previousPeriodStart = $currentPeriodEnd->copy()->subMonth()->day(16);
            $this->startDate = $previousPeriodStart->toDateString();
            $this->endDate = $currentPeriodEnd->toDateString();
        } elseif ($this->periodType === 'cutoff_21_20') {
            $currentPeriodEnd = Carbon::create($year, $month, 20);
            $previousPeriodStart = $currentPeriodEnd->copy()->subMonth()->day(21);
            $this->startDate = $previousPeriodStart->toDateString();
            $this->endDate = $currentPeriodEnd->toDateString();
        }
    }

    public function getMonthsProperty(): array
    {
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[$m] = Carbon::create(null, $m, 1)->translatedFormat('F');
        }
        return $months;
    }

    public function getYearsProperty(): array
    {
        $currentYear = now()->year;
        $years = [];
        for ($y = $currentYear - 2; $y <= $currentYear + 1; $y++) {
            $years[$y] = (string) $y;
        }
        return $years;
    }

    public function getOfficesProperty(): Collection
    {
        return Office::orderBy('name')->get();
    }

    public function getUsersProperty(): Collection
    {
        $query = User::orderBy('name');
        if ($this->selectedOfficeId) {
            $query->where('office_id', $this->selectedOfficeId);
        }
        return $query->get();
    }

    public function getRecapProperty(): array
    {
        $recapService = app(AttendanceRecapService::class);
        $startDateStr = $this->startDate ?: now()->startOfMonth()->toDateString();
        $endDateStr = $this->endDate ?: now()->endOfMonth()->toDateString();

        $start = Carbon::parse($startDateStr);
        $end = Carbon::parse($endDateStr);

        return $recapService->getRecapByDateRange(
            $start,
            $end,
            $this->selectedOfficeId,
            $this->selectedUserId
        );
    }

    public function openDetailModal(int $userId): void
    {
        $recap = $this->getRecapProperty();
        $staff = collect($recap['staff_data'])->firstWhere('user_id', $userId);

        if ($staff) {
            $this->selectedStaffDetail = $staff;
            $this->showDetailModal = true;
        }
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->selectedStaffDetail = null;
    }

    public function getPdfExportUrlProperty(): string
    {
        $start = $this->startDate ?: now()->startOfMonth()->toDateString();
        $end = $this->endDate ?: now()->endOfMonth()->toDateString();

        return route('attendance-recap.export', [
            'start_date' => $start,
            'end_date' => $end,
            'office_id' => $this->selectedOfficeId,
            'user_id' => $this->selectedUserId,
            'format' => 'pdf',
        ]);
    }

    public function getCsvExportUrlProperty(): string
    {
        $start = $this->startDate ?: now()->startOfMonth()->toDateString();
        $end = $this->endDate ?: now()->endOfMonth()->toDateString();

        return route('attendance-recap.export', [
            'start_date' => $start,
            'end_date' => $end,
            'office_id' => $this->selectedOfficeId,
            'user_id' => $this->selectedUserId,
            'format' => 'csv',
        ]);
    }
}
