<?php

namespace App\Jobs;

use App\Models\UpdateHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

abstract class BaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;
    public int $maxExceptions = 2;
    public string $jobType = 'unknown';

    protected ?UpdateHistory $history = null;

    public function __construct()
    {
        $this->onQueue('default');
    }

    abstract public function handle(): array;

    public function failed(\Throwable $exception): void
    {
        Log::error("Job falhou", [
            'job' => static::class,
            'type' => $this->jobType,
            'error' => $exception->getMessage(),
        ]);

        if ($this->history) {
            $this->history->markFailed($exception->getMessage());
        }
    }

    public function middleware(): array
    {
        return [];
    }

    public function tags(): array
    {
        return ['schf', $this->jobType];
    }

    protected function createHistory(string $fromVersion = '0.0.0', string $toVersion = '0.0.0'): UpdateHistory
    {
        $this->history = UpdateHistory::create([
            'from_version' => $fromVersion,
            'to_version' => $toVersion,
            'status' => 'pending',
            'method' => $this->jobType,
            'started_at' => now(),
        ]);

        $this->history->markRunning();
        return $this->history;
    }
}