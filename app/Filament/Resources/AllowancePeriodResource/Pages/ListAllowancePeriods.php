<?php

namespace App\Filament\Resources\AllowancePeriodResource\Pages;

use App\Filament\Resources\AllowancePeriodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAllowancePeriods extends ListRecords
{
    protected static string $resource = AllowancePeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat Periode Baru')
                ->icon('heroicon-o-plus'),
        ];
    }
}
