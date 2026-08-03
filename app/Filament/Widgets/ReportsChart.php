<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\DailyReport;
use Illuminate\Support\Carbon;

class ReportsChart extends ChartWidget
{
    protected static ?string $heading = 'Daily Reports (Last 7 Days)';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $maxHeight = '250px';

    protected function getData(): array
    {
        $data = [];
        $labels = [];
        
        $user = auth()->user();
        $isAdmin = $user->hasRole('super_admin') || $user->hasRole('admin');

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $labels[] = $date->format('M d');
            
            $query = DailyReport::whereDate('report_date', $date);
            
            if (!$isAdmin) {
                $query->where('user_id', $user->id);
            }
            
            $data[] = $query->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Reports Submitted',
                    'data' => $data,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => '#93c5fd',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
}
