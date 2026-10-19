<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExecutorJob extends Model
{
    protected $fillable = [
        'type',
        'status',
        'executor_node_id',
        'manual_publication_account_id',
        'social_publication_id',
        'manual_publication_id',
        'platform',
        'recipe',
        'payload',
        'login_identifier',
        'error',
        'remote_id',
        'remote_url',
        'logs',
        'claimed_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'executor_node_id' => 'integer',
            'manual_publication_account_id' => 'integer',
            'social_publication_id' => 'integer',
            'manual_publication_id' => 'integer',
            'recipe' => 'array',
            'payload' => 'array',
            'logs' => 'array',
            'claimed_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(ExecutorNode::class, 'executor_node_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ManualPublicationAccount::class, 'manual_publication_account_id');
    }

    public function socialPublication(): BelongsTo
    {
        return $this->belongsTo(SocialPublication::class, 'social_publication_id');
    }

    public function manualPublication(): BelongsTo
    {
        return $this->belongsTo(ManualPublication::class, 'manual_publication_id');
    }

    public function toNodeJob(): array
    {
        return [
            'id' => (string) $this->id,
            'type' => (string) $this->type,
            'status' => (string) $this->status,
            'accountId' => (string) $this->manual_publication_account_id,
            'platform' => (string) $this->platform,
            'recipe' => is_array($this->recipe) ? $this->recipe : [],
            'payload' => is_array($this->payload) ? $this->payload : null,
            'loginIdentifier' => $this->login_identifier,
            'createdAt' => optional($this->created_at)?->toAtomString(),
            'claimedAt' => optional($this->claimed_at)?->toAtomString(),
        ];
    }
}
