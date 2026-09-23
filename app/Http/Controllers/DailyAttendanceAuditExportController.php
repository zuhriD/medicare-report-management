<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Services\DailyAttendanceAuditService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DailyAttendanceAuditExportController extends Controller
{
    protected DailyAttendanceAuditService $auditService;

    public function __construct(DailyAttendanceAuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    public function export(Request $request)
    {
        $dateStr = $request->query('date', now()->toDateString());
        $officeId = $request->query('office_id') ? (int) $request->query('office_id') : null;
        $userId = $request->query('user_id') ? (int) $request->query('user_id') : null;
        $format = strtolower((string) $request->query('format', 'pdf'));

        $audit = $this->auditService->getDailyAuditData($dateStr, $officeId, $userId);
        $officeName = $officeId ? Office::find($officeId)?->name : 'Semua Kantor';

        $fileNamePrefix = "audit-presensi-harian-{$dateStr}";

        if ($format === 'csv' || $format === 'excel') {
            return $this->exportCsv($audit, "{$fileNamePrefix}.csv");
        }

        // Default: Export PDF
        $pdf = Pdf::loadView('daily-attendance-audit.pdf', [
            'audit' => $audit,
            'officeName' => $officeName,
        ])->setPaper('a4', 'landscape');

        return $pdf->download("{$fileNamePrefix}.pdf");
    }

    protected function exportCsv(array $audit, string $fileName): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        return response()->stream(function () use ($audit) {
            $handle = fopen('php://output', 'w');
            
            // Add BOM for proper UTF-8 Excel compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Title & Info Rows
            fputcsv($handle, ['LAPORAN LOG PRESENSI HARIAN & AUDIT']);
            fputcsv($handle, ['Tanggal', $audit['date_formatted'] . ' (' . $audit['day_name'] . ')']);
            fputcsv($handle, []);

            // Table Header
            fputcsv($handle, [
                'No',
                'Nama Staff',
                'Email',
                'Kantor',
                'Status',
                'Jam Masuk',
                'Jam Pulang',
                'Jarak GPS Masuk (meter)',
                'Geofence Masuk Valid',
                'Jarak GPS Pulang (meter)',
                'Geofence Pulang Valid',
                'Total Jeda Istirahat (menit)',
                'Durasi Kerja (jam)',
                'Durasi Lembur (jam)',
                'Uang Kehadiran (Rp)',
                'Keterangan',
            ]);

            // Table Data
            foreach ($audit['staff_logs'] as $index => $staff) {
                fputcsv($handle, [
                    $index + 1,
                    $staff['name'],
                    $staff['email'],
                    $staff['office_name'],
                    $staff['status_label'],
                    $staff['check_in_at'] ?? '-',
                    $staff['check_out_at'] ?? '-',
                    $staff['check_in_distance'] ?? '-',
                    $staff['check_in_within_radius'] !== null ? ($staff['check_in_within_radius'] ? 'Ya' : 'Luar Radius') : '-',
                    $staff['check_out_distance'] ?? '-',
                    $staff['check_out_within_radius'] !== null ? ($staff['check_out_within_radius'] ? 'Ya' : 'Luar Radius') : '-',
                    $staff['total_break_minutes'],
                    $staff['working_hours'],
                    $staff['overtime_hours'],
                    $staff['allowance_amount'],
                    $staff['leave_request'] ? 'Izin: ' . $staff['leave_request']['reason'] : ($staff['notes'] ?: '-'),
                ]);
            }

            // Summary Footer
            fputcsv($handle, []);
            fputcsv($handle, [
                'TOTAL STAFF',
                $audit['summary']['total_staff'],
                'TOTAL MASUK',
                $audit['summary']['total_checked_in'],
                'SUDAH PULANG',
                $audit['summary']['total_checked_out'],
                'IZIN KELUAR',
                $audit['summary']['total_currently_paused'],
                'IZIN/CUTI',
                $audit['summary']['total_leaves'],
                'ALPHA',
                $audit['summary']['total_alpha'],
                'TOTAL JAM KERJA',
                $audit['summary']['total_working_hours'],
            ]);

            fclose($handle);
        }, 200, $headers);
    }
}
