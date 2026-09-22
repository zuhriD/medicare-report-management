<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Office;
use App\Models\User;
use Carbon\Carbon;

class DailyAttendanceAuditService
{
    protected GeoLocationService $geoService;
    protected AttendanceFineService $fineService;
    protected SelfieStorageService $selfieService;

    public function __construct(
        GeoLocationService $geoService,
        AttendanceFineService $fineService,
        SelfieStorageService $selfieService
    ) {
        $this->geoService = $geoService;
        $this->fineService = $fineService;
        $this->selfieService = $selfieService;
    }

    /**
     * Get daily attendance audit data and logs for a specific date and office.
     *
     * @return array{
     *   date: string,
     *   date_formatted: string,
     *   day_name: string,
     *   is_sunday: bool,
     *   summary: array,
     *   staff_logs: array,
     *   offices: array
     * }
     */
    public function getDailyAuditData(string $dateStr, ?int $officeId = null, ?int $userId = null): array
    {
        $date = Carbon::parse($dateStr);
        $dateFormatted = $date->translatedFormat('d F Y');
        $dayName = $date->translatedFormat('l');
        $isSunday = $date->isSunday();

        $userQuery = User::query()->with(['office.attendanceSettings']);

        if ($officeId) {
            $userQuery->where('office_id', $officeId);
        }

        if ($userId) {
            $userQuery->where('id', $userId);
        }

        $users = $userQuery->orderBy('name')->get();

        // Fetch all attendances on this date with breaks & overtime
        $attendances = Attendance::with(['breaks', 'overtime', 'attendanceSetting', 'office'])
            ->whereDate('attendance_date', $dateStr)
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->get()
            ->keyBy('user_id');

        // Fetch approved leaves covering this date
        $approvedLeaves = LeaveRequest::where('status', 'approved')
            ->where('start_date', '<=', $dateStr)
            ->where('end_date', '>=', $dateStr)
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->get()
            ->keyBy('user_id');

        $staffLogs = [];
        $totalCheckedIn = 0;
        $totalCheckedOut = 0;
        $totalCurrentlyPaused = 0;
        $totalLeaves = 0;
        $totalAlpha = 0;
        $totalWorkingMinutes = 0;
        $totalOvertimeMinutes = 0;
        $totalBreakMinutes = 0;

        foreach ($users as $user) {
            $office = $user->office;
            $att = $attendances->get($user->id);
            $leave = $approvedLeaves->get($user->id);

            $status = 'alpha';
            $statusLabel = 'Alpha (Tanpa Keterangan)';
            $badgeColor = 'rose';

            $checkInSelfieUrl = null;
            $checkOutSelfieUrl = null;
            $checkInDistance = null;
            $checkOutDistance = null;
            $checkInWithinRadius = null;
            $checkOutWithinRadius = null;
            $breaksList = [];
            $totalUserBreakMinutes = 0;
            $isCurrentlyPaused = false;

            if ($isSunday && !$att) {
                $status = 'holiday';
                $statusLabel = 'Hari Libur (Minggu)';
                $badgeColor = 'gray';
            } elseif ($att && $att->isCheckedIn()) {
                $totalCheckedIn++;
                $checkInSelfieUrl = $this->selfieService->getSelfieUrl($att->check_in_selfie);

                if ($office && $att->check_in_latitude && $att->check_in_longitude) {
                    $checkInDistance = round($this->geoService->calculateDistance(
                        (float) $att->check_in_latitude,
                        (float) $att->check_in_longitude,
                        (float) $office->latitude,
                        (float) $office->longitude
                    ), 1);
                    $checkInWithinRadius = $checkInDistance <= ($office->attendance_radius_meter ?? 100);
                }

                // Parse breaks
                if ($att->breaks->isNotEmpty()) {
                    foreach ($att->breaks as $break) {
                        $breakDuration = (int) ($break->duration_minutes ?? 0);
                        $totalUserBreakMinutes += $breakDuration;

                        $breaksList[] = [
                            'id' => $break->id,
                            'paused_at' => $break->paused_at?->format('H:i:s'),
                            'resumed_at' => $break->resumed_at?->format('H:i:s'),
                            'duration_minutes' => $breakDuration,
                            'reason' => $break->reason,
                            'notes' => $break->notes,
                            'is_active' => is_null($break->resumed_at),
                        ];
                    }
                }

                $totalBreakMinutes += $totalUserBreakMinutes;
                $isCurrentlyPaused = $att->isPaused();

                if ($att->isCheckedOut()) {
                    $totalCheckedOut++;
                    $status = 'completed';
                    $statusLabel = 'Hadir Selesai';
                    $badgeColor = 'emerald';

                    $checkOutSelfieUrl = $this->selfieService->getSelfieUrl($att->check_out_selfie);

                    if ($office && $att->check_out_latitude && $att->check_out_longitude) {
                        $checkOutDistance = round($this->geoService->calculateDistance(
                            (float) $att->check_out_latitude,
                            (float) $att->check_out_longitude,
                            (float) $office->latitude,
                            (float) $office->longitude
                        ), 1);
                        $checkOutWithinRadius = $checkOutDistance <= ($office->attendance_radius_meter ?? 100);
                    }
                } elseif ($isCurrentlyPaused) {
                    $totalCurrentlyPaused++;
                    $status = 'paused';
                    $statusLabel = 'Sedang Izin Keluar (Pause)';
                    $badgeColor = 'amber';
                } else {
                    $status = 'working';
                    $statusLabel = 'Sedang Bekerja (Aktif)';
                    $badgeColor = 'sky';
                }

                $totalWorkingMinutes += (int) $att->working_minutes;
                if ($att->overtime) {
                    $totalOvertimeMinutes += (int) $att->overtime->overtime_minutes;
                }
            } elseif ($leave) {
                $totalLeaves++;
                $status = 'leave';
                $type = match ($leave->leave_type) {
                    'sick' => 'Sakit',
                    'permission' => 'Izin Keperluan',
                    'annual_leave' => 'Cuti Tahunan',
                    default => 'Izin',
                };
                $statusLabel = 'Izin Disetujui (' . $type . ')';
                $badgeColor = 'amber';
            } else {
                $totalAlpha++;
            }

            $staffLogs[] = [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'office_id' => $office?->id,
                'office_name' => $office?->name ?? 'Belum Ditugaskan',
                'status' => $status,
                'status_label' => $statusLabel,
                'badge_color' => $badgeColor,
                'has_attendance' => !is_null($att),
                'check_in_at' => $att?->check_in_at?->format('H:i:s'),
                'check_out_at' => $att?->check_out_at?->format('H:i:s'),
                'check_in_selfie' => $checkInSelfieUrl,
                'check_out_selfie' => $checkOutSelfieUrl,
                'check_in_distance' => $checkInDistance,
                'check_in_within_radius' => $checkInWithinRadius,
                'check_out_distance' => $checkOutDistance,
                'check_out_within_radius' => $checkOutWithinRadius,
                'working_minutes' => $att ? (int) $att->working_minutes : 0,
                'working_hours' => $att ? round((int) $att->working_minutes / 60, 1) : 0,
                'total_break_minutes' => $totalUserBreakMinutes,
                'breaks' => $breaksList,
                'has_overtime' => $att && !is_null($att->overtime),
                'overtime_minutes' => $att?->overtime ? (int) $att->overtime->overtime_minutes : 0,
                'overtime_hours' => $att?->overtime ? round((int) $att->overtime->overtime_minutes / 60, 1) : 0,
                'allowance_eligible' => $att?->allowance_eligible ?? false,
                'allowance_amount' => (float) ($att?->allowance_amount ?? 0),
                'notes' => $att?->notes,
                'leave_request' => $leave ? [
                    'id' => $leave->id,
                    'leave_type' => $leave->leave_type,
                    'reason' => $leave->reason,
                    'adjusted_fine_amount' => (float) $leave->adjusted_fine_amount,
                ] : null,
            ];
        }

        $totalStaff = count($staffLogs);

        return [
            'date' => $dateStr,
            'date_formatted' => $dateFormatted,
            'day_name' => $dayName,
            'is_sunday' => $isSunday,
            'summary' => [
                'total_staff' => $totalStaff,
                'total_checked_in' => $totalCheckedIn,
                'total_checked_out' => $totalCheckedOut,
                'total_currently_paused' => $totalCurrentlyPaused,
                'total_leaves' => $totalLeaves,
                'total_alpha' => $totalAlpha,
                'total_working_hours' => round($totalWorkingMinutes / 60, 1),
                'total_overtime_hours' => round($totalOvertimeMinutes / 60, 1),
                'total_break_minutes' => $totalBreakMinutes,
            ],
            'staff_logs' => $staffLogs,
            'offices' => Office::orderBy('name')->get(['id', 'name'])->toArray(),
        ];
    }
}
