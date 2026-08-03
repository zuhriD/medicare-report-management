<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;
use App\Models\DailyReport;
use App\Models\WeeklyReport;

class DashboardStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getColumns(): int
    {
        $user = auth()->user();
        if ($user && ($user->hasRole('super_admin') || $user->hasRole('admin'))) {
            return 3;
        }
        return 2;
    }

    protected function getStats(): array
    {
        $user = auth()->user();

        if ($user->hasRole('super_admin') || $user->hasRole('admin')) {
            return [
                Stat::make('Total Users', User::count())
                    ->description('Total active users')
                    ->descriptionIcon('heroicon-m-users')
                    ->chart([7, 2, 10, 3, 15, 4, 17])
                    ->color('success'),
                Stat::make('Daily Reports Today', DailyReport::whereDate('report_date', today())->count())
                    ->description('Submitted today')
                    ->descriptionIcon('heroicon-m-document-text')
                    ->color('primary'),
                Stat::make('Weekly Reports', WeeklyReport::count())
                    ->description('Total weekly reports')
                    ->descriptionIcon('heroicon-m-calendar-days')
                    ->color('info'),
            ];
        }

        return [
            Stat::make('My Daily Reports', DailyReport::where('user_id', $user->id)->count())
                ->description('Your total daily reports')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),
            Stat::make('Total Weekly Reports', WeeklyReport::count())
                ->description('System weekly reports')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),
        ];
    }
}
