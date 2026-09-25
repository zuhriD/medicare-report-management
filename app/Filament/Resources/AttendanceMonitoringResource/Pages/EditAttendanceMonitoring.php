<?php

namespace App\Filament\Resources\AttendanceMonitoringResource\Pages;

use App\Filament\Resources\AttendanceMonitoringResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAttendanceMonitoring extends EditRecord
{
    protected static string $resource = AttendanceMonitoringResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }
}
