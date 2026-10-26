<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Requests\Api\EnqueueSocialPublicationRequest;
use App\Models\ManualPublicationAccount;
use App\Models\ExecutorJob;
use App\Services\Api\IdempotencyService;
use App\Services\GeoFlow\ExecutorJobService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class SocialPublicationController extends BaseApiController
{
    public function showJob(Request $request, int $job): JsonResponse
    {
        $record = ExecutorJob::query()
            ->with('account:id,account_name')
            ->find($job);
        if (! $record instanceof ExecutorJob) {
            throw new ApiException('social_job_not_found', '社媒发布任务不存在', 404);
        }

        return $this->success($request, [
            'job_id' => (int) $record->id,
            'type' => (string) $record->type,
            'status' => (string) $record->status,
            'platform' => (string) $record->platform,
            'account_id' => (int) $record->manual_publication_account_id,
            'account_name' => (string) ($record->account?->account_name ?? ''),
            'error' => (string) ($record->error ?? ''),
            'remote_id' => (string) ($record->remote_id ?? ''),
            'remote_url' => (string) ($record->remote_url ?? ''),
            'created_at' => $record->created_at?->toAtomString(),
            'claimed_at' => $record->claimed_at?->toAtomString(),
            'finished_at' => $record->finished_at?->toAtomString(),
        ]);
    }

    public function platforms(Request $request): JsonResponse
    {
        $platforms = array_map(fn (string $key): array => [
            'key' => $key,
            'name' => (string) __('admin.manual_publications.platform.'.$key),
        ], ManualPublicationAccount::PLATFORMS);

        return $this->success($request, ['items' => $platforms]);
    }

    public function accounts(Request $request): JsonResponse
    {
        $platform = trim((string) $request->query('platform', ''));
        if ($platform !== '' && ! in_array($platform, ManualPublicationAccount::PLATFORMS, true)) {
            throw new ApiException('validation_failed', '不支持的社媒平台', 422, [
                'field_errors' => ['platform' => '请选择 GEOFlow 支持的社媒平台'],
            ]);
        }

        $accounts = ManualPublicationAccount::query()
            ->with(['persona:id,name', 'executorNode:id,name,enabled,online,last_heartbeat_at'])
            ->where('is_active', true)
            ->when($platform !== '', fn ($query) => $query->where('platform', $platform))
            ->orderBy('platform')
            ->orderBy('account_name')
            ->get()
            ->map(fn (ManualPublicationAccount $account): array => [
                'id' => (int) $account->id,
                'platform' => (string) $account->platform,
                'platform_name' => (string) __('admin.manual_publications.platform.'.$account->platform),
                'account_name' => (string) $account->account_name,
                'persona_id' => (int) $account->persona_id,
                'persona_name' => (string) ($account->persona?->name ?? ''),
                'session_status' => (string) $account->session_status,
                'executor_node_id' => $account->executor_node_id ? (int) $account->executor_node_id : null,
                'executor_node_name' => (string) ($account->executorNode?->name ?? ''),
                'can_enqueue' => $account->executorNode !== null && (bool) $account->executorNode->enabled,
            ])->all();

        return $this->success($request, ['items' => $accounts]);
    }

    public function enqueue(
        EnqueueSocialPublicationRequest $request,
        ExecutorJobService $jobs,
    ): JsonResponse {
        return IdempotencyService::executeJson($request, 'POST /social-publication/jobs', function () use ($request, $jobs): JsonResponse {
            $data = $request->validated();
            $account = ManualPublicationAccount::query()
                ->with('executorNode')
                ->whereKey((int) $data['account_id'])
                ->where('is_active', true)
                ->first();
            if (! $account || ! $account->executorNode || ! $account->executorNode->enabled) {
                throw new ApiException('social_account_unavailable', '所选账号没有绑定可用的执行节点', 409);
            }

            try {
                $job = $jobs->enqueuePublish($account, [
                    'title' => (string) $data['title'],
                    'content' => (string) $data['content'],
                    'excerpt' => (string) ($data['excerpt'] ?? ''),
                    'images' => array_values($data['images'] ?? []),
                    'autoPublish' => false,
                ]);
            } catch (RuntimeException) {
                throw new ApiException('social_job_enqueue_failed', '社媒发布任务无法加入队列', 409);
            }

            return $this->success($request, [
                'job_id' => (int) $job->id,
                'job_type' => (string) $job->type,
                'job_state' => (string) $job->status,
                'account_id' => (int) $account->id,
                'account_name' => (string) $account->account_name,
                'platform' => (string) $account->platform,
                'executor_node_id' => (int) $account->executor_node_id,
                'remote_publish_completed' => false,
            ], 202);
        });
    }
}
