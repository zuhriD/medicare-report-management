<?php

namespace App\Filament\Resources\AllowancePeriodResource\Pages;

use App\Filament\Resources\AllowancePeriodResource;
use App\Filament\Resources\AllowancePeriodResource\Widgets\AllowancePeriodStatsWidget;
use App\Models\AllowancePeriod;
use App\Models\AllowancePeriodStaff;
use App\Models\User;
use App\Services\AllowanceCalculationService;
use Carbon\Carbon;
use Filament\Actions\Action as HeaderAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\View\View;

class ManageAllowancePeriod extends ManageRelatedRecords
{
    protected static string $resource = AllowancePeriodResource::class;

    protected static string $relationship = 'periodStaff';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public function getTitle(): string
    {
        /** @var AllowancePeriod $period */
        $period = $this->getOwnerRecord();
        return "Manajemen Tunjangan: {$period->name}";
    }

    public function getSubheading(): ?string
    {
        /** @var AllowancePeriod $period */
        $period = $this->getOwnerRecord();
        $dates = $period->start_date->format('d M Y') . ' — ' . $period->end_date->format('d M Y');
        $office = $period->office ? " | Kantor: {$period->office->name}" : " | Semua Kantor";
        return "Periode: {$dates}{$office}";
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AllowancePeriodStatsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        /** @var AllowancePeriod $period */
        $period = $this->getOwnerRecord();

        return [
            HeaderAction::make('add_staff')
                ->label('Tambah Staff')
                ->icon('heroicon-o-user-plus')
                ->color('primary')
                ->form([
                    Select::make('user_ids')
                        ->label('Pilih Staff Baru')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(function () use ($period) {
                            $existingUserIds = $period->periodStaff()->pluck('user_id')->toArray();
                            $query = User::query()
                                ->whereNotIn('id', $existingUserIds)
                                ->whereHas('roles', function ($q) {
                                    $q->whereIn('name', ['team_member', 'Team Member', 'team-member', 'staff', 'Staff']);
                                })
                                ->orderBy('name');

                            if ($period->office_id) {
                                $query->where('office_id', $period->office_id);
                            }

                            return $query->get()->mapWithKeys(function ($u) {
                                $off = $u->office ? " ({$u->office->name})" : "";
                                return [$u->id => "{$u->name}{$off} - {$u->email}"];
                            });
                        })
                        ->required(),
                ])
                ->action(function (array $data) use ($period) {
                    $existingUserIds = $period->periodStaff()->pluck('user_id')->toArray();
                    $allUserIds = array_unique(array_merge($existingUserIds, $data['user_ids']));

                    $service = app(AllowanceCalculationService::class);
                    $service->generatePeriodSummary($period, $allUserIds);

                    Notification::make()
                        ->title('Staff Berhasil Ditambahkan')
                        ->body('Data kehadiran dan lembur staff terpilih telah dikalkulasi.')
                        ->success()
                        ->send();
                }),

            HeaderAction::make('recalculate_all')
                ->label('Sinkronisasi Ulang')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Sinkronisasi Ulang Seluruh Kalkulasi?')
                ->modalDescription('Tindakan ini akan mengalkulasi ulang nominal kehadiran dan lembur seluruh staff berdasarkan rekaman presensi terbaru. Status pembayaran yang sudah lunas tidak akan hilang.')
                ->action(function () use ($period) {
                    $userIds = $period->periodStaff()->pluck('user_id')->toArray();
                    $service = app(AllowanceCalculationService::class);
                    $service->generatePeriodSummary($period, $userIds);

                    Notification::make()
                        ->title('Kalkulasi Diperbarui')
                        ->body('Seluruh data tunjangan staff telah disinkronkan.')
                        ->success()
                        ->send();
                }),

            HeaderAction::make('export_pdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->url(fn (): string => route('allowance-periods.export', ['allowancePeriod' => $period, 'format' => 'pdf']))
                ->openUrlInNewTab(),

            HeaderAction::make('export_csv')
                ->label('Export Excel / CSV')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->url(fn (): string => route('allowance-periods.export', ['allowancePeriod' => $period, 'format' => 'csv'])),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('user.name')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Staff Member')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (AllowancePeriodStaff $record): string => ($record->user?->office?->name ?? 'No Office') . ' • ' . ($record->user?->email ?? '')),

                TextColumn::make('attendance_info')
                    ->label('Kehadiran')
                    ->getStateUsing(function (AllowancePeriodStaff $record) {
                        return "{$record->total_attendance_days} Hari";
                    })
                    ->description(fn (AllowancePeriodStaff $record): string => $record->formatted_attendance_amount)
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('total_attendance_amount', $direction)),

                TextColumn::make('overtime_info')
                    ->label('Lembur')
                    ->getStateUsing(function (AllowancePeriodStaff $record) {
                        $hours = round($record->total_overtime_minutes / 60, 1);
                        return "{$hours} Jam ({$record->total_overtime_minutes}m)";
                    })
                    ->description(fn (AllowancePeriodStaff $record): string => $record->formatted_overtime_amount)
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('total_overtime_amount', $direction)),

                TextColumn::make('total_allowance')
                    ->label('Total Tunjangan')
                    ->money('IDR', locale: 'id')
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('payment_status')
                    ->label('Status Bayar')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'unpaid' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'paid' => 'Sudah Dibayar',
                        'unpaid' => 'Belum Dibayar',
                        default => ucfirst($state),
                    }),

                TextColumn::make('payment_details')
                    ->label('Rincian Pembayaran')
                    ->getStateUsing(function (AllowancePeriodStaff $record) {
                        if (!$record->isPaid()) {
                            return 'Menunggu pembayaran';
                        }
                        $method = $record->payment_method ?? 'Transfer';
                        $date = $record->paid_at ? $record->paid_at->format('d/m/Y') : '-';
                        return "{$date} ({$method})";
                    })
                    ->description(function (AllowancePeriodStaff $record) {
                        if (!$record->isPaid()) {
                            return null;
                        }
                        $payer = $record->paidBy?->name ? "by {$record->paidBy->name}" : '';
                        $ref = $record->payment_reference ? "Ref: {$record->payment_reference}" : '';
                        return trim("{$payer} {$ref}");
                    })
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('payment_status')
                    ->label('Status Pembayaran')
                    ->options([
                        'unpaid' => 'Belum Dibayar (Unpaid)',
                        'paid' => 'Sudah Dibayar (Paid)',
                    ]),

                SelectFilter::make('office_id')
                    ->label('Kantor')
                    ->relationship('user.office', 'name'),
            ])
            ->actions([
                Action::make('pay')
                    ->label('Bayar')
                    ->icon('heroicon-o-credit-card')
                    ->color('success')
                    ->button()
                    ->visible(fn (AllowancePeriodStaff $record): bool => $record->isUnpaid())
                    ->form([
                        DatePicker::make('paid_at')
                            ->label('Tanggal Pembayaran')
                            ->default(now())
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Select::make('payment_method')
                            ->label('Metode Pembayaran')
                            ->options([
                                'Transfer Bank' => 'Transfer Bank',
                                'Tunai / Cash' => 'Tunai / Cash',
                                'Payroll Transfer' => 'Payroll Transfer',
                                'Lainnya' => 'Lainnya',
                            ])
                            ->default('Transfer Bank')
                            ->required(),

                        TextInput::make('payment_reference')
                            ->label('Nomor Referensi / Bukti Transfer')
                            ->placeholder('Contoh: TRF-20260929-0012')
                            ->maxLength(255),

                        Textarea::make('notes')
                            ->label('Catatan Pembayaran')
                            ->placeholder('Catatan opsional...')
                            ->rows(2),
                    ])
                    ->action(function (AllowancePeriodStaff $record, array $data) {
                        $service = app(AllowanceCalculationService::class);
                        $service->markStaffAsPaid($record, $data);

                        Notification::make()
                            ->title('Pembayaran Berhasil Dicatat')
                            ->body("Tunjangan untuk {$record->user?->name} sebesar {$record->formatted_total_allowance} telah ditandai lunas.")
                            ->success()
                            ->send();
                    }),

                Action::make('revert')
                    ->label('Batalkan')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(fn (AllowancePeriodStaff $record): bool => $record->isPaid())
                    ->requiresConfirmation()
                    ->modalHeading('Batalkan Status Pembayaran?')
                    ->modalDescription(fn (AllowancePeriodStaff $record): string => "Apakah Anda yakin ingin mengembalikan status {$record->user?->name} menjadi 'Belum Dibayar'?")
                    ->action(function (AllowancePeriodStaff $record) {
                        $service = app(AllowanceCalculationService::class);
                        $service->markStaffAsUnpaid($record);

                        Notification::make()
                            ->title('Status Pembayaran Dibatalkan')
                            ->body("Status {$record->user?->name} telah dikembalikan ke Belum Dibayar.")
                            ->warning()
                            ->send();
                    }),

                Action::make('view_breakdown')
                    ->label('Rincian')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('info')
                    ->modalHeading(fn (AllowancePeriodStaff $record): string => "Rincian Harian Presensi & Lembur - {$record->user?->name}")
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(fn (AllowancePeriodStaff $record): View => view(
                        'filament.resources.allowance-period.breakdown-modal',
                        ['staffRecord' => $record->load(['items.attendance', 'items.overtime', 'user.office'])]
                    )),
            ])
            ->bulkActions([
                BulkAction::make('bulk_pay')
                    ->label('Bayar Terpilih Sekaligus')
                    ->icon('heroicon-o-credit-card')
                    ->color('success')
                    ->form([
                        DatePicker::make('paid_at')
                            ->label('Tanggal Pembayaran')
                            ->default(now())
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Select::make('payment_method')
                            ->label('Metode Pembayaran')
                            ->options([
                                'Transfer Bank' => 'Transfer Bank',
                                'Tunai / Cash' => 'Tunai / Cash',
                                'Payroll Transfer' => 'Payroll Transfer',
                                'Lainnya' => 'Lainnya',
                            ])
                            ->default('Transfer Bank')
                            ->required(),

                        TextInput::make('payment_reference')
                            ->label('Nomor Referensi / Bukti Transfer (Opsional)')
                            ->placeholder('Contoh: BATCH-TRF-20260929')
                            ->maxLength(255),

                        Textarea::make('notes')
                            ->label('Catatan')
                            ->placeholder('Catatan opsional untuk seluruh staff terpilih...')
                            ->rows(2),
                    ])
                    ->action(function (Collection $records, array $data) {
                        $unpaidRecords = $records->filter(fn (AllowancePeriodStaff $r) => $r->isUnpaid());
                        
                        if ($unpaidRecords->isEmpty()) {
                            Notification::make()
                                ->title('Tidak Ada Staff Belum Dibayar')
                                ->body('Seluruh staff terpilih sudah berstatus lunas.')
                                ->info()
                                ->send();
                            return;
                        }

                        $service = app(AllowanceCalculationService::class);
                        $service->markBulkStaffAsPaid($unpaidRecords, $data);

                        $count = $unpaidRecords->count();
                        Notification::make()
                            ->title('Pembayaran Massal Berhasil')
                            ->body("{$count} staff telah berhasil ditandai lunas.")
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }
}
