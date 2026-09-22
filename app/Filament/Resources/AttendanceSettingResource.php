<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceSettingResource\Pages;
use App\Models\AttendanceSetting;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AttendanceSettingResource extends Resource
{
    protected static ?string $model = AttendanceSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'HR & Attendance';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Attendance Policies';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Office Assignment')
                    ->description('Select the target office for this attendance policy.')
                    ->schema([
                        Select::make('office_id')
                            ->relationship('office', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Office'),
                    ]),

                Section::make('Regular Attendance Policy')
                    ->description('Configure working hours window, minimum duration, and regular allowance.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TimePicker::make('regular_check_in_start')
                                    ->label('Check-In Opens At')
                                    ->default('08:00:00')
                                    ->seconds(false)
                                    ->required()
                                    ->helperText('Staff cannot check-in earlier than this time.'),
                                TimePicker::make('regular_check_out_end')
                                    ->label('Check-Out Maximum Time')
                                    ->default('17:00:00')
                                    ->seconds(false)
                                    ->required()
                                    ->helperText('Standard regular work schedule limit.'),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextInput::make('minimum_regular_minutes')
                                    ->label('Minimum Regular Duration (Minutes)')
                                    ->numeric()
                                    ->default(360)
                                    ->required()
                                    ->helperText('Default: 360 minutes (6 hours) to qualify for allowance and OT.'),
                                TextInput::make('regular_allowance_amount')
                                    ->label('Regular Allowance Amount')
                                    ->numeric()
                                    ->default(0.00)
                                    ->prefix('Rp')
                                    ->required()
                                    ->helperText('Nominal allowance earned when minimum regular duration is met.'),
                                TextInput::make('absence_fine_amount')
                                    ->label('Absence Fine / Day')
                                    ->numeric()
                                    ->default(50000.00)
                                    ->prefix('Rp')
                                    ->required()
                                    ->helperText('Fine amount charged per day if staff is absent without approved leave (e.g. Rp 50.000). Set to 0 for branches without fine.'),
                            ]),
                    ]),

                Section::make('Overtime (OT) Policy')
                    ->description('Configure overtime hours window, minimum overtime duration, and OT allowance.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TimePicker::make('overtime_check_in_start')
                                    ->label('OT Check-In Opens At')
                                    ->default('18:00:00')
                                    ->seconds(false)
                                    ->required()
                                    ->helperText('Staff cannot start overtime before this time.'),
                                TimePicker::make('overtime_check_out_end')
                                    ->label('OT Check-Out Maximum Time')
                                    ->default('22:00:00')
                                    ->seconds(false)
                                    ->required()
                                    ->helperText('Overtime window closing limit.'),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('minimum_overtime_minutes')
                                    ->label('Minimum Overtime Duration (Minutes)')
                                    ->numeric()
                                    ->default(120)
                                    ->required()
                                    ->helperText('Default: 120 minutes (2 hours) to qualify for OT allowance.'),
                                TextInput::make('overtime_allowance_amount')
                                    ->label('Overtime Allowance Amount')
                                    ->numeric()
                                    ->default(0.00)
                                    ->prefix('Amount')
                                    ->required()
                                    ->helperText('Additional allowance earned when minimum overtime is met.'),
                            ]),
                    ]),

                Section::make('Policy Validity Period')
                    ->description('Set effective date range and active status. Periods for the same office should not overlap.')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                DatePicker::make('effective_from')
                                    ->label('Effective From')
                                    ->required()
                                    ->default(now()->toDateString())
                                    ->helperText('Start date of this policy version.'),
                                DatePicker::make('effective_until')
                                    ->label('Effective Until')
                                    ->nullable()
                                    ->helperText('Leave empty if this policy has no expiration.'),
                                Toggle::make('is_active')
                                    ->label('Policy is Active')
                                    ->default(true)
                                    ->helperText('Enable or disable this policy record.'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('office.name')
                    ->label('Office')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('regular_window')
                    ->label('Regular Hours')
                    ->getStateUsing(fn ($record) => substr($record->regular_check_in_start, 0, 5) . ' - ' . substr($record->regular_check_out_end, 0, 5))
                    ->badge()
                    ->color('info'),
                TextColumn::make('minimum_regular_minutes')
                    ->label('Min Regular')
                    ->formatStateUsing(fn ($state) => round($state / 60, 1) . 'h (' . $state . 'm)')
                    ->sortable(),
                TextColumn::make('regular_allowance_amount')
                    ->label('Regular Allowance')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                TextColumn::make('absence_fine_amount')
                    ->label('Absence Fine/Day')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                TextColumn::make('overtime_window')
                    ->label('OT Hours')
                    ->getStateUsing(fn ($record) => substr($record->overtime_check_in_start, 0, 5) . ' - ' . substr($record->overtime_check_out_end, 0, 5))
                    ->badge()
                    ->color('warning'),
                TextColumn::make('minimum_overtime_minutes')
                    ->label('Min OT')
                    ->formatStateUsing(fn ($state) => round($state / 60, 1) . 'h (' . $state . 'm)')
                    ->sortable(),
                TextColumn::make('overtime_allowance_amount')
                    ->label('OT Allowance')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('effective_from')
                    ->label('Effective')
                    ->date()
                    ->formatStateUsing(fn ($record) => $record->effective_from?->format('d M Y') . ($record->effective_until ? ' - ' . $record->effective_until->format('d M Y') : ' onwards'))
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('office_id')
                    ->relationship('office', 'name')
                    ->label('Filter by Office'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendanceSettings::route('/'),
            'create' => Pages\CreateAttendanceSetting::route('/create'),
            'edit' => Pages\EditAttendanceSetting::route('/{record}/edit'),
        ];
    }
}
