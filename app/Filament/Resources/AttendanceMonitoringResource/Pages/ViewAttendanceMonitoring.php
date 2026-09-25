<?php

namespace App\Filament\Resources\AttendanceMonitoringResource\Pages;

use App\Filament\Resources\AttendanceMonitoringResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAttendanceMonitoring extends ViewRecord
{
    protected static string $resource = AttendanceMonitoringResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => auth()->user()?->hasRole(['hr', 'HR', 'admin', 'super_admin', 'Admin', 'Super Admin'])),
        ];
    }
}
