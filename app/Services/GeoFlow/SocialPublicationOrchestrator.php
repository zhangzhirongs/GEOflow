<?php

namespace App\Services\GeoFlow;

use App\Jobs\ProcessSocialPublicationJob;
use App\Models\Article;
use App\Models\ManualPublicationAccount;
use App\Models\SocialPublication;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SocialPublicationOrchestrator
{
    public function __construct(
        private readonly SocialPublishAdapterManager $adapterManager,
    ) {}

    public function enqueueForArticle(int|Article $article): void
    {
        $articleModel = $article instanceof Article
            ? $article
            : Article::query()->whereKey($article)->first();
        if (! $articleModel) {
            return;
        }

        $articleModel->loadMissing(['author:id,name', 'category:id,name']);

        $accounts = ManualPublicationAccount::query()
            ->with('persona:id,name')
            ->readyForAutoPublish()
            ->orderBy('next_publish_at')
            ->orderBy('id')
            ->get();
        if ($accounts->isEmpty()) {
            return;
        }

        foreach ($accounts as $account) {
            $this->queueAccount($articleModel, $account);
        }
    }

    public function process(SocialPublication $publication): bool
    {
        $publication = SocialPublication::query()
            ->with(['article', 'account.persona'])
            ->whereKey($publication->id)
            ->first();
        if (! $publication || ! $publication->article || ! $publication->account) {
            return false;
        }

        $lockedPublication = DB::transaction(function () use ($publication): ?SocialPublication {
            $lockedPublication = SocialPublication::query()
                ->whereKey($publication->id)
                ->lockForUpdate()
                ->first();
            if (! $lockedPublication || $lockedPublication->status !== 'queued') {
                return null;
            }

            $account = ManualPublicationAccount::query()
                ->whereKey((int) $lockedPublication->manual_publication_account_id)
                ->lockForUpdate()
                ->first();
            if (! $account || ! $account->is_active || ! $account->auto_publish_enabled) {
                $lockedPublication->forceFill([
                    'status' => 'failed',
                    'last_error_message' => '账号不可用或未启用自动发布。',
                    'next_retry_at' => null,
                ])->save();

                return null;
            }

            $lockedPublication->forceFill([
                'status' => 'sending',
                'attempt_count' => (int) $lockedPublication->attempt_count + 1,
                'last_attempt_at' => now(),
                'last_error_message' => null,
            ])->save();

            return $lockedPublication;
        });

        if (! $lockedPublication) {
            return false;
        }

        $account = ManualPublicationAccount::query()->whereKey((int) $lockedPublication->manual_publication_account_id)->first();
        if (! $account) {
            return false;
        }

        $account->forceFill([
            'publish_adapter' => $account->resolvedPublishAdapter(),
        ])->save();

        $adapter = $this->adapterManager->forAccount($account);
        $response = $adapter->publish($account, $this->buildPayload($lockedPublication));

        DB::transaction(function () use ($lockedPublication, $account, $response): void {
            $lockedPublication = SocialPublication::query()->whereKey($lockedPublication->id)->lockForUpdate()->first();
            $account = ManualPublicationAccount::query()->whereKey((int) $account->id)->lockForUpdate()->first();
            if (! $lockedPublication || ! $account) {
                return;
            }

            $account->forceFill([
                'publish_status' => 'synced',
                'publish_attempt_count' => (int) $account->publish_attempt_count + 1,
                'last_published_at' => now(),
                'last_publish_status' => 'synced',
                'last_publish_error' => null,
                'last_remote_id' => is_scalar($response['remote_id'] ?? null) ? (string) $response['remote_id'] : null,
                'last_remote_url' => is_scalar($response['remote_url'] ?? null) ? (string) $response['remote_url'] : null,
            ])->save();

            $lockedPublication->forceFill([
                'status' => 'synced',
                'remote_id' => is_scalar($response['remote_id'] ?? null) ? (string) $response['remote_id'] : null,
                'remote_url' => is_scalar($response['remote_url'] ?? null) ? (string) $response['remote_url'] : null,
                'remote_meta' => $response['remote_meta'] ?? null,
                'last_published_at' => now(),
                'next_retry_at' => null,
                'last_error_message' => null,
            ])->save();
        });

        return true;
    }

    private function queueAccount(Article $article, ManualPublicationAccount $account): void
    {
        DB::transaction(function () use ($article, $account): void {
            $lockedAccount = ManualPublicationAccount::query()
                ->whereKey((int) $account->id)
                ->lockForUpdate()
                ->first();
            if (! $lockedAccount || ! $lockedAccount->is_active || ! $lockedAccount->auto_publish_enabled) {
                return;
            }

            $publication = SocialPublication::query()
                ->where('article_id', (int) $article->id)
                ->where('manual_publication_account_id', (int) $lockedAccount->id)
                ->lockForUpdate()
                ->first();
            if ($publication && in_array((string) $publication->status, ['queued', 'sending'], true)) {
                return;
            }

            $publication ??= new SocialPublication([
                'article_id' => (int) $article->id,
                'manual_publication_account_id' => (int) $lockedAccount->id,
                'platform' => (string) $lockedAccount->platform,
            ]);

            $publication->forceFill([
                'status' => 'queued',
                'attempt_count' => 0,
                'last_attempt_at' => null,
                'next_retry_at' => now(),
                'payload_hash' => $this->payloadHash($article, $lockedAccount),
                'payload_snapshot' => $this->buildPayloadSnapshot($article),
                'remote_meta' => null,
                'last_error_message' => null,
            ])->save();

            $lockedAccount->forceFill([
                'publish_adapter' => $lockedAccount->resolvedPublishAdapter(),
                'publish_status' => 'queued',
                'next_publish_at' => now(),
                'last_publish_status' => 'queued',
                'last_publish_error' => null,
            ])->save();

            ProcessSocialPublicationJob::dispatch((int) $publication->id)
                ->onQueue('distribution')
                ->afterCommit();
        });
    }

    /**
     * @return array<string,mixed>
     */
    private function buildPayload(SocialPublication $publication): array
    {
        $article = $publication->article;
        $account = $publication->account;
        if (! $article || ! $account) {
            throw new RuntimeException('社交发布对象缺失。');
        }

        return [
            'article' => $this->buildPayloadSnapshot($article),
            'account' => [
                'id' => (int) $account->id,
                'platform' => (string) $account->platform,
                'name' => (string) $account->account_name,
            ],
            'platform' => (string) $account->platform,
            'published_at' => now()->toAtomString(),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function buildPayloadSnapshot(Article $article): array
    {
        $article->loadMissing(['author:id,name', 'category:id,name']);

        return [
            'id' => (int) $article->id,
            'title' => (string) $article->title,
            'slug' => (string) $article->slug,
            'excerpt' => (string) ($article->excerpt ?? ''),
            'content' => (string) $article->content,
            'status' => (string) $article->status,
            'review_status' => (string) $article->review_status,
            'category' => $article->category ? [
                'id' => (int) $article->category->id,
                'name' => (string) $article->category->name,
            ] : null,
            'author' => $article->author ? [
                'id' => (int) $article->author->id,
                'name' => (string) $article->author->name,
            ] : null,
            'published_at' => $article->published_at?->toAtomString(),
            'updated_at' => $article->updated_at?->toAtomString(),
        ];
    }

    private function payloadHash(Article $article, ManualPublicationAccount $account): string
    {
        return hash('sha256', json_encode([
            'article_id' => (int) $article->id,
            'account_id' => (int) $account->id,
            'platform' => (string) $account->platform,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
    }
}
