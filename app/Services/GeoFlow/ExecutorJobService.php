<?php

namespace App\Services\GeoFlow;

use App\Models\ExecutorJob;
use App\Models\ExecutorNode;
use App\Models\ManualPublicationAccount;
use App\Models\ManualPublication;
use App\Models\SocialPublication;
use App\Support\GeoRank\WireGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ExecutorJobService
{
    public function __construct(private readonly GeoRankRecipeCatalog $recipes) {}

    /**
     * @return array{node: ExecutorNode, pairing: array<string,mixed>}
     */
    public function createNode(array $input, ?int $adminId = null): array
    {
        $name = trim((string) ($input['name'] ?? '')) ?: 'executor';
        $slug = $this->uniqueSlug((string) ($input['slug'] ?? $name));
        $nodeKey = 'grn_'.rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
        $node = ExecutorNode::query()->create([
            'name' => $name,
            'slug' => $slug,
            'node_key_hash' => hash('sha256', $nodeKey),
            'platforms' => $input['platforms'] ?? ['xiaohongshu', 'zhihu', 'bilibili', 'weibo'],
            'max_concurrency' => max(1, min(16, (int) ($input['max_concurrency'] ?? 1))),
            'enabled' => true,
            'online' => false,
            'created_by_admin_id' => $adminId,
        ]);

        return ['node' => $node, 'pairing' => $this->pairingPayload($node, $nodeKey, $input['base_url'] ?? null)];
    }

    /**
     * @return array<string,mixed>
     */
    public function resetKey(ExecutorNode $node, ?string $baseUrl = null): array
    {
        $nodeKey = 'grn_'.rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
        $node->forceFill([
            'node_key_hash' => hash('sha256', $nodeKey),
            'online' => false,
        ])->save();

        return $this->pairingPayload($node, $nodeKey, $baseUrl);
    }

    public function authenticate(string $slug, string $nodeKey): ?ExecutorNode
    {
        $node = ExecutorNode::query()->where('slug', $slug)->where('enabled', true)->first();
        if (! $node) {
            return null;
        }
        if (! hash_equals((string) $node->node_key_hash, hash('sha256', $nodeKey))) {
            return null;
        }

        return $node;
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    public function heartbeat(ExecutorNode $node, array $payload, ?string $ip = null): array
    {
        WireGuard::assertNoSecrets($payload);
        $node->forceFill([
            'online' => true,
            'load' => (int) ($payload['load'] ?? 0),
            'version' => (string) ($payload['version'] ?? $node->version),
            'ip' => $ip ?: (string) ($payload['ip'] ?? $node->ip),
            'last_heartbeat_at' => now(),
        ])->save();

        $this->reconcileManualPublications($node);

        foreach (($payload['sessions'] ?? []) as $session) {
            if (! is_array($session)) {
                continue;
            }
            $accountId = (int) ($session['accountId'] ?? 0);
            if ($accountId <= 0) {
                continue;
            }
            ManualPublicationAccount::query()
                ->whereKey($accountId)
                ->where('executor_node_id', $node->id)
                ->update([
                    'session_status' => (string) ($session['status'] ?? 'unknown'),
                    'session_checked_at' => now(),
                ]);
        }

        $accounts = ManualPublicationAccount::query()
            ->where('executor_node_id', $node->id)
            ->orderBy('id')
            ->get(['id', 'account_name', 'platform', 'is_active', 'session_status', 'session_checked_at']);

        $node->refresh();

        return [
            'node' => [
                'id' => (string) $node->id,
                'slug' => $node->slug,
                'online' => $node->isFresh(),
                'load' => $node->load,
                'maxConcurrency' => $node->max_concurrency,
            ],
            'accounts' => $accounts->map(fn (ManualPublicationAccount $account): array => [
                'id' => (string) $account->id,
                'name' => (string) $account->account_name,
                'platform' => (string) $account->platform,
                'enabled' => (bool) $account->is_active,
                'sessionStatus' => (string) ($account->session_status ?: 'missing'),
            ])->all(),
            'heartbeatAt' => optional($node->last_heartbeat_at)->toAtomString(),
        ];
    }

    private function reconcileManualPublications(ExecutorNode $node): void
    {
        ManualPublication::query()
            ->with(['account', 'article'])
            ->where('status', ManualPublication::STATUS_IN_PROGRESS)
            ->where('type', ManualPublication::TYPE_POST)
            ->whereHas('account', fn ($query) => $query->where('executor_node_id', $node->id))
            ->orderBy('id')
            ->get()
            ->each(function (ManualPublication $publication): void {
                $exists = ExecutorJob::query()
                    ->where('manual_publication_id', $publication->getKey())
                    ->whereIn('status', ['queued', 'running'])
                    ->exists();
                if (! $exists) {
                    try {
                        $this->enqueueManualPublication($publication);
                    } catch (RuntimeException $exception) {
                        report($exception);
                    }
                }
            });
    }

    public function claim(ExecutorNode $node): ?ExecutorJob
    {
        return DB::transaction(function () use ($node): ?ExecutorJob {
            $running = ExecutorJob::query()
                ->where('executor_node_id', $node->id)
                ->where('status', 'running')
                ->count();
            if ($running >= max(1, (int) $node->max_concurrency)) {
                return null;
            }

            $platforms = is_array($node->platforms) ? $node->platforms : [];
            $job = ExecutorJob::query()
                ->where('status', 'queued')
                ->where(function ($query) use ($node): void {
                    $query->whereNull('executor_node_id')->orWhere('executor_node_id', $node->id);
                })
                ->when($platforms !== [], fn ($query) => $query->whereIn('platform', $platforms))
                ->orderBy('id')
                ->lockForUpdate()
                ->first();
            if (! $job) {
                return null;
            }

            $job->forceFill([
                'status' => 'running',
                'executor_node_id' => $node->id,
                'claimed_at' => now(),
            ])->save();

            if ($job->type === 'login') {
                ManualPublicationAccount::query()->whereKey($job->manual_publication_account_id)->update([
                    'session_status' => 'login_in_progress',
                ]);
            }

            return $job->refresh();
        });
    }

    /**
     * @param  array<string,mixed>  $report
     */
    public function report(ExecutorNode $node, int $jobId, array $report): ExecutorJob
    {
        WireGuard::assertNoSecrets($report);

        return DB::transaction(function () use ($node, $jobId, $report): ExecutorJob {
            $job = ExecutorJob::query()->whereKey($jobId)->lockForUpdate()->first();
            if (! $job || (int) $job->executor_node_id !== (int) $node->id) {
                throw new RuntimeException('job not found');
            }

            $status = (string) ($report['status'] ?? 'failed');
            $sessionStatus = (string) ($report['sessionStatus'] ?? 'unknown');
            $job->forceFill([
                'status' => $status,
                'error' => (string) ($report['error'] ?? ''),
                'remote_id' => (string) ($report['remoteId'] ?? $job->remote_id ?? ''),
                'remote_url' => (string) ($report['remoteUrl'] ?? $job->remote_url ?? ''),
                'logs' => $report['logs'] ?? $job->logs,
                'finished_at' => now(),
            ])->save();

            if ($status === 'succeeded') {
                $node->increment('done');
            }
            if (in_array($status, ['failed', 'needs_login'], true)) {
                $node->increment('fail');
            }

            $account = ManualPublicationAccount::query()->whereKey($job->manual_publication_account_id)->lockForUpdate()->first();
            if ($account) {
                $account->forceFill([
                    'session_status' => $sessionStatus,
                    'session_checked_at' => now(),
                    'last_publish_status' => $job->type === 'publish' ? $status : $account->last_publish_status,
                    'last_publish_error' => $status === 'failed' ? mb_substr((string) ($report['error'] ?? ''), 0, 1000) : null,
                    'last_remote_id' => $job->type === 'publish' ? ($job->remote_id ?: $account->last_remote_id) : $account->last_remote_id,
                    'last_remote_url' => $job->type === 'publish' ? ($job->remote_url ?: $account->last_remote_url) : $account->last_remote_url,
                    'last_published_at' => $job->type === 'publish' && $status === 'succeeded' ? now() : $account->last_published_at,
                    'publish_status' => $job->type === 'publish' ? ($status === 'succeeded' ? 'synced' : $status) : $account->publish_status,
                ])->save();
            }

            if ($job->social_publication_id) {
                $publication = SocialPublication::query()->whereKey($job->social_publication_id)->lockForUpdate()->first();
                if ($publication) {
                    $publication->forceFill([
                        'status' => $status === 'succeeded' ? 'synced' : ($status === 'needs_login' ? 'queued' : 'failed'),
                        'remote_id' => $job->remote_id ?: $publication->remote_id,
                        'remote_url' => $job->remote_url ?: $publication->remote_url,
                        'last_published_at' => $status === 'succeeded' ? now() : $publication->last_published_at,
                        'last_error_message' => $status === 'succeeded' ? null : mb_substr((string) ($report['error'] ?? ''), 0, 1000),
                        'next_retry_at' => $status === 'needs_login' ? now()->addMinutes(5) : null,
                    ])->save();
                }
            }

            if ($job->manual_publication_id) {
                $manual = ManualPublication::query()->whereKey($job->manual_publication_id)->lockForUpdate()->first();
                if ($manual) {
                    $nextStatus = $status === 'succeeded'
                        ? ManualPublication::STATUS_COMPLETED
                        : ($status === 'needs_login' ? ManualPublication::STATUS_IN_PROGRESS : ManualPublication::STATUS_FAILED);
                    $previousStatus = (string) $manual->status;
                    $manual->forceFill([
                        'status' => $nextStatus,
                        'status_changed_at' => now(),
                        'completed_at' => $status === 'succeeded' ? now() : null,
                        'result_note' => $status === 'succeeded'
                            ? 'executor completed'
                            : mb_substr((string) ($report['error'] ?? ''), 0, 1000),
                    ])->save();
                    if ($previousStatus !== $nextStatus) {
                        \App\Models\ManualPublicationTransition::query()->create([
                            'manual_publication_id' => $manual->getKey(),
                            'from_status' => $previousStatus,
                            'to_status' => $nextStatus,
                            'result_note' => $manual->result_note,
                        ]);
                    }
                }
            }

            return $job->refresh();
        });
    }

    public function enqueueLogin(ManualPublicationAccount $account): ExecutorJob
    {
        return $this->enqueue($account, 'login');
    }

    public function enqueueCheck(ManualPublicationAccount $account): ExecutorJob
    {
        return $this->enqueue($account, 'check');
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    public function enqueuePublish(ManualPublicationAccount $account, array $payload, ?SocialPublication $publication = null): ExecutorJob
    {
        return $this->enqueue($account, 'publish', $payload, $publication);
    }

    public function enqueueManualPublication(ManualPublication $publication): ExecutorJob
    {
        if ($publication->type !== ManualPublication::TYPE_POST) {
            throw new RuntimeException('manual comments are not supported by the browser publish executor');
        }
        $account = $publication->account;
        if (! $account instanceof ManualPublicationAccount) {
            throw new RuntimeException('manual publication account is missing');
        }
        if (! $account->executor_node_id) {
            throw new RuntimeException('manual publication account is missing executor node');
        }

        $article = $publication->article;
        $title = (string) ($article?->title ?: data_get($publication->source_snapshot, 'title', ''));
        if ($title === '') {
            throw new RuntimeException('manual publication title is missing');
        }

        return $this->enqueue($account, 'publish', [
            'title' => $title,
            'content' => (string) $publication->content,
            'excerpt' => (string) ($publication->target_context ?? ''),
            'images' => [],
            'autoPublish' => false,
        ], null, $publication);
    }

    /**
     * @param  array<string,mixed>|null  $payload
     */
    private function enqueue(ManualPublicationAccount $account, string $type, ?array $payload = null, ?SocialPublication $publication = null, ?ManualPublication $manualPublication = null): ExecutorJob
    {
        if (! $account->executor_node_id) {
            throw new RuntimeException('account is not bound to an executor node');
        }
        WireGuard::assertNoSecrets($payload ?? []);

        return ExecutorJob::query()->create([
            'type' => $type,
            'status' => 'queued',
            'executor_node_id' => $account->executor_node_id,
            'manual_publication_account_id' => $account->id,
            'social_publication_id' => $publication?->id,
            'manual_publication_id' => $manualPublication?->id,
            'platform' => (string) $account->platform,
            'recipe' => $this->recipes->forPlatform((string) $account->platform),
            'payload' => $payload,
            'login_identifier' => (string) ($account->publish_login_identifier ?: $account->account_name),
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    public function pairingPayload(ExecutorNode $node, string $nodeKey, ?string $baseUrl = null): array
    {
        $url = rtrim((string) ($baseUrl ?: config('app.url')), '/');

        return [
            'Slug' => $node->slug,
            'NodeKey' => $nodeKey,
            'BaseUrl' => $url,
            'MaxConcurrency' => (int) $node->max_concurrency,
            'HeartbeatIntervalSeconds' => 30,
            'PollIntervalSeconds' => 10,
            'BrowserType' => 'chromium',
            'Headless' => false,
            'Language' => 'zh-CN',
            'IsValid' => true,
            'Mode' => 'remote',
        ];
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'node';
        $slug = $base;
        $i = 2;
        while (ExecutorNode::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
