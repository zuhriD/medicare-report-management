<?php

namespace App\Filament\Resources\GithubRepositoryResource\Pages;

use App\Filament\Resources\GithubRepositoryResource;
use App\Services\GitHubService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGithubRepository extends EditRecord
{
    protected static string $resource = GithubRepositoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['full_name'] = trim($data['owner']).'/'.trim($data['name']);

        if (app(GitHubService::class)->tokenConfigured()) {
            try {
                $meta = app(GitHubService::class)->fetchRepositoryMeta($data['full_name']);
                $data['default_branch'] = $meta['default_branch'] ?? null;
            } catch (\Throwable) {
                // Keep existing metadata if GitHub is unreachable.
            }
        }

        return $data;
    }
}
