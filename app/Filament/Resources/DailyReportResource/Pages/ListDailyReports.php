<?php

namespace App\Filament\Resources\DailyReportResource\Pages;

use App\Filament\Resources\DailyReportResource;
use App\Services\PoaActRecapService;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class ListDailyReports extends ListRecords
{
    protected static string $resource = DailyReportResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [
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
        ];

        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if ($user && ($user->hasRole('super_admin') || $user->hasRole('admin'))) {
            $actions[] = Actions\Action::make('poa_act_recap')
                ->label('POA & ACT Recap')
                ->icon('heroicon-m-document-text')
                ->color('primary')
                ->visible(fn () => auth()->user()?->hasAnyRole(['admin', 'super_admin']))
                ->modalHeading('PLAN OF ACTION (POA) & ACHIEVEMENT (ACT) RECAP')
                ->modalIcon('heroicon-o-document-text')
                ->modalWidth('4xl')
                ->modalSubmitAction(false)
                ->form([
                    Forms\Components\DatePicker::make('recap_date')
                        ->label('Select Date')
                        ->default(now()->toDateString())
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->live(),
                    Forms\Components\Placeholder::make('recap_content')
                        ->hiddenLabel()
                        ->content(function (Forms\Get $get) {
                            $rawDate = $get('recap_date') ?? now()->toDateString();
                            $recapData = app(PoaActRecapService::class)->generateRecap($rawDate);

                            return new HtmlString(
                                view('daily-reports.poa-act-recap-modal', [
                                    'recapText' => $recapData['recapText'],
                                    'dateStr' => $recapData['dateStr'],
                                    'dbDate' => $recapData['dbDate'],
                                ])->render()
                            );
                        }),
                ]);
        }

        $actions[] = Actions\CreateAction::make();

        return $actions;
    }
}
