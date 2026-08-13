<?php

namespace App\Console\Commands;

use App\Models\GithubRepository;
use App\Services\GitHubService;
use Illuminate\Console\Command;
use RuntimeException;

class SyncGithubCommits extends Command
{
    protected $signature = 'github:sync
        {--repo= : Sync only a specific repository (owner/name). Repeatable.}
        {--since= : Only fetch commits after this ISO 8601 date/time.}
        {--until= : Only fetch commits before this ISO 8601 date/time.}
        {--with-stats : Also fetch per-commit stats (additions/deletions/files). Slower.}';

    protected $description = 'Sync GitHub commits for all active repositories into the database.';

    public function handle(GitHubService $service): int
    {
        if (! $service->tokenConfigured()) {
            $this->error('GITHUB_TOKEN is not configured. Set it in your .env file.');

            return self::FAILURE;
        }

        $query = GithubRepository::query()->where('is_active', true);

        $repoFilters = $this->option('repo');

        if ($repoFilters) {
            $repoFilters = is_array($repoFilters) ? $repoFilters : [$repoFilters];
            $query->whereIn('full_name', $repoFilters);
        }

        $repositories = $query->get();

        if ($repositories->isEmpty()) {
            $this->info('No active GitHub repositories to sync.');

            return self::SUCCESS;
        }

        $since = $this->option('since');
        $until = $this->option('until');
        $withStats = (bool) $this->option('with-stats');

        $this->info("Syncing {$repositories->count()} repositories...");

        foreach ($repositories as $repository) {
            $this->line("  -> {$repository->full_name}");

            try {
                $result = $service->syncRepository($repository, $since, $until, $withStats);
                $this->info("     {$result['new']} new, {$result['updated']} updated commit(s).");
            } catch (RuntimeException $e) {
                $this->error("     {$e->getMessage()}");

                return self::FAILURE;
            }
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
