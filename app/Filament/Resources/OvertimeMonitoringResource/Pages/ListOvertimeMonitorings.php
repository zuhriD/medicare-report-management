<?php

namespace App\Filament\Resources\OvertimeMonitoringResource\Pages;

use App\Filament\Resources\OvertimeMonitoringResource;
use Filament\Resources\Pages\ListRecords;

class ListOvertimeMonitorings extends ListRecords
{
    protected static string $resource = OvertimeMonitoringResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
