<?php

namespace App\Filament\Resources\AttendanceSettingResource\Pages;

use App\Filament\Resources\AttendanceSettingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAttendanceSetting extends CreateRecord
{
    protected static string $resource = AttendanceSettingResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
