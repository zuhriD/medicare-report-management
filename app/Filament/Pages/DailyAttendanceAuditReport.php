<?php

namespace App\Filament\Pages;

use App\Models\Office;
use App\Models\User;
use App\Services\DailyAttendanceAuditService;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class DailyAttendanceAuditReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'HR & Attendance';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Daily Attendance Audit';

    protected static ?string $navigationLabel = 'Daily Attendance Audit';

    protected static string $view = 'filament.pages.daily-attendance-audit-report';

    public string $selectedDate = '';
    public ?int $selectedOfficeId = null;
    public ?int $selectedUserId = null;

    public ?string $previewImageUrl = null;
    public ?string $previewImageTitle = null;
    public bool $showPreviewModal = false;

    public ?array $selectedBreaksDetail = null;
    public bool $showBreaksModal = false;

    public static function canAccess(): bool
    {
        return true;
    }

    public function mount(): void
    {
        $this->selectedDate = now()->toDateString();

        // If regular user (non-admin), lock to self
        $user = auth()->user();
        if ($user && method_exists($user, 'hasRole') && !$user->hasRole(['admin', 'super_admin', 'lead', 'Admin', 'Super Admin', 'Manager'])) {
            $this->selectedUserId = $user->id;
            $this->selectedOfficeId = $user->office_id;
        }
    }

    public function getOfficesProperty(): Collection
    {
        return Office::orderBy('name')->get();
    }

    public function getUsersProperty(): Collection
    {
        $query = User::orderBy('name');
        if ($this->selectedOfficeId) {
            $query->where('office_id', $this->selectedOfficeId);
        }
        return $query->get();
    }

    public function getAuditDataProperty(): array
    {
        $service = app(DailyAttendanceAuditService::class);
        $dateStr = $this->selectedDate ?: now()->toDateString();

        return $service->getDailyAuditData(
            $dateStr,
            $this->selectedOfficeId,
            $this->selectedUserId
        );
    }

    public function openImagePreview(?string $url, string $title = 'Foto Presensi'): void
    {
        if ($url) {
            $this->previewImageUrl = $url;
            $this->previewImageTitle = $title;
            $this->showPreviewModal = true;
        }
    }

    public function closeImagePreview(): void
    {
        $this->showPreviewModal = false;
        $this->previewImageUrl = null;
        $this->previewImageTitle = null;
    }

    public function openBreaksModal(int $userId): void
    {
        $audit = $this->getAuditDataProperty();
        $staff = collect($audit['staff_logs'])->firstWhere('user_id', $userId);

        if ($staff && !empty($staff['breaks'])) {
            $this->selectedBreaksDetail = $staff;
            $this->showBreaksModal = true;
        }
    }

    public function closeBreaksModal(): void
    {
        $this->showBreaksModal = false;
        $this->selectedBreaksDetail = null;
    }

    public function getPdfExportUrlProperty(): string
    {
        $dateStr = $this->selectedDate ?: now()->toDateString();

        return route('daily-attendance-audit.export', [
            'date' => $dateStr,
            'office_id' => $this->selectedOfficeId,
            'user_id' => $this->selectedUserId,
            'format' => 'pdf',
        ]);
    }

    public function getCsvExportUrlProperty(): string
    {
        $dateStr = $this->selectedDate ?: now()->toDateString();

        return route('daily-attendance-audit.export', [
            'date' => $dateStr,
            'office_id' => $this->selectedOfficeId,
            'user_id' => $this->selectedUserId,
            'format' => 'csv',
        ]);
    }
}
