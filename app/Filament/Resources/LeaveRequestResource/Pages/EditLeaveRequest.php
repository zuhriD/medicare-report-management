<?php

namespace App\Filament\Resources\LeaveRequestResource\Pages;

use App\Filament\Resources\LeaveRequestResource;
use App\Models\Office;
use App\Services\AttendanceFineService;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLeaveRequest extends EditRecord
{
    protected static string $resource = LeaveRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);

        if ($end->lt($start)) {
            $end = $start->copy();
            $data['end_date'] = $start->toDateString();
        }

        $office = !empty($data['office_id']) ? Office::find($data['office_id']) : null;
        $fineService = app(AttendanceFineService::class);

        if ($office) {
            $calc = $fineService->calculateLeaveNormalFine($office, $start, $end);
            $data['total_days'] = $calc['total_days'];
            $data['normal_fine_amount'] = $calc['normal_fine_amount'];
        } else {
            $days = $fineService->calculateWorkingDays($start, $end);
            $data['total_days'] = $days;
            $data['normal_fine_amount'] = $days * 50000.00;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
