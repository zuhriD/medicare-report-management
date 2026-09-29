<?php

namespace App\Services;

use App\Models\AllowancePeriod;
use App\Models\AllowancePeriodItem;
use App\Models\AllowancePeriodStaff;
use App\Models\Attendance;
use App\Models\Overtime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AllowanceCalculationService
{
    /**
     * Calculate attendance and overtime allowances for a specific staff in a date range.
     *
     * @return array{
     *     user_id: int,
     *     staff: User,
     *     total_attendance_days: int,
     *     total_attendance_amount: float,
     *     total_overtime_minutes: int,
     *     total_overtime_amount: float,
     *     total_allowance: float,
     *     items: array
     * }
     */
    public function calculateForStaff(User $staff, Carbon $startDate, Carbon $endDate): array
    {
        $startStr = $startDate->copy()->startOfDay()->toDateString();
        $endStr = $endDate->copy()->endOfDay()->toDateString();

        // 1. Fetch eligible attendances
        $attendances = Attendance::query()
            ->where('user_id', $staff->id)
            ->whereDate('attendance_date', '>=', $startStr)
            ->whereDate('attendance_date', '<=', $endStr)
            ->where('allowance_eligible', true)
            ->where('allowance_amount', '>', 0)
            ->orderBy('attendance_date')
            ->get();

        $totalAttendanceDays = $attendances->count();
        $totalAttendanceAmount = (float) $attendances->sum('allowance_amount');

        $items = [];

        foreach ($attendances as $att) {
            $attDate = is_string($att->attendance_date) ? substr($att->attendance_date, 0, 10) : $att->attendance_date->toDateString();
            $items[] = [
                'attendance_id' => $att->id,
                'overtime_id' => null,
                'item_type' => 'attendance',
                'item_date' => $attDate,
                'amount' => (float) $att->allowance_amount,
                'duration_minutes' => (int) $att->working_minutes,
                'check_in_at' => $att->check_in_at?->format('H:i'),
                'check_out_at' => $att->check_out_at?->format('H:i'),
            ];
        }

        // 2. Fetch eligible overtimes
        $overtimes = Overtime::query()
            ->whereHas('attendance', function ($q) use ($staff) {
                $q->where('user_id', $staff->id);
            })
            ->whereDate('overtime_date', '>=', $startStr)
            ->whereDate('overtime_date', '<=', $endStr)
            ->where('allowance_eligible', true)
            ->where('allowance_amount', '>', 0)
            ->orderBy('overtime_date')
            ->get();

        $totalOvertimeMinutes = (int) $overtimes->sum('overtime_minutes');
        $totalOvertimeAmount = (float) $overtimes->sum('allowance_amount');

        foreach ($overtimes as $ot) {
            $otDate = is_string($ot->overtime_date) ? substr($ot->overtime_date, 0, 10) : $ot->overtime_date->toDateString();
            $items[] = [
                'attendance_id' => $ot->attendance_id,
                'overtime_id' => $ot->id,
                'item_type' => 'overtime',
                'item_date' => $otDate,
                'amount' => (float) $ot->allowance_amount,
                'duration_minutes' => (int) $ot->overtime_minutes,
                'check_in_at' => $ot->check_in_at?->format('H:i'),
                'check_out_at' => $ot->check_out_at?->format('H:i'),
            ];
        }

        $totalAllowance = $totalAttendanceAmount + $totalOvertimeAmount;

        return [
            'user_id' => $staff->id,
            'staff' => $staff,
            'total_attendance_days' => $totalAttendanceDays,
            'total_attendance_amount' => $totalAttendanceAmount,
            'total_overtime_minutes' => $totalOvertimeMinutes,
            'total_overtime_amount' => $totalOvertimeAmount,
            'total_allowance' => $totalAllowance,
            'items' => $items,
        ];
    }

    /**
     * Preview calculation for multiple staff.
     */
    public function previewForStaffList(array $userIds, Carbon $startDate, Carbon $endDate): array
    {
        $users = User::whereIn('id', $userIds)->with('office')->orderBy('name')->get();
        $results = [];

        foreach ($users as $user) {
            $results[] = $this->calculateForStaff($user, $startDate, $endDate);
        }

        return $results;
    }

    /**
     * Check for double claim overlaps for selected users in date range.
     *
     * @return array List of warning messages or conflicts
     */
    public function checkOverlappingStaff(array $userIds, Carbon $startDate, Carbon $endDate, ?int $ignorePeriodId = null): array
    {
        $startStr = $startDate->copy()->startOfDay()->toDateString();
        $endStr = $endDate->copy()->endOfDay()->toDateString();

        $query = AllowancePeriodItem::query()
            ->join('allowance_period_staff', 'allowance_period_items.allowance_period_staff_id', '=', 'allowance_period_staff.id')
            ->join('allowance_periods', 'allowance_period_staff.allowance_period_id', '=', 'allowance_periods.id')
            ->join('users', 'allowance_period_staff.user_id', '=', 'users.id')
            ->whereIn('allowance_period_staff.user_id', $userIds)
            ->whereDate('allowance_period_items.item_date', '>=', $startStr)
            ->whereDate('allowance_period_items.item_date', '<=', $endStr)
            ->whereIn('allowance_periods.status', ['active', 'completed']);

        if ($ignorePeriodId) {
            $query->where('allowance_periods.id', '!=', $ignorePeriodId);
        }

        $conflicts = $query->select(
            'users.name as staff_name',
            'allowance_periods.name as period_name',
            'allowance_period_items.item_type',
            'allowance_period_items.item_date',
            'allowance_period_items.amount'
        )->get();

        $warnings = [];
        foreach ($conflicts as $c) {
            $warnings[] = "Staff {$c->staff_name} pada tanggal {$c->item_date} ({$c->item_type}) sudah terdaftar di periode '{$c->period_name}'";
        }

        return $warnings;
    }

    /**
     * Generate or regenerate period staff and item records.
     */
    public function generatePeriodSummary(AllowancePeriod $period, array $userIds): void
    {
        DB::transaction(function () use ($period, $userIds) {
            $startDate = Carbon::parse($period->start_date);
            $endDate = Carbon::parse($period->end_date);

            $users = User::whereIn('id', $userIds)->get();

            foreach ($users as $user) {
                $calc = $this->calculateForStaff($user, $startDate, $endDate);

                /** @var AllowancePeriodStaff $periodStaff */
                $periodStaff = AllowancePeriodStaff::firstOrNew([
                    'allowance_period_id' => $period->id,
                    'user_id' => $user->id,
                ]);

                $periodStaff->total_attendance_days = $calc['total_attendance_days'];
                $periodStaff->total_attendance_amount = $calc['total_attendance_amount'];
                $periodStaff->total_overtime_minutes = $calc['total_overtime_minutes'];
                $periodStaff->total_overtime_amount = $calc['total_overtime_amount'];
                $periodStaff->total_allowance = $calc['total_allowance'];

                if (!$periodStaff->exists) {
                    $periodStaff->payment_status = 'unpaid';
                }

                $periodStaff->save();

                // Sync items
                $periodStaff->items()->delete();

                foreach ($calc['items'] as $item) {
                    AllowancePeriodItem::create([
                        'allowance_period_staff_id' => $periodStaff->id,
                        'attendance_id' => $item['attendance_id'] ?? null,
                        'overtime_id' => $item['overtime_id'] ?? null,
                        'item_type' => $item['item_type'],
                        'item_date' => $item['item_date'],
                        'amount' => $item['amount'],
                        'duration_minutes' => $item['duration_minutes'],
                    ]);
                }
            }

            // Remove staff that are no longer selected
            AllowancePeriodStaff::where('allowance_period_id', $period->id)
                ->whereNotIn('user_id', $userIds)
                ->delete();

            $this->recalculatePeriodTotals($period);
        });
    }

    /**
     * Mark a single staff as paid.
     */
    public function markStaffAsPaid(AllowancePeriodStaff $periodStaff, array $paymentData = []): void
    {
        DB::transaction(function () use ($periodStaff, $paymentData) {
            $periodStaff->update([
                'payment_status' => 'paid',
                'paid_at' => $paymentData['paid_at'] ?? now(),
                'paid_by' => $paymentData['paid_by'] ?? auth()->id(),
                'payment_method' => $paymentData['payment_method'] ?? 'Transfer',
                'payment_reference' => $paymentData['payment_reference'] ?? null,
                'notes' => $paymentData['notes'] ?? $periodStaff->notes,
            ]);

            $this->recalculatePeriodTotals($periodStaff->allowancePeriod);
        });
    }

    /**
     * Mark multiple staff as paid in bulk.
     */
    public function markBulkStaffAsPaid(Collection|array $staffRecords, array $paymentData = []): void
    {
        DB::transaction(function () use ($staffRecords, $paymentData) {
            $period = null;

            foreach ($staffRecords as $record) {
                $periodStaff = $record instanceof AllowancePeriodStaff ? $record : AllowancePeriodStaff::find($record);
                if (!$periodStaff) {
                    continue;
                }

                $periodStaff->update([
                    'payment_status' => 'paid',
                    'paid_at' => $paymentData['paid_at'] ?? now(),
                    'paid_by' => $paymentData['paid_by'] ?? auth()->id(),
                    'payment_method' => $paymentData['payment_method'] ?? 'Transfer',
                    'payment_reference' => $paymentData['payment_reference'] ?? null,
                    'notes' => $paymentData['notes'] ?? $periodStaff->notes,
                ]);

                if (!$period) {
                    $period = $periodStaff->allowancePeriod;
                }
            }

            if ($period) {
                $this->recalculatePeriodTotals($period);
            }
        });
    }

    /**
     * Revert staff payment status to unpaid.
     */
    public function markStaffAsUnpaid(AllowancePeriodStaff $periodStaff): void
    {
        DB::transaction(function () use ($periodStaff) {
            $periodStaff->update([
                'payment_status' => 'unpaid',
                'paid_at' => null,
                'paid_by' => null,
                'payment_method' => null,
                'payment_reference' => null,
            ]);

            $this->recalculatePeriodTotals($periodStaff->allowancePeriod);
        });
    }

    /**
     * Recalculate totals for an allowance period and update status.
     */
    public function recalculatePeriodTotals(AllowancePeriod $period): void
    {
        $periodId = $period->id;
        $staffRecords = AllowancePeriodStaff::where('allowance_period_id', $periodId)->get();

        $totalAmount = (float) $staffRecords->sum('total_allowance');
        $totalPaid = (float) $staffRecords->where('payment_status', 'paid')->sum('total_allowance');
        $totalUnpaid = (float) $staffRecords->where('payment_status', 'unpaid')->sum('total_allowance');

        $totalStaff = $staffRecords->count();
        $paidStaff = $staffRecords->where('payment_status', 'paid')->count();

        $status = $period->status;
        if ($totalStaff > 0 && $paidStaff === $totalStaff) {
            $status = 'completed';
        } elseif ($period->status !== 'draft') {
            $status = 'active';
        }

        AllowancePeriod::where('id', $periodId)->update([
            'total_amount' => $totalAmount,
            'total_paid_amount' => $totalPaid,
            'total_unpaid_amount' => $totalUnpaid,
            'status' => $status,
        ]);

        $period->refresh();
    }
}
