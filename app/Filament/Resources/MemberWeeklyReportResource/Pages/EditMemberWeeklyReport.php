<?php

namespace App\Filament\Resources\MemberWeeklyReportResource\Pages;

use App\Filament\Resources\MemberWeeklyReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMemberWeeklyReport extends EditRecord
{
    protected static string $resource = MemberWeeklyReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
