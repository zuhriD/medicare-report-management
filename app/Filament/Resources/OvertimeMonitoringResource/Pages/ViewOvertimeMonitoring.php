<?php

namespace App\Filament\Resources\OvertimeMonitoringResource\Pages;

use App\Filament\Resources\OvertimeMonitoringResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewOvertimeMonitoring extends ViewRecord
{
    protected static string $resource = OvertimeMonitoringResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => auth()->user()?->hasRole(['hr', 'HR', 'admin', 'super_admin', 'Admin', 'Super Admin'])),
        ];
    }
}
