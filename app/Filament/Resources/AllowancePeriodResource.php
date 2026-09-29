<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AllowancePeriodResource\Pages;
use App\Models\AllowancePeriod;
use App\Models\Office;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AllowancePeriodResource extends Resource
{
    protected static ?string $model = AllowancePeriod::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'HR & Attendance';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Allowance Periods';

    protected static ?string $modelLabel = 'Allowance Period';

    protected static ?string $pluralModelLabel = 'Allowance Periods';

    public static function canCreate(): bool
    {
        $user = auth()->user();
        return $user ? $user->hasRole(['hr', 'HR', 'admin', 'super_admin', 'Admin', 'Super Admin', 'lead', 'Lead']) : false;
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();
        return $user ? $user->hasRole(['hr', 'HR', 'admin', 'super_admin', 'Admin', 'Super Admin', 'lead', 'Lead']) : false;
    }

    public static function canDelete(Model $record): bool
    {
        /** @var AllowancePeriod $record */
        if ($record->periodStaff()->where('payment_status', 'paid')->exists()) {
            return false;
        }

        $user = auth()->user();
        return $user ? $user->hasRole(['hr', 'HR', 'admin', 'super_admin', 'Admin', 'Super Admin', 'lead', 'Lead']) : false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Periode Information')
                    ->description('Tentukan nama periode, rentang tanggal cut-off, dan kantor target.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Periode')
                            ->placeholder('Contoh: Periode 15 Juni - 16 Juli Kantor Pusat')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Select::make('office_id')
                            ->label('Filter Kantor (Opsional)')
                            ->relationship('office', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Semua Kantor / Multi Office')
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                $query = User::query()
                                    ->whereHas('roles', fn ($q) => $q->whereIn('name', ['team_member', 'Team Member', 'team-member', 'staff', 'Staff']));
                                if ($state) {
                                    $query->where('office_id', $state);
                                }
                                $set('selected_staff_ids', $query->pluck('id')->map(fn ($id) => (string) $id)->toArray());
                            }),

                        Select::make('status')
                            ->label('Status Periode')
                            ->options([
                                'draft' => 'Draft',
                                'active' => 'Active',
                                'completed' => 'Completed',
                            ])
                            ->default('active')
                            ->required(),

                        DatePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        DatePicker::make('end_date')
                            ->label('Tanggal Selesai')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->afterOrEqual('start_date'),

                        Textarea::make('notes')
                            ->label('Catatan HR')
                            ->placeholder('Catatan atau keterangan internal untuk periode pembayaran ini...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Pemilihan Staff')
                    ->description('Pilih staff yang akan dimasukkan ke dalam periode ini. Gunakan tombol "Select all" / "Deselect all" atau centang/uncentang secara bebas sesuai kebutuhan.')
                    ->schema([
                        \Filament\Forms\Components\CheckboxList::make('selected_staff_ids')
                            ->label('Daftar Staff Terpilih')
                            ->options(function (Get $get) {
                                $officeId = $get('office_id');
                                $query = User::query()
                                    ->whereHas('roles', function ($q) {
                                        $q->whereIn('name', ['team_member', 'Team Member', 'team-member', 'staff', 'Staff']);
                                    })
                                    ->with('office')
                                    ->orderBy('name');

                                if ($officeId) {
                                    $query->where('office_id', $officeId);
                                }

                                return $query->get()->mapWithKeys(function ($user) {
                                    $officeName = $user->office ? " [{$user->office->name}]" : " [Tanpa Kantor]";
                                    return [$user->id => "{$user->name}{$officeName} — {$user->email}"];
                                });
                            })
                            ->live()
                            ->searchable()
                            ->bulkToggleable()
                            ->columns(2)
                            ->gridDirection('row')
                            ->required()
                            ->helperText(function (Get $get) {
                                $selected = count((array) ($get('selected_staff_ids') ?? []));
                                return "Jumlah staff yang akan digenerate: {$selected} orang. Anda dapat mencentang atau meng-uncentang staff secara bebas.";
                            })
                            ->columnSpanFull(),
                    ])
                    ->hiddenOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Periode')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (AllowancePeriod $record): string => $record->start_date->format('d M Y') . ' — ' . $record->end_date->format('d M Y')),

                TextColumn::make('office.name')
                    ->label('Kantor')
                    ->searchable()
                    ->sortable()
                    ->default('Semua Kantor')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('staff_summary')
                    ->label('Staff (Lunas / Total)')
                    ->getStateUsing(function (AllowancePeriod $record) {
                        $paid = $record->paid_staff_count;
                        $total = $record->staff_count;
                        return "{$paid} / {$total} Staff";
                    })
                    ->badge()
                    ->color(fn (AllowancePeriod $record) => $record->unpaid_staff_count === 0 && $record->staff_count > 0 ? 'success' : 'warning'),

                TextColumn::make('total_amount')
                    ->label('Total Tagihan')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('total_paid_amount')
                    ->label('Terbayar')
                    ->money('IDR', locale: 'id')
                    ->color('success'),

                TextColumn::make('total_unpaid_amount')
                    ->label('Sisa Belum Bayar')
                    ->money('IDR', locale: 'id')
                    ->color('danger'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'active' => 'info',
                        'completed' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('office_id')
                    ->label('Filter Kantor')
                    ->relationship('office', 'name'),

                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Active',
                        'completed' => 'Completed',
                    ]),
            ])
            ->actions([
                Action::make('manage')
                    ->label('Manage / Detail')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('primary')
                    ->url(fn (AllowancePeriod $record): string => Pages\ManageAllowancePeriod::getUrl(['record' => $record])),

                ActionGroup::make([
                    EditAction::make(),

                    Action::make('export_pdf')
                        ->label('Export PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('danger')
                        ->url(fn (AllowancePeriod $record): string => route('allowance-periods.export', ['allowancePeriod' => $record, 'format' => 'pdf']))
                        ->openUrlInNewTab(),

                    Action::make('export_csv')
                        ->label('Export Excel / CSV')
                        ->icon('heroicon-o-table-cells')
                        ->color('success')
                        ->url(fn (AllowancePeriod $record): string => route('allowance-periods.export', ['allowancePeriod' => $record, 'format' => 'csv'])),

                    DeleteAction::make()
                        ->before(function (DeleteAction $action, AllowancePeriod $record) {
                            if ($record->periodStaff()->where('payment_status', 'paid')->exists()) {
                                Notification::make()
                                    ->title('Gagal Menghapus')
                                    ->body('Periode ini tidak dapat dihapus karena sudah ada staff yang tercatat lunas/dibayar.')
                                    ->danger()
                                    ->send();

                                $action->halt();
                            }
                        }),
                ]),
            ])
            ->bulkActions([
                // Bulk delete disabled for financial integrity
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAllowancePeriods::route('/'),
            'create' => Pages\CreateAllowancePeriod::route('/create'),
            'manage' => Pages\ManageAllowancePeriod::route('/{record}/manage'),
            'edit' => Pages\EditAllowancePeriod::route('/{record}/edit'),
        ];
    }
}
