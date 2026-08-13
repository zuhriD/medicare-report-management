<?php

namespace App\Services;

use App\Models\GithubCommit;
use App\Models\GithubRepository;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class GitHubService
{
    protected string $token;

    protected string $apiUrl;

    protected int $perPage;

    public function __construct()
    {
        $this->token = (string) config('services.github.token');
        $this->apiUrl = rtrim((string) config('services.github.api_url'), '/');
        $this->perPage = (int) config('services.github.per_page', 100);
    }

    public function tokenConfigured(): bool
    {
        return $this->token !== '';
    }

    /**
     * Sync commits for a repository into the database.
     *
     * @return array{new: int, updated: int}
     */
    public function syncRepository(
        GithubRepository $repository,
        ?string $since = null,
        ?string $until = null,
        bool $withStats = false,
    ): array {
        if (! $this->tokenConfigured()) {
            throw new RuntimeException('GITHUB_TOKEN is not configured. Set it in your .env file.');
        }

        set_time_limit(0);

        // Incremental sync: if no explicit "since" is given, only pull commits
        // after the last successful sync (minus a safety buffer).
        if ($since === null && $repository->last_synced_at !== null) {
            $since = $repository->last_synced_at->copy()->subDay()->toIso8601String();
        }

        $existingShas = GithubCommit::query()
            ->where('repository_id', $repository->id)
            ->pluck('sha')
            ->flip()
            ->all();

        $userMap = $this->buildUserMap();

        $commits = $this->fetchCommits($repository->full_name, $since, $until, $existingShas);

        $newPayloads = [];
        $updatePayloads = [];

        foreach ($commits as $commitData) {
            $sha = (string) ($commitData['sha'] ?? '');

            if ($sha === '') {
                continue;
            }

            $payload = $this->buildCommitPayload($repository, $commitData, $userMap);

            if ($withStats) {
                $payload = array_merge(
                    $payload,
                    $this->extractStats($this->fetchCommitDetail($repository->full_name, $sha)),
                );
            }

            if (isset($existingShas[$sha])) {
                $updatePayloads[] = $payload;
            } else {
                $newPayloads[] = $payload;
            }
        }

        $this->insertNewCommits($newPayloads);
        $this->updateExistingCommits($updatePayloads);

        $repository->update(['last_synced_at' => now()]);

        return [
            'new' => count($newPayloads),
            'updated' => count($updatePayloads),
        ];
    }

    public function fetchRepositoryMeta(string $fullName): array
    {
        $response = Http::withToken($this->token)
            ->accept('application/vnd.github+json')
            ->get("{$this->apiUrl}/repos/{$fullName}")
            ->throw();

        return $response->json();
    }

    protected function buildCommitPayload(GithubRepository $repository, array $data, array $userMap): array
    {
        $sha = (string) $data['sha'];
        $commit = $data['commit'] ?? [];
        $author = $commit['author'] ?? [];
        $committer = $commit['committer'] ?? [];
        $authorNode = $data['author'] ?? null;

        $email = $author['email'] ?? null;
        $username = is_array($authorNode) ? ($authorNode['login'] ?? null) : null;
        $user = $this->resolveUser($email, $username, $userMap);

        return [
            'repository_id' => $repository->id,
            'sha' => $sha,
            'short_sha' => substr($sha, 0, 7),
            'user_id' => $user?->id,
            'message' => Str::limit($commit['message'] ?? null, 60000),
            'author_name' => $author['name'] ?? ($username ?? null),
            'author_email' => $email,
            'author_username' => $username,
            'authored_at' => $this->parseDate($author['date'] ?? null),
            'committed_at' => $this->parseDate($committer['date'] ?? null),
            'url' => $data['html_url'] ?? null,
            'updated_at' => now(),
            'created_at' => now(),
        ];
    }

    /**
     * Build a single lookup map for resolving users by email/username in memory.
     *
     * @return array<string, User> maps lowercased email/username to User
     */
    protected function buildUserMap(): array
    {
        $map = [];

        foreach (User::query()->whereNotNull('github_username')->get(['id', 'github_username', 'github_email', 'email']) as $user) {
            if ($user->github_username) {
                $map['username:'.mb_strtolower($user->github_username)] = $user;
            }
            if ($user->github_email) {
                $map['email:'.mb_strtolower($user->github_email)] = $user;
            }
            if ($user->email) {
                $map['email:'.mb_strtolower($user->email)] = $user;
            }
        }

        return $map;
    }

    protected function resolveUser(?string $email, ?string $username, array $userMap): ?User
    {
        if ($email && isset($userMap['email:'.mb_strtolower($email)])) {
            return $userMap['email:'.mb_strtolower($email)];
        }

        if ($username && isset($userMap['username:'.mb_strtolower($username)])) {
            return $userMap['username:'.mb_strtolower($username)];
        }

        return null;
    }

    protected function insertNewCommits(array $payloads): void
    {
        foreach (array_chunk($payloads, 500) as $chunk) {
            DB::table('github_commits')->insert($chunk);
        }
    }

    protected function updateExistingCommits(array $payloads): void
    {
        foreach ($payloads as $payload) {
            $repositoryId = $payload['repository_id'];
            $sha = $payload['sha'];

            unset($payload['repository_id'], $payload['sha'], $payload['created_at']);

            GithubCommit::query()
                ->where('repository_id', $repositoryId)
                ->where('sha', $sha)
                ->update($payload);
        }
    }

    protected function extractStats(array $detail): array
    {
        return [
            'additions' => $detail['stats']['additions'] ?? 0,
            'deletions' => $detail['stats']['deletions'] ?? 0,
            'files_changed' => count($detail['files'] ?? []),
        ];
    }

    protected function fetchCommits(string $fullName, ?string $since, ?string $until, array $existingShas = []): array
    {
        $commits = [];
        $page = 1;

        do {
            $response = Http::withToken($this->token)
                ->accept('application/vnd.github+json')
                ->timeout(60)
                ->connectTimeout(10)
                ->retry(2, 100)
                ->get("{$this->apiUrl}/repos/{$fullName}/commits", array_filter([
                    'per_page' => $this->perPage,
                    'page' => $page,
                    'since' => $since,
                    'until' => $until,
                ]));

            if ($response->status() === 403 && (int) $response->header('X-RateLimit-Remaining') === 0) {
                throw new RuntimeException('GitHub API rate limit exceeded. Please try again later.');
            }

            $response->throw();

            $batch = $response->json();

            if (! is_array($batch)) {
                break;
            }

            $commits = array_merge($commits, $batch);

            // GitHub returns commits newest-first. Once a whole page consists of
            // commits we already have, every older page will too — stop early.
            if ($existingShas && collect($batch)->every(fn ($c) => isset($existingShas[(string) ($c['sha'] ?? '')]))) {
                break;
            }

            $page++;
        } while (count($batch) === $this->perPage && $page <= 50);

        return $commits;
    }

    protected function fetchCommitDetail(string $fullName, string $sha): array
    {
        $response = Http::withToken($this->token)
            ->accept('application/vnd.github+json')
            ->timeout(60)
            ->connectTimeout(10)
            ->retry(2, 100)
            ->get("{$this->apiUrl}/repos/{$fullName}/commits/{$sha}")
            ->throw();

        return $response->json();
    }

    protected function parseDate(?string $date): ?Carbon
    {
        return $date ? Carbon::parse($date) : null;
    }
}
