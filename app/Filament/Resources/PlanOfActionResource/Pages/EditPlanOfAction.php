<?php

namespace App\Filament\Resources\PlanOfActionResource\Pages;

use App\Filament\Resources\PlanOfActionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlanOfAction extends EditRecord
{
    protected static string $resource = PlanOfActionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
            Actions\ForceDeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
