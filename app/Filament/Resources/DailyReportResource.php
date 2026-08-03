<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DailyReportResource\Pages;
use App\Models\DailyReport;
use App\Models\SubModule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class DailyReportResource extends Resource
{
    protected static ?string $model = DailyReport::class;

    protected static ?string $navigationGroup = 'Reports';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Report Information')
                    ->description('Provide the details of your daily report.')
                    ->schema([
                        Forms\Components\Hidden::make('user_id')
                            ->default(Auth::id()),
                        Forms\Components\DatePicker::make('report_date')
                            ->label('Report Date')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                        Forms\Components\Select::make('module_id')
                            ->label('Module')
                            ->options(\App\Models\Module::pluck('name', 'id'))
                            ->live()
                            ->afterStateUpdated(fn(callable $set) => $set('sub_module_id', null))
                            ->afterStateHydrated(function (callable $set, $record) {
                                if ($record && $record->subModule) {
                                    $set('module_id', $record->subModule->module_id);
                                }
                            })
                            ->dehydrated(false)
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('sub_module_id')
                            ->label('Sub Module / Platform')
                            ->options(function (callable $get) {
                                $module = \App\Models\Module::find($get('module_id'));
                                if (! $module) {
                                    return SubModule::all()->pluck('name', 'id');
                                }
                                return $module->subModules->pluck('name', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\RichEditor::make('description')
                            ->required()
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'blockquote',
                                'bold',
                                'bulletList',
                                'codeBlock',
                                'h2',
                                'h3',
                                'italic',
                                'link',
                                'orderedList',
                                'redo',
                                'strike',
                                'underline',
                                'undo',
                            ]),
                    ])->columns(3),

                Forms\Components\Section::make('Attachments')
                    ->description('Upload any relevant images.')
                    ->schema([
                        Forms\Components\Repeater::make('reportImages')
                            ->relationship('reportImages')
                            ->hiddenLabel()
                            ->schema([
                                Forms\Components\FileUpload::make('image_path')
                                    ->image()
                                    ->hiddenLabel()
                                    ->required()
                                    ->imageEditor(),
                                Forms\Components\TextInput::make('caption')
                                    ->hiddenLabel()
                                    ->placeholder('Add a caption...')
                                    ->maxLength(255),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel('Add Image')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->cloneable()
                            ->itemLabel(fn(array $state): ?string => $state['caption'] ?? null)
                            ->columns(2),
                    ]),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->sortable()
                    ->label("Member")
                    ->searchable()
                    ->weight(\Filament\Support\Enums\FontWeight::Bold),
                Tables\Columns\TextColumn::make('subModule.name')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('info')
                    ->label('Sub Module'),
                Tables\Columns\TextColumn::make('report_date')
                    ->date('M d, Y')
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-m-calendar'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('report_date')
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
                                fn(Builder $query, $date): Builder => $query->whereDate('report_date', '>=', $date),
                            )
                            ->when(
                                $data['until_date'],
                                fn(Builder $query, $date): Builder => $query->whereDate('report_date', '<=', $date),
                            );
                    })
                    ->columns(2)
                    ->columnSpan(2),
                Tables\Filters\TrashedFilter::make(),
            ], layout: Tables\Enums\FiltersLayout::AboveContent)
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
            ])
            ->emptyStateHeading('No daily reports yet')
            ->emptyStateDescription('Start by creating your first daily report.')
            ->emptyStateIcon('heroicon-o-document-text');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        $user = Auth::user();
        if ($user->hasRole('super_admin') || $user->hasRole('admin')) {
            return $query; // Admins can see all
        }

        if ($user->hasRole('lead') && $user->section_id) {
            // Section lead can see reports of users in the same section
            return $query->whereHas('user', function ($q) use ($user) {
                $q->where('section_id', $user->section_id);
            });
        }

        // Team members see only their own
        return $query->where('user_id', $user->id);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDailyReports::route('/'),
            'create' => Pages\CreateDailyReport::route('/create'),
            'edit' => Pages\EditDailyReport::route('/{record}/edit'),
        ];
    }
}
