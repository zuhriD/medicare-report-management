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
                            ->default(session('last_report_date', now()))
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->live(),
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
                            ->dehydrated(false)
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
                        Forms\Components\Placeholder::make('poa_today')
                            ->label('Your Plan of Action Today')
                            ->columnSpanFull()
                            ->content(function (Forms\Get $get, $record) {
                                $rawDate = $get('report_date') ?? $record?->report_date ?? now()->toDateString();

                                try {
                                    if (is_string($rawDate) && preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $rawDate)) {
                                        $dateObj = \Illuminate\Support\Carbon::createFromFormat('d/m/Y', $rawDate);
                                    } else {
                                        $dateObj = \Illuminate\Support\Carbon::parse($rawDate);
                                    }
                                } catch (\Throwable $e) {
                                    $dateObj = now();
                                }

                                $dbDate = $dateObj->format('Y-m-d');
                                $userId = Auth::id() ?? $record?->user_id;

                                if (!$userId) {
                                    return new \Illuminate\Support\HtmlString('<p class="text-sm text-gray-500 italic dark:text-gray-400">No Plan of Action for today.</p>');
                                }

                                $poas = \App\Models\PlanOfAction::with(['module', 'subModule'])
                                    ->where('user_id', $userId)
                                    ->whereDate('start_date', $dbDate)
                                    ->get();

                                if ($poas->isEmpty()) {
                                    return new \Illuminate\Support\HtmlString('<p class="text-sm text-gray-500 italic dark:text-gray-400">No Plan of Action for today.</p>');
                                }

                                $html = '<div class="space-y-3 p-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg">';
                                foreach ($poas as $poa) {
                                    $moduleName = $poa->module?->name;
                                    $subName = $poa->subModule?->name;
                                    $label = ($moduleName && $subName) ? "{$moduleName} | {$subName}" : ($subName ?? $moduleName ?? '');

                                    if ($label) {
                                        $html .= '<div class="font-semibold text-xs text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">' . e($label) . '</div>';
                                    }

                                    $tasks = is_array($poa->description)
                                        ? $poa->description
                                        : array_filter(array_map('trim', explode('-', strip_tags($poa->description ?? ''))));

                                    $html .= '<ul class="space-y-1 list-none pl-1 text-sm text-gray-700 dark:text-white">';
                                    foreach ($tasks as $task) {
                                        $cleanTask = trim(strip_tags($task));
                                        if ($cleanTask) {
                                            $html .= '<li class="flex items-start gap-2"><span class="text-indigo-500 font-bold">•</span><span>' . e($cleanTask) . '</span></li>';
                                        }
                                    }
                                    $html .= '</ul>';
                                }
                                $html .= '</div>';

                                return new \Illuminate\Support\HtmlString($html);
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

    public static function infolist(\Filament\Infolists\Infolist $infolist): \Filament\Infolists\Infolist
    {
        return $infolist
            ->schema([
                \Filament\Infolists\Components\Section::make('Report Information')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('report_date')
                            ->label('Report Date')
                            ->date('d/m/Y'),
                        \Filament\Infolists\Components\TextEntry::make('subModule.module.name')
                            ->label('Main Task'),
                        \Filament\Infolists\Components\TextEntry::make('subModule.name')
                            ->label('Sub Task / Platform'),
                        \Filament\Infolists\Components\TextEntry::make('description')
                            ->label('Description Task')
                            ->formatStateUsing(function ($state) {
                                if (empty($state)) return '-';
                                $tasks = is_array($state) ? $state : [$state];
                                $html = '<ul class="list-disc pl-5 space-y-1">';
                                foreach ($tasks as $task) {
                                    $html .= '<li>' . e($task) . '</li>';
                                }
                                $html .= '</ul>';
                                return new \Illuminate\Support\HtmlString($html);
                            })
                            ->columnSpanFull(),
                    ])->columns(3),
            ]);
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
                Tables\Actions\ViewAction::make()->modal(),
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

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user && ($user->hasRole('super_admin') || $user->hasRole('admin'))) {
            return $query; // Admins can see all
        }

        if ($user && $user->hasRole('lead') && $user->sections()->exists()) {
            // Section lead can see reports of users in the same section
            $sectionIds = $user->sections->pluck('id')->toArray();
            return $query->whereHas('user.sections', function ($q) use ($sectionIds) {
                $q->whereIn('sections.id', $sectionIds);
            });
        }

        // Team members see only their own
        return $query->where('user_id', $user?->id);
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
