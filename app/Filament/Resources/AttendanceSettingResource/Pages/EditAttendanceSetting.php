<?php

namespace App\Filament\Resources\AttendanceSettingResource\Pages;

use App\Filament\Resources\AttendanceSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAttendanceSetting extends EditRecord
{
    protected static string $resource = AttendanceSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
