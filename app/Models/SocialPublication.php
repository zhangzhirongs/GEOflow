<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialPublication extends Model
{
    protected $fillable = [
        'article_id',
        'manual_publication_account_id',
        'platform',
        'status',
        'attempt_count',
        'last_attempt_at',
        'last_published_at',
        'next_retry_at',
        'remote_id',
        'remote_url',
        'payload_hash',
        'payload_snapshot',
        'remote_meta',
        'last_error_message',
    ];

    protected function casts(): array
    {
        return [
            'article_id' => 'integer',
            'manual_publication_account_id' => 'integer',
            'attempt_count' => 'integer',
            'last_attempt_at' => 'datetime',
            'last_published_at' => 'datetime',
            'next_retry_at' => 'datetime',
            'payload_snapshot' => 'array',
            'remote_meta' => 'array',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'article_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ManualPublicationAccount::class, 'manual_publication_account_id');
    }
}
