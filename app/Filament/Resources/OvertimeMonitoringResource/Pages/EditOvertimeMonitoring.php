<?php

namespace App\Filament\Resources\OvertimeMonitoringResource\Pages;

use App\Filament\Resources\OvertimeMonitoringResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOvertimeMonitoring extends EditRecord
{
    protected static string $resource = OvertimeMonitoringResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }
}
