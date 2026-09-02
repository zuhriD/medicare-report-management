<?php

namespace App\Filament\Resources\PlanOfActionResource\Pages;

use App\Filament\Resources\PlanOfActionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePlanOfAction extends CreateRecord
{
    protected static string $resource = PlanOfActionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
