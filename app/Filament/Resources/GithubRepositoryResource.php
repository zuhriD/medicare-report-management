<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GithubRepositoryResource\Pages;
use App\Models\GithubRepository;
use App\Services\GitHubService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class GithubRepositoryResource extends Resource
{
    protected static ?string $model = GithubRepository::class;

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'GitHub Repositories';

    protected static ?int $navigationSort = 10;

    public static function canViewAny(): bool
    {
        return Auth::user()->hasRole(['super_admin', 'admin']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Repository')
                    ->description('Provide the owner and repository name. The token must have read access to it.')
                    ->schema([
                        Forms\Components\TextInput::make('owner')
                            ->required()
                            ->maxLength(255)
                            ->label('Owner / Organization')
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Repository Name')
                            ->columnSpan(1),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive repositories are skipped during sync.'),
                        Forms\Components\Textarea::make('description')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\Placeholder::make('last_synced_at')
                            ->label('Last Synced At')
                            ->content(fn (?GithubRepository $record) => $record?->last_synced_at?->format('d M Y H:i') ?? 'Never synced'),
                        Forms\Components\Placeholder::make('default_branch')
                            ->label('Default Branch')
                            ->content(fn (?GithubRepository $record) => $record?->default_branch ?? '—'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->label('Repository'),
                Tables\Columns\TextColumn::make('commits_count')
                    ->counts('commits')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->label('Commits'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                Tables\Columns\TextColumn::make('last_synced_at')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->placeholder('Never')
                    ->label('Last Synced At'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\Action::make('sync_now')
                    ->label('Sync Now')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Sync GitHub commits')
                    ->modalDescription(fn (GithubRepository $record) => "Pull latest commits from {$record->full_name}?")
                    ->action(function (GithubRepository $record, GitHubService $service): void {
                        try {
                            $result = $service->syncRepository($record);

                            Notification::make()
                                ->title('Sync completed')
                                ->body("{$result['new']} new, {$result['updated']} updated commit(s).")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Sync failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No repositories configured')
            ->emptyStateDescription('Add your first GitHub repository to start syncing commits.');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGithubRepositories::route('/'),
            'create' => Pages\CreateGithubRepository::route('/create'),
            'edit' => Pages\EditGithubRepository::route('/{record}/edit'),
        ];
    }
}
