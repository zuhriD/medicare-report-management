<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceMonitoringResource\Pages;
use App\Models\Attendance;
use App\Services\AttendanceCalculationService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Attendance Information')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('user.name')->label('Staff Name')->disabled(),
                            TextInput::make('office.name')->label('Office')->disabled(),
                            TextInput::make('attendance_date')->label('Date')->disabled(),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('check_in_at')->label('Check-In Time')->disabled(),
                            TextInput::make('check_out_at')->label('Check-Out Time')->disabled(),
                        ]),
                        Grid::make(3)->schema([
                            TextInput::make('working_minutes')->label('Working Minutes')->disabled(),
                            TextInput::make('allowance_eligible')->label('Allowance Eligible')->disabled(),
                            TextInput::make('allowance_amount')->label('Allowance Amount')->disabled(),
                        ]),
                    ]),
            ]);
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
        ];
    }
}
