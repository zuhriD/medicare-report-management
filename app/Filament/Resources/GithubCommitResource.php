<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GithubCommitResource\Pages;
use App\Models\GithubCommit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class GithubCommitResource extends Resource
{
    protected static ?string $model = GithubCommit::class;

    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'GitHub Commits';

    protected static ?string $slug = 'github-commits';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        $user = Auth::user();

        return $user->hasRole(['super_admin', 'admin', 'lead']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Commit')
                    ->schema([
                        Forms\Components\TextInput::make('short_sha')
                            ->label('SHA'),
                        Forms\Components\TextInput::make('repository.full_name')
                            ->label('Repository'),
                        Forms\Components\TextInput::make('user.name')
                            ->label('Developer'),
                        Forms\Components\TextInput::make('author_name')
                            ->label('Author'),
                        Forms\Components\TextInput::make('author_email')
                            ->label('Author Email'),
                        Forms\Components\DateTimePicker::make('committed_at')
                            ->label('Committed At'),
                        Forms\Components\Textarea::make('message')
                            ->label('Message')
                            ->rows(8)
                            ->columnSpanFull(),
                        Forms\Components\ViewField::make('url')
                            ->label('')
                            ->view('filament.forms.view.github-commit-link')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('repository.full_name')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->label('Repository'),
                Tables\Columns\TextColumn::make('short_sha')
                    ->searchable()
                    ->label('SHA')
                    ->weight(FontWeight::Bold)
                    ->copyable(),
                Tables\Columns\TextColumn::make('message')
                    ->searchable()
                    ->limit(60)
                    ->wrap()
                    ->tooltip(fn (GithubCommit $record): ?string => $record->message),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Developer')
                    ->placeholder('—')
                    ->badge()
                    ->color('success')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('author_name')
                    ->label('Author')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('committed_at')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->label('Committed At')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('committed_at', $direction);
                    }),
                Tables\Columns\TextColumn::make('additions')
                    ->numeric()
                    ->sortable()
                    ->color('success')
                    ->label('+'),
                Tables\Columns\TextColumn::make('deletions')
                    ->numeric()
                    ->sortable()
                    ->color('danger')
                    ->label('-'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('committed_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('repository_id')
                    ->label('Repository')
                    ->relationship('repository', 'full_name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Developer')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('committed_at')
                    ->label('Committed Between')
                    ->form([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Start Date')
                            ->displayFormat('dd/mm/yyyy'),
                        Forms\Components\DatePicker::make('until_date')
                            ->label('Until Date')
                            ->displayFormat('dd/mm/yyyy'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('committed_at', '>=', $date),
                            )
                            ->when(
                                $data['until_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('committed_at', '<=', $date),
                            );
                    })
                    ->columns(2)
                    ->columnSpan(2),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\Action::make('view_on_github')
                    ->label('View on GitHub')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (GithubCommit $record): string => $record->url)
                    ->openUrlInNewTab(),
                Tables\Actions\ViewAction::make(),
            ])
            ->emptyStateHeading('No commits synced yet')
            ->emptyStateDescription('Add a repository and run the sync to pull commits from GitHub.');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['repository', 'user']);

        $user = Auth::user();

        if ($user->hasRole('lead')) {
            $sectionIds = $user->sections->pluck('id')->toArray();

            return $query->whereHas('user.sections', function ($q) use ($sectionIds) {
                $q->whereIn('sections.id', $sectionIds);
            });
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGithubCommits::route('/'),
            'view' => Pages\ViewGithubCommit::route('/{record}'),
        ];
    }
}
