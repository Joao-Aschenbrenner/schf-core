<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class BackupJob extends BaseJob
{
    public string $jobType = 'backup';

    public function __construct(
        protected string $type = 'full',
        protected ?string $password = null
    ) {
        parent::__construct();
        $this->timeout = 900;
    }

    public function handle(): array
    {
        Log::info("Iniciando BackupJob", ['type' => $this->type]);

        $startTime = microtime(true);

        try {
            $timestamp = now()->format('Ymd_His');
            $filename = "backup_{$this->type}_{$timestamp}.sql";
            $backupDir = storage_path('app/backups');

            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            $backupPath = $backupDir . '/' . $filename;

            $result = Process::run(
                "docker exec schf-mysql mysqldump -u root -p\${MYSQL_ROOT_PASSWORD} schf > {$backupPath}",
                base_path()
            );

            if ($result->failed()) {
                return ['success' => false, 'message' => 'Falha no backup: ' . $result->errorOutput()];
            }

            $size = file_exists($backupPath) ? filesize($backupPath) : 0;
            $duration = round(microtime(true) - $startTime, 2);

            return [
                'success' => true,
                'file' => $filename,
                'path' => $backupPath,
                'size' => $size,
                'duration' => $duration,
                'type' => $this->type,
            ];
        } catch (\Exception $e) {
            Log::error("Erro no BackupJob", ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}