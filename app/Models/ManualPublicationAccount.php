<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class ManualPublicationAccount extends Model
{
    public const PLATFORM_ZHIHU = 'zhihu';

    public const PLATFORM_XIAOHONGSHU = 'xiaohongshu';

    public const PLATFORM_WEIBO = 'weibo';

    public const PLATFORM_WECHAT = 'wechat';

    public const PLATFORM_DOUYIN = 'douyin';

    public const PLATFORM_BILIBILI = 'bilibili';

    public const PLATFORM_REDDIT = 'reddit';

    public const PLATFORM_X = 'x';

    public const PLATFORM_LINKEDIN = 'linkedin';

    public const PLATFORM_CUSTOM = 'custom';

    public const PLATFORMS = [
        self::PLATFORM_ZHIHU,
        self::PLATFORM_XIAOHONGSHU,
        self::PLATFORM_WEIBO,
        self::PLATFORM_WECHAT,
        self::PLATFORM_DOUYIN,
        self::PLATFORM_BILIBILI,
        self::PLATFORM_REDDIT,
        self::PLATFORM_X,
        self::PLATFORM_LINKEDIN,
        self::PLATFORM_CUSTOM,
    ];

    protected $attributes = [
        'is_active' => true,
        'auto_publish_enabled' => false,
        'publish_method' => 'POST',
        'publish_auth_type' => 'none',
        'publish_status' => 'idle',
        'publish_attempt_count' => 0,
    ];

    protected $fillable = [
        'persona_id',
        'platform',
        'publish_adapter',
        'custom_platform',
        'account_name',
        'profile_url',
        'notes',
        'auto_publish_enabled',
        'publish_endpoint_url',
        'publish_method',
        'publish_auth_type',
        'publish_login_identifier',
        'publish_auth_header_name',
        'publish_basic_username',
        'publish_secret_key_id',
        'publish_secret_ciphertext',
        'publish_session_ciphertext',
        'publish_profile_json',
        'publish_status',
        'publish_attempt_count',
        'last_published_at',
        'next_publish_at',
        'last_publish_status',
        'last_publish_error',
        'last_remote_id',
        'last_remote_url',
        'is_active',
        'created_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'persona_id' => 'integer',
            'is_active' => 'boolean',
            'auto_publish_enabled' => 'boolean',
            'created_by_admin_id' => 'integer',
            'publish_attempt_count' => 'integer',
            'publish_profile_json' => 'array',
            'last_published_at' => 'datetime',
            'next_publish_at' => 'datetime',
        ];
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(ManualPublicationPersona::class, 'persona_id');
    }

    public function publications(): HasMany
    {
        return $this->hasMany(ManualPublication::class, 'account_id');
    }

    public function socialPublications(): HasMany
    {
        return $this->hasMany(SocialPublication::class, 'manual_publication_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    public function platformLabelKey(): string
    {
        return 'admin.manual_publications.platform.'.$this->platform;
    }

    public function autoPublishSecret(): string
    {
        return trim((string) $this->publish_secret_ciphertext);
    }

    public function autoPublishSession(): string
    {
        return trim((string) $this->publish_session_ciphertext);
    }

    public function resolvedPublishAdapter(): string
    {
        $adapter = trim((string) ($this->publish_adapter ?: ''));
        if ($adapter !== '') {
            return $adapter;
        }

        return match ($this->platform) {
            self::PLATFORM_ZHIHU => 'zhihu_login_session',
            self::PLATFORM_XIAOHONGSHU => 'xiaohongshu_login_session',
            self::PLATFORM_BILIBILI => 'bilibili_login_session',
            default => 'generic_http_api',
        };
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeReadyForAutoPublish(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('auto_publish_enabled', true)
            ->where(function (Builder $builder): void {
                $builder->whereNotNull('publish_endpoint_url')
                    ->orWhereNotNull('publish_session_ciphertext')
                    ->orWhereNotNull('publish_secret_ciphertext');
            })
            ->whereRaw("TRIM(COALESCE(publish_endpoint_url, '')) <> ''");
    }
}
