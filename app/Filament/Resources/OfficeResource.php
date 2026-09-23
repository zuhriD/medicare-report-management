<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OfficeResource\Pages;
use App\Models\Office;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OfficeResource extends Resource
{
    protected static ?string $model = Office::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'HR & Attendance';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Office Information')
                    ->description('Basic office identification and geographical location.')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g. Kuala Lumpur HQ'),
                        Textarea::make('address')
                            ->rows(3)
                            ->nullable()
                            ->placeholder('Full office address...'),
                        Grid::make(3)
                            ->schema([
                                TextInput::make('latitude')
                                    ->numeric()
                                    ->step('any')
                                    ->placeholder('e.g. 3.1340000')
                                    ->helperText('GPS Latitude coordinate for geofencing.')
                                    ->nullable(),
                                TextInput::make('longitude')
                                    ->numeric()
                                    ->step('any')
                                    ->placeholder('e.g. 101.6860000')
                                    ->helperText('GPS Longitude coordinate for geofencing.')
                                    ->nullable(),
                                TextInput::make('attendance_radius_meter')
                                    ->label('Radius (Meters)')
                                    ->numeric()
                                    ->default(100)
                                    ->required()
                                    ->helperText('Maximum allowed distance from office GPS for check-in.'),
                            ]),
                        Grid::make(2)
                            ->schema([
                                Select::make('timezone')
                                    ->options([
                                        'Asia/Kuala_Lumpur' => 'Asia/Kuala_Lumpur (MYT, UTC+8)',
                                        'Asia/Jakarta' => 'Asia/Jakarta (WIB, UTC+7)',
                                        'Asia/Makassar' => 'Asia/Makassar (WITA, UTC+8)',
                                        'Asia/Jayapura' => 'Asia/Jayapura (WIT, UTC+9)',
                                        'Asia/Singapore' => 'Asia/Singapore (SGT, UTC+8)',
                                        'UTC' => 'UTC',
                                    ])
                                    ->default('Asia/Kuala_Lumpur')
                                    ->required()
                                    ->searchable()
                                    ->helperText('Timezone used for opening/closing attendance windows.'),
                                Toggle::make('is_active')
                                    ->label('Office is Active')
                                    ->default(true)
                                    ->helperText('Only active offices allow staff to record attendance.'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('timezone')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('attendance_radius_meter')
                    ->label('Geofence Radius')
                    ->formatStateUsing(fn ($state) => "{$state} m")
                    ->sortable(),
                TextColumn::make('latitude')
                    ->label('Coordinates')
                    ->formatStateUsing(fn ($record) => $record->latitude && $record->longitude ? "{$record->latitude}, {$record->longitude}" : '—')
                    ->url(fn ($record) => $record->latitude && $record->longitude ? "https://www.google.com/maps?q={$record->latitude},{$record->longitude}" : null)
                    ->openUrlInNewTab()
                    ->color('primary')
                    ->icon('heroicon-m-map-pin'),
                TextColumn::make('users_count')
                    ->label('Staff Count')
                    ->counts('users')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOffices::route('/'),
            'create' => Pages\CreateOffice::route('/create'),
            'edit' => Pages\EditOffice::route('/{record}/edit'),
        ];
    }
}
