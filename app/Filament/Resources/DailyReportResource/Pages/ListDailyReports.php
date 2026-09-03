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
            Actions\CreateAction::make(),
        ];

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user && ($user->hasRole('super_admin') || $user->hasRole('admin'))) {
            $actions[] = Actions\Action::make('poa_act_recap')
                ->label('POA & ACT Report')
                ->icon('heroicon-m-document-text')
                ->color('info')
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
                                ])->render()
                            );
                        }),
                ]);
        }

        return $actions;
    }
}
