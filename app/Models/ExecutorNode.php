<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExecutorNode extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'node_key_hash',
        'platforms',
        'max_concurrency',
        'enabled',
        'online',
        'load',
        'ip',
        'version',
        'last_heartbeat_at',
        'done',
        'fail',
        'created_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'platforms' => 'array',
            'max_concurrency' => 'integer',
            'enabled' => 'boolean',
            'online' => 'boolean',
            'load' => 'integer',
            'done' => 'integer',
            'fail' => 'integer',
            'last_heartbeat_at' => 'datetime',
        ];
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(ExecutorJob::class, 'executor_node_id');
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(ManualPublicationAccount::class, 'executor_node_id');
    }

    public function isFresh(int $ttlSeconds = 90): bool
    {
        if (! $this->enabled || ! $this->last_heartbeat_at) {
            return false;
        }

        // Use the database timestamp as the source of truth. The boolean `online`
        // flag is a cached display value and may lag during a heartbeat race.
        return $this->last_heartbeat_at->gte(now()->subSeconds($ttlSeconds));
    }
}
