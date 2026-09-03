<?php

namespace App\Http\Controllers;

use App\Filament\Resources\DailyReportResource;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DailyReportPrintController extends Controller
{
    public function show(Request $request)
    {
        $rawDate = $request->query('date');

        try {
            $date = $rawDate ? Carbon::parse($rawDate) : now();
        } catch (\Throwable $e) {
            $date = now();
        }

        $formattedDateStr = $date->format('Y-m-d');

        $dailyReports = DailyReportResource::getEloquentQuery()
            ->with(['user.sections', 'subModule.module', 'reportImages'])
            ->whereDate('report_date', $formattedDateStr)
            ->orderBy('user_id')
            ->orderBy('created_at')
            ->get();

        $stats = [
            'total_reports' => $dailyReports->count(),
            'user_count' => $dailyReports->pluck('user_id')->unique()->count(),
            'module_count' => $dailyReports->pluck('subModule.module_id')->filter()->unique()->count(),
        ];

        if ($request->boolean('pdf') || $request->query('export') === 'pdf') {
            $pdf = Pdf::loadView('daily-reports.print', [
                'dailyReports' => $dailyReports,
                'date' => $date,
                'stats' => $stats,
                'isPdf' => true,
            ])->setPaper('a4');

            return $pdf->download("daily-report-{$formattedDateStr}.pdf");
        }

        return view('daily-reports.print', [
            'dailyReports' => $dailyReports,
            'date' => $date,
            'stats' => $stats,
            'isPdf' => false,
        ]);
    }
}
