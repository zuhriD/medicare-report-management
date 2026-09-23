<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceMonitoringResource\Pages;
use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Services\AttendanceCalculationService;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\Grid as InfoGrid;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AttendanceMonitoringResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'HR & Attendance';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Attendance Monitoring';

    public static function canCreate(): bool
    {
        return false; // Monitoring is populated by staff attendance events
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();
        return $user ? $user->hasRole(['hr', 'HR', 'admin', 'super_admin', 'Admin', 'Super Admin']) : false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Staff & Office Information')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('staff_name')
                                ->label('Staff Name')
                                ->formatStateUsing(fn (?Attendance $record) => $record?->user?->name ?? '—')
                                ->disabled()
                                ->dehydrated(false),
                            TextInput::make('office_name')
                                ->label('Office')
                                ->formatStateUsing(fn (?Attendance $record) => $record?->office?->name ?? '—')
                                ->disabled()
                                ->dehydrated(false),
                            DatePicker::make('attendance_date')
                                ->label('Attendance Date')
                                ->required(),
                        ]),
                    ]),
                Section::make('Attendance Timestamps & Allowance')
                    ->schema([
                        Grid::make(2)->schema([
                            DateTimePicker::make('check_in_at')
                                ->label('Check-In Time')
                                ->seconds(false)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, $get, $set, $record) {
                                    static::recalculateAttendanceForm($get, $set, $record);
                                })
                                ->nullable(),
                            DateTimePicker::make('check_out_at')
                                ->label('Check-Out Time')
                                ->seconds(false)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, $get, $set, $record) {
                                    static::recalculateAttendanceForm($get, $set, $record);
                                })
                                ->nullable(),
                        ]),
                        Grid::make(3)->schema([
                            TextInput::make('working_minutes')
                                ->label('Working Duration (Minutes)')
                                ->numeric()
                                ->required()
                                ->helperText('Durasi kerja efektif dalam menit.'),
                            Toggle::make('allowance_eligible')
                                ->label('Allowance Eligible')
                                ->inline(false)
                                ->helperText('Kelayakan uang saku kehadiran reguler.'),
                            TextInput::make('allowance_amount')
                                ->label('Allowance Amount')
                                ->numeric()
                                ->prefix('Rp')
                                ->required(),
                        ]),
                        Textarea::make('notes')
                            ->label('HR / Attendance Notes')
                            ->rows(3)
                            ->placeholder('Catatan atau alasan penyesuaian absensi oleh HR...')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function recalculateAttendanceForm($get, $set, ?Attendance $record): void
    {
        $checkIn = $get('check_in_at');
        $checkOut = $get('check_out_at');

        if (filled($checkIn) && filled($checkOut)) {
            try {
                $in = Carbon::parse($checkIn);
                $out = Carbon::parse($checkOut);
                $totalBreakMinutes = $record ? $record->totalBreakMinutes() : 0;
                $service = app(AttendanceCalculationService::class);
                $workingMinutes = $service->calculateWorkingMinutes($in, $out, $totalBreakMinutes);
                $set('working_minutes', $workingMinutes);

                $policy = $record?->attendanceSetting ?? AttendanceSetting::active()->first();
                if ($policy) {
                    $eval = $service->evaluateRegularAllowance($workingMinutes, $policy);
                    $set('allowance_eligible', $eval['allowance_eligible']);
                    $set('allowance_amount', $eval['allowance_amount']);
                }
            } catch (\Throwable $e) {
                // Ignore parsing exceptions while user is actively typing
            }
        }
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfoSection::make('Staff & Office Information')
                    ->schema([
                        InfoGrid::make(4)->schema([
                            TextEntry::make('user.name')->label('Staff Name')->weight('bold'),
                            TextEntry::make('user.username')->label('Username')->badge(),
                            TextEntry::make('office.name')->label('Office')->badge()->color('info'),
                            TextEntry::make('attendance_date')->label('Date')->date('d F Y'),
                        ]),
                    ]),

                InfoSection::make('Check-In Details')
                    ->schema([
                        InfoGrid::make(3)->schema([
                            TextEntry::make('check_in_at')
                                ->label('Check-In Timestamp')
                                ->dateTime('d M Y, H:i:s')
                                ->timezone(fn ($record) => $record->office?->timezone ?? config('app.timezone'))
                                ->placeholder('Not checked in'),
                            TextEntry::make('check_in_coordinates')
                                ->label('GPS Location')
                                ->getStateUsing(fn ($record) => $record->check_in_latitude && $record->check_in_longitude
                                    ? "{$record->check_in_latitude}, {$record->check_in_longitude} (±{$record->check_in_accuracy}m)"
                                    : '—')
                                ->url(fn ($record) => $record->check_in_latitude && $record->check_in_longitude
                                    ? "https://www.google.com/maps?q={$record->check_in_latitude},{$record->check_in_longitude}"
                                    : null)
                                ->openUrlInNewTab()
                                ->color('primary')
                                ->icon('heroicon-m-map-pin'),
                            ImageEntry::make('check_in_selfie_url')
                                ->label('Check-In Live Selfie')
                                ->height(160)
                                ->circular(false)
                                ->extraImgAttributes(['class' => 'rounded-lg shadow border object-cover'])
                                ->placeholder('No selfie captured'),
                        ]),
                    ]),

                InfoSection::make('Check-Out Details')
                    ->schema([
                        InfoGrid::make(3)->schema([
                            TextEntry::make('check_out_at')
                                ->label('Check-Out Timestamp')
                                ->dateTime('d M Y, H:i:s')
                                ->timezone(fn ($record) => $record->office?->timezone ?? config('app.timezone'))
                                ->placeholder('Not checked out yet'),
                            TextEntry::make('check_out_coordinates')
                                ->label('GPS Location')
                                ->getStateUsing(fn ($record) => $record->check_out_latitude && $record->check_out_longitude
                                    ? "{$record->check_out_latitude}, {$record->check_out_longitude} (±{$record->check_out_accuracy}m)"
                                    : '—')
                                ->url(fn ($record) => $record->check_out_latitude && $record->check_out_longitude
                                    ? "https://www.google.com/maps?q={$record->check_out_latitude},{$record->check_out_longitude}"
                                    : null)
                                ->openUrlInNewTab()
                                ->color('primary')
                                ->icon('heroicon-m-map-pin'),
                            ImageEntry::make('check_out_selfie_url')
                                ->label('Check-Out Live Selfie')
                                ->height(160)
                                ->circular(false)
                                ->extraImgAttributes(['class' => 'rounded-lg shadow border object-cover'])
                                ->placeholder('No selfie captured'),
                        ]),
                    ]),

                InfoSection::make('Riwayat Izin Keluar / Jeda Absensi')
                    ->schema([
                        \Filament\Infolists\Components\RepeatableEntry::make('breaks')
                            ->label('')
                            ->schema([
                                InfoGrid::make(4)->schema([
                                    TextEntry::make('reason')->label('Alasan Izin')->weight('bold'),
                                    TextEntry::make('paused_at')->label('Jam Keluar')->dateTime('H:i:s (d M)'),
                                    TextEntry::make('resumed_at')->label('Jam Kembali')->dateTime('H:i:s (d M)')->placeholder('Sedang di Luar (Aktif)'),
                                    TextEntry::make('duration_minutes')->label('Durasi Jeda')->formatStateUsing(fn ($state) => $state ? app(AttendanceCalculationService::class)->formatMinutesToDuration($state) : 'Sedang Berjalan')->badge()->color(fn ($record) => $record?->isOpen() ? 'warning' : 'info'),
                                ]),
                                TextEntry::make('notes')->label('Catatan Tambahan')->placeholder('—'),
                            ])
                            ->columnSpanFull()
                            ->visible(fn ($record) => $record->breaks()->exists()),
                        TextEntry::make('no_breaks_notice')
                            ->label('')
                            ->default('Tidak ada riwayat izin keluar / jeda pada kehadiran ini.')
                            ->visible(fn ($record) => !$record->breaks()->exists()),
                    ])
                    ->collapsible(),

                InfoSection::make('Calculation & Allowance Snapshot')
                    ->schema([
                        InfoGrid::make(4)->schema([
                            TextEntry::make('working_minutes')
                                ->label('Working Duration')
                                ->formatStateUsing(fn ($state) => app(AttendanceCalculationService::class)->formatMinutesToDuration($state ?? 0) . " ({$state}m)")
                                ->badge()
                                ->color(fn ($record) => $record->allowance_eligible ? 'success' : 'warning'),
                            IconEntry::make('allowance_eligible')
                                ->label('Allowance Eligible')
                                ->boolean(),
                            TextEntry::make('allowance_amount')
                                ->label('Regular Allowance')
                                ->numeric(decimalPlaces: 2)
                                ->prefix('Amount: ')
                                ->weight('bold'),
                            TextEntry::make('overtime_status')
                                ->label('Overtime Session')
                                ->getStateUsing(fn ($record) => $record->overtime()->exists() ? 'Recorded' : 'None')
                                ->badge()
                                ->color(fn ($state) => $state === 'Recorded' ? 'success' : 'gray'),
                        ]),
                        TextEntry::make('notes')
                            ->label('Staff Notes')
                            ->placeholder('No notes provided')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('attendance_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Staff')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->user?->username)
                    ->weight('bold'),
                TextColumn::make('office.name')
                    ->label('Office')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('check_in_at')
                    ->label('Check-In')
                    ->time('H:i')
                    ->timezone(fn ($record) => $record->office?->timezone ?? config('app.timezone'))
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('check_out_at')
                    ->label('Check-Out')
                    ->time('H:i')
                    ->timezone(fn ($record) => $record->office?->timezone ?? config('app.timezone'))
                    ->placeholder('In Progress')
                    ->sortable(),
                TextColumn::make('breaks_summary')
                    ->label('Izin Keluar')
                    ->getStateUsing(function ($record) {
                        $count = $record->breaks()->count();
                        if ($count === 0) return '—';
                        $totalMinutes = $record->totalBreakMinutes();
                        $duration = app(AttendanceCalculationService::class)->formatMinutesToDuration($totalMinutes);
                        $isPaused = $record->isPaused() ? ' (Jeda Aktif)' : '';
                        return "{$count}x ({$duration}){$isPaused}";
                    })
                    ->badge()
                    ->color(fn ($record) => $record->isPaused() ? 'warning' : ($record->breaks()->count() > 0 ? 'info' : 'gray'))
                    ->toggleable(),
                TextColumn::make('working_minutes')
                    ->label('Duration')
                    ->formatStateUsing(fn ($state) => $state ? app(AttendanceCalculationService::class)->formatMinutesToDuration($state) : '—')
                    ->badge()
                    ->color(fn ($record) => $record->allowance_eligible ? 'success' : 'gray')
                    ->sortable(),
                IconColumn::make('allowance_eligible')
                    ->label('Allowance')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('allowance_amount')
                    ->label('Nominal')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->toggleable(),
                ImageColumn::make('check_in_selfie_url')
                    ->label('Selfie IN')
                    ->circular()
                    ->toggleable(),
                ImageColumn::make('check_out_selfie_url')
                    ->label('Selfie OUT')
                    ->circular()
                    ->toggleable(),
                TextColumn::make('overtime_badge')
                    ->label('OT')
                    ->getStateUsing(fn ($record) => $record->overtime()->exists() ? 'OT Done' : '—')
                    ->badge()
                    ->color(fn ($state) => $state === 'OT Done' ? 'warning' : 'gray')
                    ->toggleable(),
            ])
            ->defaultSort('attendance_date', 'desc')
            ->filters([
                Filter::make('date_range')
                    ->form([
                        DatePicker::make('from')->label('From Date'),
                        DatePicker::make('until')->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('attendance_date', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('attendance_date', '<=', $date));
                    }),
                SelectFilter::make('office_id')
                    ->relationship('office', 'name')
                    ->label('Filter Office'),
                SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Filter Staff'),
                TernaryFilter::make('allowance_eligible')
                    ->label('Allowance Eligible'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn () => auth()->user()?->hasRole(['hr', 'HR', 'admin', 'super_admin', 'Admin', 'Super Admin'])),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendanceMonitorings::route('/'),
            'view' => Pages\ViewAttendanceMonitoring::route('/{record}'),
            'edit' => Pages\EditAttendanceMonitoring::route('/{record}/edit'),
        ];
    }
}
