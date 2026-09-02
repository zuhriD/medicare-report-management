<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DailyReportResource\Pages;
use App\Models\DailyReport;
use App\Models\GithubCommit;
use App\Models\GithubRepository;
use App\Models\Module;
use App\Models\SubModule;
use App\Services\OllamaService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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
                            ->displayFormat('d/m/Y'),
                        Forms\Components\Select::make('module_id')
                            ->label('Main Task')
                            ->options(Module::pluck('name', 'id'))
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('sub_module_id', null))
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
                                $module = Module::find($get('module_id'));
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
                                return SubModule::create([
                                    'name' => $data['name'],
                                    'module_id' => $get('module_id'),
                                ])->getKey();
                            }),
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

                Forms\Components\Section::make('Import GitHub Commits')
                    ->description('Select your commits by repository and add them to the description.')
                    ->schema([
                        Forms\Components\Select::make('commit_repository_id')
                            ->label('Repository')
                            ->options(function (): array {
                                $repoIds = GithubCommit::query()
                                    ->where('user_id', Auth::id())
                                    ->distinct()
                                    ->pluck('repository_id');

                                return GithubRepository::query()
                                    ->whereIn('id', $repoIds)
                                    ->orderBy('full_name')
                                    ->pluck('full_name', 'id')
                                    ->all();
                            })
                            ->live()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('commit_ids', []);
                                $set('commit_page', 1);
                            })
                            ->searchable()
                            ->preload()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                        Forms\Components\Hidden::make('commit_page')
                            ->default(1)
                            ->live()
                            ->dehydrated(false),
                        Forms\Components\CheckboxList::make('commit_ids')
                            ->label('Commits')
                            ->live()
                            ->dehydrated(false)
                            ->searchable()
                            ->bulkToggleable()
                            ->options(function (Get $get): array {
                                $repositoryId = $get('commit_repository_id');

                                if (blank($repositoryId)) {
                                    return [];
                                }

                                $commits = GithubCommit::query()
                                    ->where('user_id', Auth::id())
                                    ->where('repository_id', $repositoryId)
                                    ->orderByDesc('committed_at')
                                    ->take(500)
                                    ->get()
                                    ->mapWithKeys(fn (GithubCommit $commit): array => [
                                        $commit->id => sprintf(
                                            '[%s] %s — %s',
                                            $commit->committed_at?->format('d M Y H:i') ?? '—',
                                            $commit->short_sha,
                                            self::firstLine($commit->message),
                                        ),
                                    ]);

                                return $commits
                                    ->forPage(max(1, (int) $get('commit_page')), 10)
                                    ->all();
                            })
                            ->helperText('Pick the commits you want to add to the description.')
                            ->columnSpanFull(),
                        Forms\Components\Placeholder::make('commit_page_info')
                            ->label('')
                            ->content(function (Get $get): string {
                                $repositoryId = $get('commit_repository_id');

                                if (blank($repositoryId)) {
                                    return 'Select a repository above to browse your commits.';
                                }

                                $total = GithubCommit::query()
                                    ->where('user_id', Auth::id())
                                    ->where('repository_id', $repositoryId)
                                    ->count();

                                return sprintf(
                                    'Page %s of %s · %s commit(s) available',
                                    max(1, (int) $get('commit_page')),
                                    max(1, (int) ceil($total / 10)),
                                    number_format($total),
                                );
                            })
                            ->columnSpanFull()
                            ->dehydrated(false),
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('prev_commit_page')
                                ->label('Previous')
                                ->icon('heroicon-o-chevron-left')
                                ->outlined()
                                ->iconButton()
                                ->action(fn (Get $get, Set $set) => $set(
                                    'commit_page',
                                    max(1, (int) $get('commit_page') - 1),
                                ))
                                ->visible(fn (Get $get): bool => (int) ($get('commit_page') ?? 1) > 1),
                            Forms\Components\Actions\Action::make('next_commit_page')
                                ->label('Next')
                                ->icon('heroicon-o-chevron-right')
                                ->outlined()
                                ->iconButton()
                                ->action(function (Get $get, Set $set): void {
                                    $total = blank($get('commit_repository_id'))
                                        ? 0
                                        : GithubCommit::query()
                                            ->where('user_id', Auth::id())
                                            ->where('repository_id', $get('commit_repository_id'))
                                            ->count();

                                    $totalPages = max(1, (int) ceil($total / 10));

                                    $set('commit_page', min($totalPages, (int) $get('commit_page') + 1));
                                })
                                ->visible(fn (Get $get): bool => filled($get('commit_repository_id'))),
                            Forms\Components\Actions\Action::make('add_selected_commits')
                                ->label('Add Selected Commits to Description')
                                ->icon('heroicon-o-plus-circle')
                                ->hiddenLabel(false)
                                ->action(function (Get $get, Set $set): void {
                                    $ids = (array) $get('commit_ids');

                                    if (empty($ids)) {
                                        Notification::make()
                                            ->title('No commits selected')
                                            ->body('Check at least one commit first.')
                                            ->warning()
                                            ->send();

                                        return;
                                    }

                                    $commits = GithubCommit::query()
                                        ->with('repository')
                                        ->where('user_id', Auth::id())
                                        ->whereIn('id', $ids)
                                        ->orderByDesc('committed_at')
                                        ->get();

                                    $content = $commits
                                        ->map(fn (GithubCommit $commit): string => sprintf(
                                            '<ul><li><strong>[%s] %s @ %s</strong><br>%s</li></ul>',
                                            e($commit->repository?->full_name ?? ''),
                                            e($commit->short_sha),
                                            $commit->committed_at?->format('d M Y H:i'),
                                            nl2br(e($commit->message)),
                                        ))
                                        ->implode('');

                                    $current = (string) $get('description');
                                    $set('description', $current.$content);

                                    $set('commit_ids', []);

                                    Notification::make()
                                        ->title("Added {$commits->count()} commit(s)")
                                        ->success()
                                        ->send();
                                }),
                            Forms\Components\Actions\Action::make('ai_summary')
                                ->label('AI Summary')
                                ->icon('heroicon-o-sparkles')
                                ->hiddenLabel(false)
                                ->color('info')
                                ->modalHeading('AI Summary')
                                ->modalDescription('Generate a summary of the selected commits and append it to the description.')
                                ->modalSubmitActionLabel('Generate & Add to Description')
                                ->form([
                                    Forms\Components\Textarea::make('prompt')
                                        ->label('Prompt / Instructions')
                                        ->rows(4)
                                        ->default('Summarize the changes from these commits for a daily report. Write in clear bullet points using markdown.')
                                        ->helperText('Customize how the AI should summarize your selected commits.'),
                                ])
                                ->action(function (Get $get, Set $set, array $data): void {
                                    $ids = (array) $get('commit_ids');

                                    if (empty($ids)) {
                                        Notification::make()
                                            ->title('No commits selected')
                                            ->body('Check at least one commit first, then run AI Summary.')
                                            ->warning()
                                            ->send();

                                        return;
                                    }

                                    $commits = GithubCommit::query()
                                        ->with('repository')
                                        ->where('user_id', Auth::id())
                                        ->whereIn('id', $ids)
                                        ->orderByDesc('committed_at')
                                        ->get();

                                    $context = $commits
                                        ->map(fn (GithubCommit $commit): string => sprintf(
                                            '- [%s] %s: %s',
                                            $commit->repository?->full_name ?? 'unknown repo',
                                            $commit->short_sha,
                                            str($commit->message)->before("\n")->toString(),
                                        ))
                                        ->implode("\n");

                                    $system = 'You are an assistant that writes concise daily report summaries based on git commits.';

                                    $prompt = trim((string) ($data['prompt'] ?? ''));

                                    if ($prompt === '') {
                                        $prompt = 'Summarize the changes from these commits for a daily report. Write in clear bullet points using markdown.';
                                    }

                                    $prompt .= "\n\nCommits:\n".$context;

                                    try {
                                        $summary = app(OllamaService::class)->chat($prompt, $system);

                                        $content = sprintf(
                                            '<p><strong>AI Summary</strong></p><p>%s</p>',
                                            nl2br(e($summary)),
                                        );

                                        $current = (string) $get('description');
                                        $set('description', $current.$content);

                                        Notification::make()
                                            ->title('AI summary added to description')
                                            ->success()
                                            ->send();
                                    } catch (\Throwable $e) {
                                        Notification::make()
                                            ->title('AI summary failed')
                                            ->body($e->getMessage())
                                            ->danger()
                                            ->send();
                                    }
                                }),
                        ]),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),

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
                            ->itemLabel(fn (array $state): ?string => $state['caption'] ?? null)
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
                    ->label('Member')
                    ->searchable()
                    ->weight(FontWeight::Bold),
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
                                fn (Builder $query, $date): Builder => $query->whereDate('report_date', '>=', $date),
                            )
                            ->when(
                                $data['until_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('report_date', '<=', $date),
                            );
                    })
                    ->columns(2)
                    ->columnSpan(2),
                Tables\Filters\TrashedFilter::make(),
            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\Action::make('print')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (DailyReport $record): string => route('daily-reports.print', ['date' => $record->report_date->format('Y-m-d')]))
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

    protected static function firstLine(?string $message): string
    {
        if (blank($message)) {
            return '—';
        }

        return Str::of($message)
            ->before("\n")
            ->before("\r")
            ->limit(100)
            ->toString();
    }
}
