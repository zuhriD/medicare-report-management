<?php

namespace App\Http\Controllers;

use App\Models\AllowancePeriod;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AllowancePeriodExportController extends Controller
{
    public function export(Request $request, AllowancePeriod $allowancePeriod)
    {
        $allowancePeriod->load([
            'office',
            'creator',
            'periodStaff.user.office',
            'periodStaff.paidBy',
            'periodStaff.items',
        ]);

        $format = strtolower((string) $request->query('format', 'pdf'));
        $safeName = \Illuminate\Support\Str::slug($allowancePeriod->name);
        $fileNamePrefix = "rekap-allowance-{$safeName}";

        if ($format === 'csv' || $format === 'excel') {
            return $this->exportCsv($allowancePeriod, "{$fileNamePrefix}.csv");
        }

        // Default: PDF
        $pdf = Pdf::loadView('allowance-period.pdf', [
            'period' => $allowancePeriod,
            'officeName' => $allowancePeriod->office?->name ?? 'Semua / Multi Kantor',
        ])->setPaper('a4', 'landscape');

        return $pdf->download("{$fileNamePrefix}.pdf");
    }

    protected function exportCsv(AllowancePeriod $period, string $fileName): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        return response()->stream(function () use ($period) {
            $handle = fopen('php://output', 'w');

            // Add UTF-8 BOM
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Title & Info Rows
            fputcsv($handle, ['REKAPITULASI PEMBAYARAN TUNJANGAN (HR ALLOWANCE)']);
            fputcsv($handle, ['Nama Periode', $period->name]);
            fputcsv($handle, ['Kantor', $period->office?->name ?? 'Semua / Multi Kantor']);
            fputcsv($handle, ['Rentang Tanggal', $period->start_date->format('d/m/Y') . ' - ' . $period->end_date->format('d/m/Y')]);
            fputcsv($handle, ['Status Periode', ucfirst($period->status)]);
            fputcsv($handle, []); // Empty line

            // Table Header
            fputcsv($handle, [
                'No',
                'Nama Staff',
                'Email',
                'Kantor',
                'Hari Hadir',
                'Uang Kehadiran (Rp)',
                'Durasi Lembur (Menit)',
                'Durasi Lembur (Jam)',
                'Uang Lembur (Rp)',
                'Total Tunjangan (Rp)',
                'Status Bayar',
                'Waktu Bayar',
                'Dibayar Oleh',
                'Metode',
                'No Referensi',
                'Catatan',
            ]);

            // Table Data
            foreach ($period->periodStaff as $index => $ps) {
                $hours = round($ps->total_overtime_minutes / 60, 2);
                fputcsv($handle, [
                    $index + 1,
                    $ps->user?->name ?? 'N/A',
                    $ps->user?->email ?? 'N/A',
                    $ps->user?->office?->name ?? '-',
                    $ps->total_attendance_days,
                    $ps->total_attendance_amount,
                    $ps->total_overtime_minutes,
                    $hours,
                    $ps->total_overtime_amount,
                    $ps->total_allowance,
                    strtoupper($ps->payment_status),
                    $ps->paid_at ? $ps->paid_at->format('d/m/Y H:i') : '-',
                    $ps->paidBy?->name ?? '-',
                    $ps->payment_method ?? '-',
                    $ps->payment_reference ?? '-',
                    $ps->notes ?? '-',
                ]);
            }

            // Summary Footer
            fputcsv($handle, []);
            fputcsv($handle, [
                'TOTAL',
                '',
                '',
                '',
                $period->periodStaff->sum('total_attendance_days'),
                $period->periodStaff->sum('total_attendance_amount'),
                $period->periodStaff->sum('total_overtime_minutes'),
                round($period->periodStaff->sum('total_overtime_minutes') / 60, 2),
                $period->periodStaff->sum('total_overtime_amount'),
                $period->total_amount,
                "Terbayar: Rp " . number_format((float) $period->total_paid_amount, 0, ',', '.') . " | Sisa: Rp " . number_format((float) $period->total_unpaid_amount, 0, ',', '.'),
                '',
                '',
                '',
                '',
                '',
            ]);

            fclose($handle);
        }, 200, $headers);
    }
}
