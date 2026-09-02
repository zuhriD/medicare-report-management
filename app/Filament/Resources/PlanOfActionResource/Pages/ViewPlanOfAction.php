<?php

namespace App\Filament\Resources\PlanOfActionResource\Pages;

use App\Filament\Resources\PlanOfActionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPlanOfAction extends ViewRecord
{
    protected static string $resource = PlanOfActionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
