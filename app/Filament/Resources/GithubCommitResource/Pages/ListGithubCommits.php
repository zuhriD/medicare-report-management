<?php

namespace App\Filament\Resources\GithubCommitResource\Pages;

use App\Filament\Resources\GithubCommitResource;
use Filament\Resources\Pages\ListRecords;

class ListGithubCommits extends ListRecords
{
    protected static string $resource = GithubCommitResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
