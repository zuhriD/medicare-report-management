<?php

namespace App\Filament\Resources\GithubRepositoryResource\Pages;

use App\Filament\Resources\GithubRepositoryResource;
use App\Services\GitHubService;
use Filament\Resources\Pages\CreateRecord;

class CreateGithubRepository extends CreateRecord
{
    protected static string $resource = GithubRepositoryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['full_name'] = trim($data['owner']).'/'.trim($data['name']);

        if (app(GitHubService::class)->tokenConfigured()) {
            try {
                $meta = app(GitHubService::class)->fetchRepositoryMeta($data['full_name']);
                $data['description'] = $data['description'] ?? ($meta['description'] ?? null);
                $data['default_branch'] = $meta['default_branch'] ?? null;
            } catch (\Throwable) {
                // Token/network issue: leave metadata empty, sync will surface errors.
            }
        }

        return $data;
    }
}
