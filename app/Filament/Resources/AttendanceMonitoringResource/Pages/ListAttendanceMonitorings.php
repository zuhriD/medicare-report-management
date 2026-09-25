<?php

namespace App\Filament\Resources\AttendanceMonitoringResource\Pages;

use App\Filament\Resources\AttendanceMonitoringResource;
use Filament\Resources\Pages\ListRecords;

class ListAttendanceMonitorings extends ListRecords
{
    protected static string $resource = AttendanceMonitoringResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
