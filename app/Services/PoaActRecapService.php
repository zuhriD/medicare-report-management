<?php

namespace App\Services;

use App\Models\DailyReport;
use App\Models\Module;
use App\Models\PlanOfAction;
use App\Models\SubModule;
use App\Models\User;
use Illuminate\Support\Carbon;

class PoaActRecapService
{
    public function generateRecap(string $rawDate): array
    {
        try {
            if (is_string($rawDate) && preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $rawDate)) {
                $dateObj = Carbon::createFromFormat('d/m/Y', $rawDate);
            } else {
                $dateObj = Carbon::parse($rawDate);
            }
        } catch (\Throwable $e) {
            $dateObj = now();
        }

        $dbDate = $dateObj->format('Y-m-d');
        $dateStr = $dateObj->format('d/m/Y') . ' (' . $dateObj->format('l') . ')';

        // Retrieve POAs for selected date
        $poas = PlanOfAction::with(['user', 'module', 'subModule'])
            ->whereNotNull('user_id')
            ->whereDate('start_date', $dbDate)
            ->get();

        // Retrieve DailyReports for selected date
        $acts = DailyReport::with(['user', 'subModule.module'])
            ->whereNotNull('user_id')
            ->whereDate('report_date', $dbDate)
            ->get();

        // Collect all unique user IDs
        $userIds = $poas->pluck('user_id')
            ->concat($acts->pluck('user_id'))
            ->unique()
            ->filter();

        $users = User::whereIn('id', $userIds)->orderBy('name')->get();

        $text = "PLAN OF ACTION (POA) & ACHIEVEMENT (ACT) REPORT\n";
        $text .= "Date: {$dateStr}\n\n";
        $text .= "MEDIKCARE\n\n";

        if ($users->isEmpty()) {
            $text .= "No Plan of Action or ACT Report records found for {$dateStr}.";
            return [
                'dateStr' => $dateStr,
                'dbDate' => $dbDate,
                'recapText' => $text,
                'isEmpty' => true,
                'usersCount' => 0,
            ];
        }

        $counter = 1;
        foreach ($users as $user) {
            $userPoas = $poas->where('user_id', $user->id);
            $userActs = $acts->where('user_id', $user->id);

            $text .= "{$counter}. {$user->name} POA\n";

            // Process POA records
            if ($userPoas->isNotEmpty()) {
                $groupedPoas = $userPoas->groupBy(function ($poa) {
                    return $this->formatModuleLabel($poa->module, $poa->subModule);
                });

                foreach ($groupedPoas as $groupLabel => $items) {
                    $text .= "   {$groupLabel}\n";
                    foreach ($items as $poa) {
                        $tasks = is_array($poa->description)
                            ? $poa->description
                            : array_filter(array_map('trim', explode('-', strip_tags($poa->description ?? ''))));

                        foreach ($tasks as $task) {
                            $cleanTask = trim(strip_tags($task));
                            if ($cleanTask) {
                                $text .= "   - {$cleanTask}\n";
                            }
                        }
                    }
                    $text .= "\n";
                }
            } else {
                $text .= "   (No POA submitted for this date)\n\n";
            }

            // Process ACT Report records
            $text .= "   ACT Report\n";
            if ($userActs->isNotEmpty()) {
                $groupedActs = $userActs->groupBy(function ($act) {
                    return $this->formatModuleLabel($act->subModule?->module, $act->subModule);
                });

                foreach ($groupedActs as $groupLabel => $items) {
                    $text .= "   {$groupLabel}\n";
                    foreach ($items as $act) {
                        $tasks = is_array($act->description)
                            ? $act->description
                            : array_filter(array_map('trim', explode('-', strip_tags($act->description ?? ''))));

                        foreach ($tasks as $task) {
                            $cleanTask = trim(strip_tags($task));
                            if ($cleanTask) {
                                $text .= "   - {$cleanTask}\n";
                            }
                        }
                    }
                    $text .= "\n";
                }
            } else {
                $text .= "   (No ACT Report submitted for this date)\n\n";
            }

            $counter++;
        }

        return [
            'dateStr' => $dateStr,
            'dbDate' => $dbDate,
            'recapText' => trim($text),
            'isEmpty' => false,
            'usersCount' => $users->count(),
        ];
    }

    private function formatModuleLabel(?Module $module, ?SubModule $subModule): string
    {
        $moduleName = $module?->name;
        $subName = $subModule?->name;

        if ($moduleName && $subName) {
            if (strtolower($moduleName) === 'general' || $moduleName === $subName) {
                return $subName;
            }
            return "{$moduleName} | {$subName}";
        }

        if ($subName) {
            return $subName;
        }

        if ($moduleName) {
            return "{$moduleName} | No Sub";
        }

        return "General | No Sub";
    }
}
