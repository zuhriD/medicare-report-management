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
                Forms\Components\Hidden::make('user_id')
                    ->default(Auth::id()),
                Forms\Components\DatePicker::make('report_date')
                    ->required()
                    ->default(now()),
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
                    ->dehydrated(false),
                Forms\Components\Select::make('sub_module_id')
                    ->label('Sub Module')
                    ->options(function (callable $get) {
                        $module = \App\Models\Module::find($get('module_id'));
                        if (! $module) {
                            return SubModule::all()->pluck('name', 'id');
                        }
                        return $module->subModules->pluck('name', 'id');
                    })
                    ->required(),
                Forms\Components\RichEditor::make('description')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Repeater::make('reportImages')
                    ->relationship('reportImages')
                    ->schema([
                        Forms\Components\FileUpload::make('image_path')
                            ->image()
                            ->required(),
                        Forms\Components\TextInput::make('caption')
                            ->maxLength(255),
                    ])
                    ->columnSpanFull()
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->sortable()
                    ->label("Member")
                    ->searchable(),
                Tables\Columns\TextColumn::make('subModule.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('report_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('report_date')
                    ->form([
                        Forms\Components\DatePicker::make('date')
                            ->label('Report Date')
                            ->default(now()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['date'],
                            fn(Builder $query, $date): Builder => $query->whereDate('report_date', $date),
                        );
                    }),
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
            ]);
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
