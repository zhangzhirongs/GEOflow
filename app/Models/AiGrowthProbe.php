<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AiGrowthProbe extends Model
{
    public const STATUS_QUEUED = 'queued';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const VERDICT_OWNED_CONTENT = 'owned_content';

    public const VERDICT_OWNED_BRAND = 'owned_brand';

    public const VERDICT_COMPETITOR = 'competitor';

    public const VERDICT_NONE = 'none';

    public const CHANNEL_AUTO = 'auto';

    public const CHANNEL_ALL = 'all';

    public const CHANNEL_SEARCH_THEN_ANALYSIS = 'search_then_analysis';

    public const CHANNEL_SEARCH = 'doubao_search_custom';

    public const CHANNEL_DEEPSEEK = 'deepseek_analysis';

    public const CHANNEL_ARK = 'doubao_ark_responses';

    public const CHANNEL_CHAT_PREFIX = 'chat:';

    public const CHANNELS = [
        self::CHANNEL_AUTO,
        self::CHANNEL_ALL,
        self::CHANNEL_SEARCH_THEN_ANALYSIS,
        self::CHANNEL_SEARCH,
        self::CHANNEL_DEEPSEEK,
        self::CHANNEL_ARK,
    ];

    protected $fillable = [
        'uuid',
        'admin_id',
        'ai_visibility_run_id',
        'question',
        'provider_type',
        'status',
        'verdict',
        'brand_mentioned',
        'owned_content_mentioned',
        'answer_excerpt',
        'analysis_json',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'admin_id' => 'integer',
            'ai_visibility_run_id' => 'integer',
            'brand_mentioned' => 'boolean',
            'owned_content_mentioned' => 'boolean',
            'analysis_json' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AiGrowthProbe $probe): void {
            if (trim((string) $probe->uuid) === '') {
                $probe->uuid = (string) Str::uuid();
            }
            if (trim((string) ($probe->provider_type ?? '')) === '') {
                $probe->provider_type = self::CHANNEL_AUTO;
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function visibilityRun(): BelongsTo
    {
        return $this->belongsTo(AiVisibilityRun::class, 'ai_visibility_run_id');
    }

    public function isPending(): bool
    {
        return in_array((string) $this->status, [self::STATUS_QUEUED, self::STATUS_RUNNING], true);
    }

    public function providerType(): string
    {
        $value = trim((string) ($this->provider_type ?? ''));

        return (in_array($value, self::CHANNELS, true) || self::isChatChannel($value))
            ? $value
            : self::CHANNEL_AUTO;
    }

    public function providerLabel(): string
    {
        $type = $this->providerType();
        $modelId = self::chatModelId($type);
        if ($modelId !== null) {
            $name = trim((string) (AiModel::query()->whereKey($modelId)->value('name') ?? ''));

            return $name !== '' ? $name : __('admin.analytics.overview.probe.channels.chat');
        }

        return __('admin.analytics.overview.probe.channels.'.$type);
    }

    public static function chatChannel(int $modelId): string
    {
        return self::CHANNEL_CHAT_PREFIX.$modelId;
    }

    public static function isChatChannel(string $channel): bool
    {
        return preg_match('/^'.preg_quote(self::CHANNEL_CHAT_PREFIX, '/').'\d+$/', $channel) === 1;
    }

    public static function chatModelId(string $channel): ?int
    {
        if (preg_match('/^'.preg_quote(self::CHANNEL_CHAT_PREFIX, '/').'(\d+)$/', $channel, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }

    public static function isKnownChannel(string $channel): bool
    {
        return in_array($channel, self::CHANNELS, true) || self::isChatChannel($channel);
    }
}
