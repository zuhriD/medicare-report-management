<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\Office;
use App\Models\Overtime;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceBackfillService
{
    public function __construct(
        protected AttendanceCalculationService $calculationService,
        protected AttendancePolicyService $policyService,
        protected HolidayService $holidayService
    ) {}

    /**
     * Bulk backfill attendance and optional overtime records for specified users and date range.
     *
     * @param array{
     *     user_ids: array<int>,
     *     start_date: string,
     *     end_date: string,
     *     office_id?: ?int,
     *     check_in_time?: string,
     *     check_out_time?: string,
     *     skip_sundays?: bool,
     *     skip_holidays?: bool,
     *     overwrite_existing?: bool,
     *     include_overtime?: bool,
     *     overtime_scope?: string,
     *     overtime_dates?: array<string>,
     *     overtime_all_days?: bool,
     *     overtime_check_in_time?: string,
     *     overtime_check_out_time?: string,
     *     notes?: ?string
     * } $data
     * @return array{
     *     success: bool,
     *     attendances_created: int,
     *     overtimes_created: int,
     *     dates_skipped: int,
     *     users_count: int,
     *     message?: string
     * }
     */
    public function backfill(array $data): array
    {
        $userIds = (array) ($data['user_ids'] ?? []);
        if (empty($userIds)) {
            return [
                'success' => false,
                'attendances_created' => 0,
                'overtimes_created' => 0,
                'dates_skipped' => 0,
                'users_count' => 0,
                'message' => 'Tidak ada staf yang dipilih.',
            ];
        }

        $startDateStr = $data['start_date'] ?? '2026-09-16';
        $endDateStr = $data['end_date'] ?? '2026-09-24';
        $checkInTime = $data['check_in_time'] ?? '08:00:00';
        $checkOutTime = $data['check_out_time'] ?? '17:00:00';
        $skipSundays = (bool) ($data['skip_sundays'] ?? true);
        $skipHolidays = (bool) ($data['skip_holidays'] ?? true);
        $overwriteExisting = (bool) ($data['overwrite_existing'] ?? false);
        $includeOvertime = (bool) ($data['include_overtime'] ?? false);
        $overtimeScope = $data['overtime_scope'] ?? 'dates';
        $overtimeAllDays = (bool) ($data['overtime_all_days'] ?? ($overtimeScope === 'all'));
        $overtimeDates = array_filter(array_map('trim', (array) ($data['overtime_dates'] ?? [])));
        $overtimeCheckInTime = $data['overtime_check_in_time'] ?? '18:00:00';
        $overtimeCheckOutTime = $data['overtime_check_out_time'] ?? '21:00:00';
        $notes = $data['notes'] ?? 'Backfill presensi manual dari riwayat chat WhatsApp';
        $officeOverrideId = $data['office_id'] ?? null;

        $startDate = Carbon::parse($startDateStr)->startOfDay();
        $endDate = Carbon::parse($endDateStr)->startOfDay();

        if ($startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $users = User::with(['office', 'offices'])->whereIn('id', $userIds)->get();
        $fallbackOffice = $officeOverrideId
            ? Office::find($officeOverrideId)
            : (Office::active()->first() ?? Office::first());

        $attendancesCreated = 0;
        $overtimesCreated = 0;
        $datesSkipped = 0;

        DB::beginTransaction();
        try {
            foreach ($users as $user) {
                $office = null;
                if ($officeOverrideId) {
                    $office = Office::find($officeOverrideId);
                }
                if (!$office) {
                    $office = $user->office ?? $user->offices->first() ?? $fallbackOffice;
                }

                if (!$office) {
                    Log::warning("Backfill skipped for user {$user->id} ({$user->name}): No office found.");
                    continue;
                }

                $tz = $office->timezone ?? config('app.timezone', 'Asia/Kuala_Lumpur');
                $period = CarbonPeriod::create($startDate, $endDate);

                foreach ($period as $date) {
                    $dateStr = $date->toDateString();

                    // 1. Skip Sunday if requested
                    if ($skipSundays && $date->isSunday()) {
                        $datesSkipped++;
                        continue;
                    }

                    // 2. Skip Holiday if requested
                    if ($skipHolidays && $this->holidayService->isHoliday($dateStr, $office->id)) {
                        $datesSkipped++;
                        continue;
                    }

                    // 3. Existing attendance check
                    $existingAttendance = Attendance::where('user_id', $user->id)
                        ->whereDate('attendance_date', $dateStr)
                        ->first();

                    if ($existingAttendance && !$overwriteExisting) {
                        $datesSkipped++;
                        continue;
                    }

                    // 4. Resolve office policy
                    $policy = $this->policyService->getActivePolicy($office, $date)
                        ?? AttendanceSetting::where('office_id', $office->id)->first()
                        ?? AttendanceSetting::active()->first()
                        ?? AttendanceSetting::first();

                    // 5. Timestamps
                    $checkInAt = Carbon::parse("{$dateStr} {$checkInTime}", $tz);
                    $checkOutAt = Carbon::parse("{$dateStr} {$checkOutTime}", $tz);

                    // 6. Punctuality & Late minutes
                    if ($policy) {
                        $punctuality = $this->calculationService->evaluatePunctuality($checkInAt, $policy);
                        $status = $punctuality['status'];
                        $lateMinutes = $punctuality['late_minutes'];
                    } else {
                        $status = AttendanceStatus::ON_TIME;
                        $lateMinutes = 0;
                    }

                    // 7. Working duration & allowance
                    $workingMinutes = $this->calculationService->calculateWorkingMinutes($checkInAt, $checkOutAt, 0, $policy);

                    if ($policy) {
                        $evalAllowance = $this->calculationService->evaluateRegularAllowance($workingMinutes, $policy);
                        $allowanceEligible = $evalAllowance['allowance_eligible'];
                        $allowanceAmount = $evalAllowance['allowance_amount'];
                    } else {
                        $allowanceEligible = true;
                        $allowanceAmount = 50000.00;
                    }

                    // 8. Save Attendance record
                    $attendance = Attendance::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'attendance_date' => $dateStr,
                        ],
                        [
                            'office_id' => $office->id,
                            'attendance_setting_id' => $policy?->id,
                            'status' => $status,
                            'late_minutes' => $lateMinutes,
                            'check_in_at' => $checkInAt,
                            'check_out_at' => $checkOutAt,
                            'working_minutes' => $workingMinutes,
                            'allowance_eligible' => $allowanceEligible,
                            'allowance_amount' => $allowanceAmount,
                            'notes' => $notes,
                        ]
                    );

                    $attendancesCreated++;

                    // 9. Overtime handling
                    $hasOtForDate = $includeOvertime && ($overtimeAllDays || in_array($dateStr, $overtimeDates));

                    if ($hasOtForDate) {
                        $otCheckInAt = Carbon::parse("{$dateStr} {$overtimeCheckInTime}", $tz);
                        $otCheckOutAt = Carbon::parse("{$dateStr} {$overtimeCheckOutTime}", $tz);
                        $otMinutes = $this->calculationService->calculateOvertimeMinutes($otCheckInAt, $otCheckOutAt, $policy);

                        if ($policy) {
                            $otEval = $this->calculationService->evaluateOvertimeAllowance($otMinutes, $policy);
                            $otEligible = $otEval['allowance_eligible'];
                            $otAmount = $otEval['allowance_amount'];
                        } else {
                            $otEligible = true;
                            $otAmount = 25000.00;
                        }

                        Overtime::updateOrCreate(
                            ['attendance_id' => $attendance->id],
                            [
                                'office_id' => $office->id,
                                'overtime_date' => $dateStr,
                                'check_in_at' => $otCheckInAt,
                                'check_out_at' => $otCheckOutAt,
                                'overtime_minutes' => $otMinutes,
                                'allowance_eligible' => $otEligible,
                                'allowance_amount' => $otAmount,
                                'notes' => $notes . ' (Lembur)',
                            ]
                        );

                        $overtimesCreated++;
                    }
                }
            }

            DB::commit();

            return [
                'success' => true,
                'attendances_created' => $attendancesCreated,
                'overtimes_created' => $overtimesCreated,
                'dates_skipped' => $datesSkipped,
                'users_count' => $users->count(),
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Attendance backfill error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
