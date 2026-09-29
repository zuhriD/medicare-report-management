<?php

namespace App\Filament\Resources\AllowancePeriodResource\Pages;

use App\Filament\Resources\AllowancePeriodResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAllowancePeriod extends EditRecord
{
    protected static string $resource = AllowancePeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => !$this->record->periodStaff()->where('payment_status', 'paid')->exists()),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return ManageAllowancePeriod::getUrl(['record' => $this->record]);
    }
}
