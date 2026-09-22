<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Services\AttendanceRecapService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceRecapExportController extends Controller
{
    protected AttendanceRecapService $recapService;

    public function __construct(AttendanceRecapService $recapService)
    {
        $this->recapService = $recapService;
    }

    public function export(Request $request)
    {
        $startDateStr = $request->query('start_date');
        $endDateStr = $request->query('end_date');
        $officeId = $request->query('office_id') ? (int) $request->query('office_id') : null;
        $userId = $request->query('user_id') ? (int) $request->query('user_id') : null;
        $format = strtolower((string) $request->query('format', 'pdf'));

        if ($startDateStr && $endDateStr) {
            $startDate = \Carbon\Carbon::parse($startDateStr);
            $endDate = \Carbon\Carbon::parse($endDateStr);
            $recap = $this->recapService->getRecapByDateRange($startDate, $endDate, $officeId, $userId);
            $fileNamePrefix = "rekap-presensi-finansial-{$startDate->format('Ymd')}-sd-{$endDate->format('Ymd')}";
        } else {
            $month = (int) $request->query('month', now()->month);
            $year = (int) $request->query('year', now()->year);
            $recap = $this->recapService->getMonthlyRecap($month, $year, $officeId, $userId);
            $fileNamePrefix = "rekap-presensi-finansial-{$year}-" . str_pad($month, 2, '0', STR_PAD_LEFT);
        }

        $officeName = $officeId ? Office::find($officeId)?->name : 'Semua Kantor';

        if ($format === 'csv' || $format === 'excel') {
            return $this->exportCsv($recap, "{$fileNamePrefix}.csv");
        }

        // Default: Export PDF
        $pdf = Pdf::loadView('attendance-recap.pdf', [
            'recap' => $recap,
            'officeName' => $officeName,
        ])->setPaper('a4', 'landscape');

        return $pdf->download("{$fileNamePrefix}.pdf");
    }

    protected function exportCsv(array $recap, string $fileName): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        return response()->stream(function () use ($recap) {
            $handle = fopen('php://output', 'w');
            
            // Add BOM for proper UTF-8 Excel compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Title & Info Rows
            fputcsv($handle, ['LAPORAN REKAPITULASI PRESENSI & FINANSIAL']);
            fputcsv($handle, ['Periode', $recap['period']['month_name']]);
            fputcsv($handle, ['Hari Kerja Efektif', $recap['period']['working_days'] . ' Hari']);
            fputcsv($handle, []); // Empty line

            // Table Header
            fputcsv($handle, [
                'No',
                'Nama Staff',
                'Email',
                'Kantor',
                'Hari Efektif',
                'Hari Hadir',
                'Hari Izin',
                'Hari Alpha',
                'Tingkat Kehadiran (%)',
                'Total Jam Kerja',
                'Total Jam Lembur',
                'Uang Kehadiran (Rp)',
                'Uang Lembur (Rp)',
                'Denda Alpha (Rp)',
                'Denda Izin (Rp)',
                'Total Denda (Rp)',
                'Net Allowance (Rp)',
            ]);

            // Table Data
            foreach ($recap['staff_data'] as $index => $staff) {
                fputcsv($handle, [
                    $index + 1,
                    $staff['name'],
                    $staff['email'],
                    $staff['office_name'],
                    $staff['working_days'],
                    $staff['present_days'],
                    $staff['leave_days'],
                    $staff['alpha_days'],
                    $staff['attendance_rate'] . '%',
                    $staff['working_hours'],
                    $staff['overtime_hours'],
                    $staff['regular_allowance'],
                    $staff['overtime_allowance'],
                    $staff['alpha_fine'],
                    $staff['leave_fine'],
                    $staff['total_fine'],
                    $staff['net_allowance'],
                ]);
            }

            // Summary Footer
            fputcsv($handle, []);
            fputcsv($handle, [
                'TOTAL',
                '',
                '',
                '',
                $recap['summary']['working_days'],
                $recap['summary']['total_present'],
                $recap['summary']['total_leaves'],
                $recap['summary']['total_alpha'],
                $recap['summary']['average_attendance_rate'] . '%',
                $recap['summary']['total_working_hours'],
                $recap['summary']['total_overtime_hours'],
                $recap['summary']['total_regular_allowance'],
                $recap['summary']['total_overtime_allowance'],
                '',
                '',
                $recap['summary']['total_fine'],
                $recap['summary']['total_net_allowance'],
            ]);

            fclose($handle);
        }, 200, $headers);
    }
}
