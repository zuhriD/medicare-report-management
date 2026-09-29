<?php

namespace App\Filament\Resources\AllowancePeriodResource\Pages;

use App\Filament\Resources\AllowancePeriodResource;
use App\Models\AllowancePeriod;
use App\Services\AllowanceCalculationService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAllowancePeriod extends CreateRecord
{
    protected static string $resource = AllowancePeriodResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $staffIds = $data['selected_staff_ids'] ?? [];
        unset($data['selected_staff_ids']);

        /** @var AllowancePeriod $period */
        $period = static::getModel()::create($data);

        if (!empty($staffIds)) {
            $calculationService = app(AllowanceCalculationService::class);

            // Check overlap warnings
            $warnings = $calculationService->checkOverlappingStaff(
                $staffIds,
                Carbon::parse($period->start_date),
                Carbon::parse($period->end_date),
                $period->id
            );

            if (!empty($warnings)) {
                Notification::make()
                    ->title('Perhatian: Potensi Klaim Ganda')
                    ->body(implode("<br>", array_slice($warnings, 0, 3)) . (count($warnings) > 3 ? '<br>dan lainnya...' : ''))
                    ->warning()
                    ->persistent()
                    ->send();
            }

            $calculationService->generatePeriodSummary($period, $staffIds);
        }

        return $period;
    }

    protected function getRedirectUrl(): string
    {
        return ManageAllowancePeriod::getUrl(['record' => $this->record]);
    }
}
