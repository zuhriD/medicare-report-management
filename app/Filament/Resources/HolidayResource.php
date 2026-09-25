<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HolidayResource\Pages;
use App\Models\Holiday;
use App\Models\Office;
use App\Services\HolidayService;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HolidayResource extends Resource
{
    protected static ?string $model = Holiday::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationGroup = 'HR & Attendance';

    protected static ?int $navigationSort = 7;

    protected static ?string $navigationLabel = 'Holiday Calendar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Holiday Details')
                    ->description('Tentukan tanggal, nama hari libur, dan cakupan kantor.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('holiday_date')
                                    ->label('Tanggal Libur')
                                    ->required()
                                    ->default(now()->toDateString()),

                                TextInput::make('name')
                                    ->label('Nama Hari Libur')
                                    ->required()
                                    ->placeholder('Contoh: Hari Kemerdekaan RI, Cuti Bersama')
                                    ->maxLength(255),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('office_id')
                                    ->label('Cakupan Kantor')
                                    ->relationship('office', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Semua Kantor (Global)'),

                                Select::make('country_code')
                                    ->label('Negara')
                                    ->options([
                                        'ID' => 'Indonesia (ID)',
                                        'MY' => 'Malaysia (MY)',
                                    ])
                                    ->default('ID')
                                    ->required(),
                            ]),

                        Toggle::make('is_national_holiday')
                            ->label('Libur Resmi Nasional')
                            ->helperText('Jika non-aktif, dianggap sebagai libur khusus / internal kantor.')
                            ->default(true),

                        Textarea::make('description')
                            ->label('Catatan Tambahan')
                            ->placeholder('Keterangan opsional...')
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $currentYear = (int) now()->year;

        return $table
            ->columns([
                TextColumn::make('holiday_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Nama Hari Libur')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('office.name')
                    ->label('Kantor')
                    ->default('Semua Kantor')
                    ->badge()
                    ->color(fn ($state) => $state === 'Semua Kantor' ? 'gray' : 'info')
                    ->sortable(),

                TextColumn::make('country_code')
                    ->label('Negara')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'ID' => 'danger',
                        'MY' => 'warning',
                        default => 'gray',
                    }),

                IconColumn::make('is_national_holiday')
                    ->label('Nasional')
                    ->boolean(),

                TextColumn::make('description')
                    ->label('Catatan')
                    ->limit(35)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('holiday_date', 'asc')
            ->filters([
                SelectFilter::make('office_id')
                    ->label('Kantor')
                    ->relationship('office', 'name'),

                SelectFilter::make('country_code')
                    ->label('Negara')
                    ->options([
                        'ID' => 'Indonesia (ID)',
                        'MY' => 'Malaysia (MY)',
                    ]),

                Tables\Filters\Filter::make('current_year')
                    ->label('Tahun Ini (' . $currentYear . ')')
                    ->query(fn (Builder $query) => $query->whereYear('holiday_date', $currentYear))
                    ->default(),
            ])
            ->headerActions([
                Action::make('sync_api')
                    ->label('Sinkronkan Hari Libur (API)')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->form([
                        Select::make('year')
                            ->label('Pilih Tahun')
                            ->options([
                                $currentYear - 1 => (string) ($currentYear - 1),
                                $currentYear => (string) $currentYear,
                                $currentYear + 1 => (string) ($currentYear + 1),
                            ])
                            ->default($currentYear)
                            ->required(),

                        Select::make('country_code')
                            ->label('Negara')
                            ->options([
                                'ID' => 'Indonesia (ID)',
                                'MY' => 'Malaysia (MY)',
                            ])
                            ->default('ID')
                            ->required(),

                        Select::make('office_id')
                            ->label('Hubungkan ke Kantor (Opsional)')
                            ->options(fn () => Office::pluck('name', 'id'))
                            ->placeholder('Semua Kantor / Otomatis'),
                    ])
                    ->action(function (array $data, HolidayService $service) {
                        $year = (int) $data['year'];
                        $country = (string) $data['country_code'];
                        $officeId = !empty($data['office_id']) ? (int) $data['office_id'] : null;

                        $result = $service->syncHolidaysFromApi($year, $country, $officeId);

                        if ($result['success']) {
                            Notification::make()
                                ->title('Sinkronisasi Berhasil')
                                ->body($result['message'])
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Gagal Menyinkronkan')
                                ->body($result['message'])
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHolidays::route('/'),
            'create' => Pages\CreateHoliday::route('/create'),
            'edit' => Pages\EditHoliday::route('/{record}/edit'),
        ];
    }
}
