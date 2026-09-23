<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OvertimeMonitoringResource\Pages;
use App\Models\Overtime;
use App\Services\AttendanceCalculationService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
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

class OvertimeMonitoringResource extends Resource
{
    protected static ?string $model = Overtime::class;

    protected static ?string $navigationIcon = 'heroicon-o-fire';

    protected static ?string $navigationGroup = 'HR & Attendance';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Overtime Monitoring';

    public static function canCreate(): bool
    {
        return false; // Monitoring is populated by staff overtime events
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Overtime Information')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('attendance.user.name')->label('Staff Name')->disabled(),
                            TextInput::make('attendance.office.name')->label('Office')->disabled(),
                            TextInput::make('overtime_date')->label('Date')->disabled(),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('check_in_at')->label('OT Check-In Time')->disabled(),
                            TextInput::make('check_out_at')->label('OT Check-Out Time')->disabled(),
                        ]),
                        Grid::make(3)->schema([
                            TextInput::make('overtime_minutes')->label('Overtime Minutes')->disabled(),
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
                            TextEntry::make('attendance.user.name')->label('Staff Name')->weight('bold'),
                            TextEntry::make('attendance.user.username')->label('Username')->badge(),
                            TextEntry::make('attendance.office.name')->label('Office')->badge()->color('info'),
                            TextEntry::make('overtime_date')->label('Overtime Date')->date('d F Y'),
                        ]),
                    ]),

                InfoSection::make('Overtime Check-In Details')
                    ->schema([
                        InfoGrid::make(3)->schema([
                            TextEntry::make('check_in_at')
                                ->label('OT Check-In Timestamp')
                                ->dateTime('d M Y, H:i:s')
                                ->timezone(fn ($record) => $record->attendance?->office?->timezone ?? config('app.timezone'))
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
                                ->label('OT Check-In Live Selfie')
                                ->height(160)
                                ->circular(false)
                                ->extraImgAttributes(['class' => 'rounded-lg shadow border object-cover'])
                                ->placeholder('No selfie captured'),
                        ]),
                    ]),

                InfoSection::make('Overtime Check-Out Details')
                    ->schema([
                        InfoGrid::make(3)->schema([
                            TextEntry::make('check_out_at')
                                ->label('OT Check-Out Timestamp')
                                ->dateTime('d M Y, H:i:s')
                                ->timezone(fn ($record) => $record->attendance?->office?->timezone ?? config('app.timezone'))
                                ->placeholder('In progress'),
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
                                ->label('OT Check-Out Live Selfie')
                                ->height(160)
                                ->circular(false)
                                ->extraImgAttributes(['class' => 'rounded-lg shadow border object-cover'])
                                ->placeholder('No selfie captured'),
                        ]),
                    ]),

                InfoSection::make('Overtime Calculation & Allowance')
                    ->schema([
                        InfoGrid::make(3)->schema([
                            TextEntry::make('overtime_minutes')
                                ->label('Overtime Duration')
                                ->formatStateUsing(fn ($state) => app(AttendanceCalculationService::class)->formatMinutesToDuration($state ?? 0) . " ({$state}m)")
                                ->badge()
                                ->color(fn ($record) => $record->allowance_eligible ? 'success' : 'warning'),
                            IconEntry::make('allowance_eligible')
                                ->label('OT Allowance Eligible')
                                ->boolean(),
                            TextEntry::make('allowance_amount')
                                ->label('OT Allowance')
                                ->numeric(decimalPlaces: 2)
                                ->prefix('Amount: ')
                                ->weight('bold'),
                        ]),
                        TextEntry::make('notes')
                            ->label('Overtime Notes / Reason')
                            ->placeholder('No notes provided')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('overtime_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('attendance.user.name')
                    ->label('Staff')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('attendance.office.name')
                    ->label('Office')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('check_in_at')
                    ->label('OT In')
                    ->time('H:i')
                    ->timezone(fn ($record) => $record->attendance?->office?->timezone ?? config('app.timezone'))
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('check_out_at')
                    ->label('OT Out')
                    ->time('H:i')
                    ->timezone(fn ($record) => $record->attendance?->office?->timezone ?? config('app.timezone'))
                    ->placeholder('In Progress')
                    ->sortable(),
                TextColumn::make('overtime_minutes')
                    ->label('OT Duration')
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
            ])
            ->defaultSort('overtime_date', 'desc')
            ->filters([
                Filter::make('date_range')
                    ->form([
                        DatePicker::make('from')->label('From Date'),
                        DatePicker::make('until')->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('overtime_date', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('overtime_date', '<=', $date));
                    }),
                SelectFilter::make('office')
                    ->relationship('attendance.office', 'name')
                    ->label('Filter Office'),
                SelectFilter::make('user')
                    ->relationship('attendance.user', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Filter Staff'),
                TernaryFilter::make('allowance_eligible')
                    ->label('OT Allowance Eligible'),
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
            'index' => Pages\ListOvertimeMonitorings::route('/'),
            'view' => Pages\ViewOvertimeMonitoring::route('/{record}'),
        ];
    }
}
