<?php

namespace App\Filament\Resources\DailyReportResource\Pages;

use App\Filament\Resources\DailyReportResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDailyReport extends CreateRecord
{
    protected static string $resource = DailyReportResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        session()->put('last_report_date', $data['report_date']);

        return $data;
    }
}
