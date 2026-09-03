<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanOfActionResource\Pages;
use App\Models\PlanOfAction;
use App\Models\SubModule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class PlanOfActionResource extends Resource
{
    protected static ?string $model = PlanOfAction::class;

    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Plan of Action';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')
                    ->default(Auth::id()),
                Forms\Components\Section::make('Plan of Action Information')
                    ->description('Provide the details of your plan of action.')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('POA Date')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                        Forms\Components\Select::make('module_id')
                            ->label('Main Task')
                            ->options(\App\Models\Module::pluck('name', 'id'))
                            ->live()
                            ->afterStateUpdated(fn(callable $set) => $set('sub_module_id', null))
                            ->afterStateHydrated(function (callable $set, $record) {
                                if ($record && $record->subModule) {
                                    $set('module_id', $record->subModule->module_id);
                                }
                            })
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('sub_module_id')
                            ->label('Sub Task / Platform')
                            ->options(function (callable $get) {
                                $module = \App\Models\Module::find($get('module_id'));
                                if (! $module) {
                                    return SubModule::all()->pluck('name', 'id');
                                }
                                return $module->subModules->pluck('name', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('Add new if the sub module platform does not exist under the chosen module.')
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Sub Module Name')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->createOptionUsing(function (array $data, callable $get) {
                                return \App\Models\SubModule::create([
                                    'name' => $data['name'],
                                    'module_id' => $get('module_id'),
                                ])->getKey();
                            }),
                        Forms\Components\Repeater::make('description')
                            ->label('Description Task')
                            ->simple(
                                Forms\Components\Textarea::make('description')
                                    ->required()
                                    ->rows(3)
                                    ->placeholder('Describe the task...')
                            )
                            ->defaultItems(1)
                            ->addActionLabel('Add Another Task')
                            ->reorderable(false)
                            ->columnSpanFull(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('subModule.name')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('info')
                    ->label('Sub Task / Platform'),
                Tables\Columns\TextColumn::make('start_date')
                    ->date('d/m/Y')
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-m-calendar')
                    ->label('POA Date'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('start_date')
                    ->form([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Start Date')
                            ->placeholder('dd/MM/yyyy')
                            ->displayFormat('dd/MM/yyyy'),
                        Forms\Components\DatePicker::make('until_date')
                            ->label('Until Date')
                            ->placeholder('dd/MM/yyyy')
                            ->displayFormat('dd/MM/yyyy'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_date'],
                                fn(Builder $query, $date): Builder => $query->whereDate('start_date', '>=', $date),
                            )
                            ->when(
                                $data['until_date'],
                                fn(Builder $query, $date): Builder => $query->whereDate('start_date', '<=', $date),
                            );
                    })
                    ->columns(2)
                    ->columnSpan(2),
                Tables\Filters\TrashedFilter::make(),
            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\ViewAction::make()->modal(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
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
            'index' => Pages\ListPlanOfActions::route('/'),
            'create' => Pages\CreatePlanOfAction::route('/create'),
            'edit' => Pages\EditPlanOfAction::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user && ($user->hasRole('super_admin') || $user->hasRole('admin'))) {
            return $query; // Admins can see all
        }

        // Team members see only their own
        return $query->where('user_id', $user?->id);
    }
}
