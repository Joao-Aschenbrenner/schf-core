<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UpdateHistory extends Model
{
    protected $table = 'update_history';

    protected $fillable = [
        'from_version',
        'to_version',
        'status',
        'method',
        'notes',
        'metadata',
        'started_at',
        'finished_at',
        'duration_seconds',
        'user_id',
        'rollback_to_version',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'duration_seconds' => 'integer',
        ];
    }

    public function markRunning(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    public function markSuccess(array $metadata = []): void
    {
        $this->update([
            'status' => 'success',
            'finished_at' => now(),
            'duration_seconds' => $this->started_at ? now()->diffInSeconds($this->started_at) : null,
            'metadata' => $metadata,
        ]);
    }

    public function markFailed(string $notes, array $metadata = []): void
    {
        $this->update([
            'status' => 'failed',
            'finished_at' => now(),
            'duration_seconds' => $this->started_at ? now()->diffInSeconds($this->started_at) : null,
            'notes' => $notes,
            'metadata' => $metadata,
        ]);
    }

    public function markRolledBack(string $rollbackToVersion): void
    {
        $this->update([
            'status' => 'rolled_back',
            'rollback_to_version' => $rollbackToVersion,
            'finished_at' => now(),
            'duration_seconds' => $this->started_at ? now()->diffInSeconds($this->started_at) : null,
        ]);
    }

    public static function getLatest(): ?self
    {
        return static::orderByDesc('created_at')->first();
    }

    public static function getLastSuccessful(): ?self
    {
        return static::where('status', 'success')->orderByDesc('finished_at')->first();
    }
}