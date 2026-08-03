<?php

namespace App\Http\Controllers;

use App\Models\WeeklyReport;
use App\Models\DailyReport;
use Illuminate\Http\Request;

class WeeklyReportController extends Controller
{
    public function show(WeeklyReport $weeklyReport)
    {
        $startDate = $weeklyReport->start_date;
        $endDate = $weeklyReport->end_date;

        $dailyReports = DailyReport::with(['user', 'subModule.module', 'reportImages'])
            ->whereBetween('report_date', [$startDate, $endDate])
            ->orderBy('report_date')
            ->get();

        // Group by Module -> SubModule
        $groupedByModule = $dailyReports->groupBy(function ($report) {
            return $report->subModule->module->name;
        })->map(function ($reports) {
            return $reports->groupBy(function ($report) {
                return $report->subModule->name;
            });
        });

        // Group by User
        $groupedByUser = $dailyReports->groupBy(function ($report) {
            return $report->user->name;
        });

        return view('weekly-reports.show', compact('weeklyReport', 'groupedByModule', 'groupedByUser'));
    }
}
