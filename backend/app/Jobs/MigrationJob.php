<?php

namespace App\Jobs;

use App\Services\Migration\MigrationRunner;
use Illuminate\Support\Facades\Log;

class MigrationJob extends BaseJob
{
    public string $jobType = 'migration';

    public function __construct(
        protected string $packagePath,
        protected ?string $dataFile = null
    ) {
        parent::__construct();
        $this->timeout = 600;
    }

    public function handle(): array
    {
        Log::info("Iniciando MigrationJob", ['package' => $this->packagePath]);

        $runner = app(MigrationRunner::class);

        $report = $this->dataFile
            ? $runner->runFromFile($this->packagePath, $this->dataFile)
            : $runner->run($this->packagePath);

        $summary = $report->toArray()['summary'] ?? [];

        $report->save(storage_path('app/reports/migrations'));

        return [
            'success' => $summary['success'] ?? false,
            'imported' => $summary['imported'] ?? 0,
            'failed' => $summary['failed'] ?? 0,
            'duration' => $summary['duration_seconds'] ?? 0,
        ];
    }
}