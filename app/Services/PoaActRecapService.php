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
    public function generateRecap(mixed $rawDate): array
    {
        try {
            if ($rawDate instanceof \DateTimeInterface) {
                $dateObj = Carbon::instance($rawDate);
            } elseif (is_string($rawDate) && preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $rawDate)) {
                $dateObj = Carbon::createFromFormat('d/m/Y', $rawDate);
            } elseif (is_string($rawDate) && filled($rawDate)) {
                $dateObj = Carbon::parse($rawDate);
            } else {
                $dateObj = now();
            }
        } catch (\Throwable $e) {
            $dateObj = now();
        }

        $dbDate = $dateObj->format('Y-m-d');
        $dateStr = $dateObj->format('d/m/Y') . ' (' . $dateObj->format('l') . ')';

        // Retrieve POAs for selected date
        $poas = PlanOfAction::with(['user', 'module', 'subModule.module'])
            ->whereNotNull('user_id')
            ->whereDate('start_date', $dbDate)
            ->get();

        // Retrieve DailyReports for selected date
        $acts = DailyReport::with(['user', 'subModule.module'])
            ->whereNotNull('user_id')
            ->whereDate('report_date', $dbDate)
            ->get();

        // Collect all unique user IDs that have either POA or ACT
        $userIds = $poas->pluck('user_id')
            ->concat($acts->pluck('user_id'))
            ->unique()
            ->filter()
            ->values();

        $users = User::whereIn('id', $userIds)->orderBy('name')->get();

        $text = "PLAN OF ACTION (POA) & ACHIEVEMENT (ACT) REPORT\n";
        $text .= "Date: {$dateStr}\n\n";

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
        $userBlocks = [];

        foreach ($users as $user) {
            $userPoas = $poas->where('user_id', $user->id);
            $userActs = $acts->where('user_id', $user->id);

            $block = "{$counter}. {$user->name} POA\n\n";

            // Process POA records
            if ($userPoas->isNotEmpty()) {
                $groupedPoas = $userPoas->groupBy(function ($poa) {
                    $module = $poa->module ?? $poa->subModule?->module;
                    return $this->formatModuleLabel($module, $poa->subModule);
                });

                foreach ($groupedPoas as $groupLabel => $items) {
                    $block .= "   {$groupLabel}\n";
                    foreach ($items as $poa) {
                        $rawContent = $poa->getRawOriginal('description') ?? $poa->description;
                        $tasks = \App\Filament\Resources\DailyReportResource::formatPoaDescription($rawContent, $poa->title ?? null);
                        foreach ($tasks as $task) {
                            $block .= "   - {$task}\n";
                        }
                    }
                    $block .= "\n";
                }
            } else {
                $block .= "   (No POA submitted for this date)\n\n";
            }

            // Process ACT Report records
            $block .= "   ACT Report\n\n";
            if ($userActs->isNotEmpty()) {
                $groupedActs = $userActs->groupBy(function ($act) {
                    return $this->formatModuleLabel($act->subModule?->module, $act->subModule);
                });

                foreach ($groupedActs as $groupLabel => $items) {
                    $block .= "   {$groupLabel}\n";
                    foreach ($items as $act) {
                        $rawContent = $act->getRawOriginal('description') ?? $act->description;
                        $tasks = \App\Filament\Resources\DailyReportResource::formatPoaDescription($rawContent);
                        foreach ($tasks as $task) {
                            $block .= "   - {$task}\n";
                        }
                    }
                    $block .= "\n";
                }
            } else {
                $block .= "   (No ACT Report submitted for this date)\n\n";
            }

            $userBlocks[] = rtrim($block);
            $counter++;
        }

        $text .= implode("\n\n", $userBlocks);

        return [
            'dateStr' => $dateStr,
            'dbDate' => $dbDate,
            'recapText' => trim($text),
            'isEmpty' => false,
            'usersCount' => $users->count(),
        ];
    }

    private function extractTasks(mixed $description, ?string $fallback = null): array
    {
        $tasks = [];
        if (is_array($description)) {
            $tasks = $description;
        } elseif (is_string($description)) {
            $decoded = json_decode($description, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $tasks = $decoded;
            } else {
                $clean = trim(strip_tags($description));
                $tasks = array_values(array_filter(array_map('trim', preg_split('/[\n\r]+|(?<=\s)-\s/', $clean))));
                if (empty($tasks) && $clean !== '') {
                    $tasks = [$clean];
                }
            }
        }

        $result = [];
        foreach ($tasks as $task) {
            if (is_string($task)) {
                $clean = trim(strip_tags($task));
                $clean = preg_replace('/^[-*•]\s*/u', '', $clean);
                if ($clean !== '') {
                    $result[] = $clean;
                }
            }
        }

        if (empty($result) && filled($fallback)) {
            $cleanFallback = preg_replace('/^[-*•]\s*/u', '', trim(strip_tags($fallback)));
            if ($cleanFallback !== '') {
                $result[] = $cleanFallback;
            }
        }

        return $result;
    }

    private function formatModuleLabel(?Module $module, ?SubModule $subModule): string
    {
        $moduleName = $module?->name;
        $subName = $subModule?->name;

        if ($moduleName && $subName) {
            return "{$moduleName} | {$subName}";
        }

        if ($moduleName && !$subName) {
            return "{$moduleName} | No Sub";
        }

        if (!$moduleName && $subName) {
            return "General | {$subName}";
        }

        return "General | No Sub";
    }
}
