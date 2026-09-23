<?php

namespace App\Filament\Resources\AttendanceSettingResource\Pages;

use App\Filament\Resources\AttendanceSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAttendanceSettings extends ListRecords
{
    protected static string $resource = AttendanceSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
