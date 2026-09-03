<?php

namespace App\Services;

use App\Models\DailyReport;
use App\Models\WeeklyReport;
use App\Models\WeeklyReportIndividualSummary;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WeeklyReportAggregationService
{
    public function refreshDraft(WeeklyReport $weeklyReport): WeeklyReport
    {
        return DB::transaction(function () use ($weeklyReport) {
            $weeklyReport->loadMissing('project');

            $this->syncIndividualSummaries($weeklyReport);

            return $weeklyReport->refresh()->load(['project', 'individualSummaries.user']);
        });
    }

    public function syncIndividualSummaries(WeeklyReport $weeklyReport): void
    {
        $weeklyReport->individualSummaries()->delete();

        $this->dailyReportsFor($weeklyReport)
            ->groupBy('user_id')
            ->each(function (Collection $reports, int $userId) use ($weeklyReport): void {
                WeeklyReportIndividualSummary::create([
                    'weekly_report_id' => $weeklyReport->id,
                    'user_id' => $userId,
                    'summary_text' => $this->summarizeDailyReports($reports),
                ]);
            });
    }

    public function buildExportPayload(WeeklyReport $weeklyReport): array
    {
        $weeklyReport->load([
            'project.leads',
            'generatedBy',
            'individualSummaries.user',
        ]);

        $dailyReports = $this->dailyReportsFor($weeklyReport);
        $modules = $weeklyReport->project
            ->modules()
            ->with(['platforms.featureChecklists'])
            ->orderBy('code')
            ->get();

        return [
            'weeklyReport' => $weeklyReport,
            'dailyReports' => $dailyReports,
            'individualSummaries' => $weeklyReport->individualSummaries->map(fn ($summary) => [
                'user' => $summary->user,
                'summary' => $summary->summary_text,
            ]),
            'modules' => $modules,
            'stats' => [
                'daily_report_count' => $dailyReports->count(),
                'developer_count' => $dailyReports->pluck('user_id')->unique()->count(),
                'module_count' => $modules->count(),
            ],
        ];
    }

    private function dailyReportsFor(WeeklyReport $weeklyReport): Collection
    {
        return DailyReport::query()
            ->where('project_id', $weeklyReport->project_id)
            ->whereBetween('report_date', [
                $weeklyReport->period_start->toDateString(),
                $weeklyReport->period_end->toDateString(),
            ])
            ->with(['user', 'module', 'linkedFeature'])
            ->orderBy('report_date')
            ->orderBy('id')
            ->get();
    }

    private function summarizeDailyReports(Collection $reports): string
    {
        return $reports
            ->map(function (DailyReport $report): string {
                $moduleName = $report->module?->name ?? 'General';

                $rawDescription = $report->getRawOriginal('description'); $decoded = json_decode($rawDescription, true); if (is_array($decoded)) { $tasks = array_map( fn ($task) => trim(strip_tags((string) $task)), $decoded ); } else { $tasks = [trim(strip_tags((string) $rawDescription))]; } $tasks = array_filter($tasks); return sprintf( 'TEST | %s | %s', $moduleName, implode(', ', $tasks) );
            })
            ->implode("\n");
    }

    private function groupReportsByUser(Collection $reports): Collection
    {
        return $reports
            ->groupBy('user_id')
            ->map(function (Collection $userReports): array {
                $firstReport = $userReports->first();

                return [
                    'user' => $firstReport?->user,
                    'summary' => $this->summarizeDailyReports($userReports),
                    'reports' => $userReports,
                ];
            });
    }
}
