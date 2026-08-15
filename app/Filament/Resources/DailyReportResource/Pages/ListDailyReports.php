<?php

namespace App\Filament\Resources\DailyReportResource\Pages;

use App\Filament\Resources\DailyReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDailyReports extends ListRecords
{
    protected static string $resource = DailyReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('print_daily_report')
                ->label('Print Daily Report')
                ->icon('heroicon-o-printer')
                ->color('info')
                ->modalHeading('Print Daily Report')
                ->modalDescription('Select a date to view and print daily reports submitted on that day.')
                ->modalSubmitActionLabel('Print Report')
                ->form([
                    \Filament\Forms\Components\DatePicker::make('report_date')
                        ->label('Select Date')
                        ->default(now())
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $date = \Illuminate\Support\Carbon::parse($data['report_date'])->format('Y-m-d');
                    $url = route('daily-reports.print', ['date' => $date]);
                    $this->js("window.open('{$url}', '_blank')");
                }),
            Actions\CreateAction::make(),
        ];
    }
}

