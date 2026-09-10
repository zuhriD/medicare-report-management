<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MemberWeeklyReportResource\Pages;
use App\Models\WeeklyReport;
use App\Models\SubModule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class MemberWeeklyReportResource extends Resource
{
    protected static ?string $model = WeeklyReport::class;

    protected static ?string $navigationLabel = 'My Weekly Reports';
    protected static ?string $slug = 'my-weekly-reports';

    protected static ?string $navigationGroup = 'Reports';

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return true;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Report Details')
                    ->schema([
                        Forms\Components\TextInput::make('week_number')
                            ->disabled(),
                        Forms\Components\DatePicker::make('start_date')
                            ->disabled(),
                        Forms\Components\DatePicker::make('end_date')
                            ->disabled(),
                    ])->columns(3),

                Forms\Components\Section::make('My Compiled Report')
                    ->description('Your daily reports for this week.')
                    ->schema([
                        Forms\Components\Placeholder::make('my_compiled_report')
                            ->hiddenLabel()
                            ->content(function ($record) {
                                if (!$record) return '';

                                $reports = \App\Models\DailyReport::with('subModule.module')
                                    ->where('user_id', Auth::id())
                                    ->whereBetween('report_date', [$record->start_date, $record->end_date])
                                    ->orderBy('report_date')
                                    ->get();

                                if ($reports->isEmpty()) {
                                    return new HtmlString('<p class="text-gray-500">No daily reports found for this week.</p>');
                                }

                                $html = '<div class="divide-y divide-gray-200 bg-gray-50 rounded-lg border border-gray-200">';
                                foreach ($reports as $report) {
                                    $subModule = $report->subModule;
                                    $subModuleName = $subModule ? $subModule->name : 'General';
                                    $date = $report->report_date->format('M d, Y');

                                    $html .= "<div class=\"p-4\">
                                        <div class=\"font-bold text-gray-800 mb-1\">{$date}</div>
                                        <div class=\"text-indigo-600 text-sm font-semibold mb-2\">{$subModuleName}</div>
                                        <div class=\"prose prose-sm max-w-none [&>*:last-child]:mb-0\">";

                                    $tasks = is_array($report->description) ? $report->description : [$report->description];
                                    foreach ($tasks as $task) {
                                        $escapedTask = e($task);
                                        $html .= "<div class=\"flex items-start gap-2 mb-1\">
                                            <span class=\"text-indigo-500 mt-1\">•</span>
                                            <span>{$escapedTask}</span>
                                        </div>";
                                    }

                                    $html .= "</div></div>";
                                }
                                $html .= '</div>';

                                return new HtmlString($html);
                            }),
                    ]),

                Forms\Components\Section::make('Assign Progress')
                    ->description('Update the progress for the sub-modules / platforms you worked on.')
                    ->schema([
                        Forms\Components\Repeater::make('weeklyReportProgresses')
                            ->relationship('weeklyReportProgresses')
                            ->schema([
                                Forms\Components\Select::make('module_id')
                                    ->label('Module')
                                    ->options(function (callable $get) {
                                        $startDate = $get('../../start_date');
                                        $endDate = $get('../../end_date');
                                        if (!$startDate || !$endDate) return [];

                                        $workedSubModuleIds = \App\Models\DailyReport::where('user_id', Auth::id())
                                            ->whereBetween('report_date', [$startDate, $endDate])
                                            ->pluck('sub_module_id');

                                        $workedModuleIds = \App\Models\SubModule::whereIn('id', $workedSubModuleIds)
                                            ->pluck('module_id');

                                        return \App\Models\Module::whereIn('id', $workedModuleIds)->pluck('name', 'id');
                                    })
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
                                        $startDate = $get('../../start_date');
                                        $endDate = $get('../../end_date');
                                        if (!$startDate || !$endDate) return [];

                                        $workedSubModuleIds = \App\Models\DailyReport::where('user_id', Auth::id())
                                            ->whereBetween('report_date', [$startDate, $endDate])
                                            ->pluck('sub_module_id');

                                        $moduleId = $get('module_id');
                                        $query = \App\Models\SubModule::whereIn('id', $workedSubModuleIds);

                                        if ($moduleId) {
                                            $query->where('module_id', $moduleId);
                                        }

                                        return $query->pluck('name', 'id');
                                    })
                                    ->required()
                                    ->searchable(),
                                Forms\Components\TextInput::make('progress_percentage')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%')
                                    ->required(),
                            ])
                            ->columnSpanFull()
                            ->columns(3),
                    ]),
            ]);
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
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('View & Assign Progress'),
            ])
            ->bulkActions([
                // 
            ])
            ->emptyStateHeading('No weekly reports yet')
            ->emptyStateDescription('You do not have any weekly reports assigned.')
            ->emptyStateIcon('heroicon-o-document-chart-bar');
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
            'index' => Pages\ListMemberWeeklyReports::route('/'),
            'edit' => Pages\EditMemberWeeklyReport::route('/{record}/edit'),
        ];
    }
}
