<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WeeklyReportResource\Pages;
use App\Models\WeeklyReport;
use App\Models\SubModule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class WeeklyReportResource extends Resource
{
    protected static ?string $model = WeeklyReport::class;

    protected static ?string $navigationGroup = 'Reports';

    public static function canViewAny(): bool
    {
        return Auth::user()->hasRole(['super_admin', 'admin']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Report Period')
                    ->description('Set the week number and the date range for this report.')
                    ->schema([
                        Forms\Components\TextInput::make('week_number')
                            ->required()
                            ->maxLength(255)
                            ->label("Week Number"),
                        Forms\Components\DatePicker::make('start_date')
                            ->required()
                            ->displayFormat('d/m/Y')
                            ->placeholder('dd/mm/yyyy')
                            ->native(false)
                            ->label("Start Date"),
                        Forms\Components\DatePicker::make('end_date')
                            ->required()
                            ->displayFormat('d/m/Y')
                            ->placeholder('dd/mm/yyyy')
                            ->native(false)
                            ->label("End Date"),
                    ])->columns(3),

                Forms\Components\Section::make('Report Details')
                    ->description('Provide the executive summary and plan of action.')
                    ->schema([
                        Forms\Components\RichEditor::make('executive_summary')
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
                            ])
                            ->label("Executive Summary"),
                        Forms\Components\RichEditor::make('plan_of_action')
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
                            ])
                            ->label("Plan of Action"),
                    ]),

                Forms\Components\Section::make('Sub Module Progress')
                    ->description('Track the progress for each sub-module.')
                    ->schema([
                        Forms\Components\Repeater::make('weeklyReportProgresses')
                            ->relationship('weeklyReportProgresses')
                            ->hiddenLabel()
                            ->schema([
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
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Forms\Components\TextInput::make('progress_percentage')
                                    ->label('Progress')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%')
                                    ->required(),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel('Add Progress')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->cloneable()
                            ->columns(3),
                    ]),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('week_number')
                    ->searchable()
                    ->label("Week")
                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                    ->icon('heroicon-m-calendar-days'),
                Tables\Columns\TextColumn::make('start_date')
                    ->date('M d, Y')
                    ->sortable()
                    ->label("Start Date")
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('end_date')
                    ->date('M d, Y')
                    ->sortable()
                    ->label("End Date")
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('view_report')
                    ->label('View Report')
                    ->icon('heroicon-o-eye')
                    ->url(fn(WeeklyReport $record): string => route('weekly-reports.show', $record))
                    ->openUrlInNewTab(),
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
            ->emptyStateHeading('No weekly reports yet')
            ->emptyStateDescription('Start by creating your first weekly report.')
            ->emptyStateIcon('heroicon-o-document-chart-bar');
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWeeklyReports::route('/'),
            'create' => Pages\CreateWeeklyReport::route('/create'),
            'edit' => Pages\EditWeeklyReport::route('/{record}/edit'),
        ];
    }
}
