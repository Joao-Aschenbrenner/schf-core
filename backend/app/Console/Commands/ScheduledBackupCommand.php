<?php

namespace App\Console\Commands;

use App\Models\Backup;
use App\Services\Backup\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ScheduledBackupCommand extends Command
{
    protected $signature = 'backup:scheduled
                            {--type=database : Type of backup (full|database|files)}
                            {--password= : Encryption password (required for full backups)}';

    protected $description = 'Run a scheduled backup';

    public function handle(BackupService $backupService): int
    {
        $type = $this->option('type');
        $password = $this->option('password');

        if ($type === 'full' && empty($password)) {
            $this->error('Password is required for full backups');
            return Command::FAILURE;
        }

        $backupName = "scheduled_backup_{$type}_" . date('Y-m-d_His');

        $backup = Backup::create([
            'name' => $backupName,
            'type' => $type,
            'status' => 'running',
            'started_at' => now(),
            'user_id' => null,
        ]);

        $this->info("Starting scheduled backup: {$backupName}");

        try {
            $result = match ($type) {
                'full' => $backupService->createFullBackup($password),
                'database' => $backupService->createDatabaseBackup(),
                'files' => $backupService->createFilesBackup($password),
                default => throw new \InvalidArgumentException("Unknown backup type: {$type}"),
            };

            if ($result['success']) {
                $backup->update([
                    'status' => 'completed',
                    'file_path' => $result['path'] ?? null,
                    'file_name' => $result['filename'] ?? null,
                    'file_size' => $result['size'] ?? null,
                    'checksum' => $result['checksum'] ?? null,
                    'encrypted' => !empty($password),
                    'completed_at' => now(),
                ]);

                $this->info("Backup completed: {$backupName}");
                $filename = $result['filename'] ?? 'N/A';
                $size = $this->formatSize($result['size'] ?? 0);
                $this->info("File: {$filename}");
                $this->info("Size: {$size}");

                return Command::SUCCESS;
            } else {
                $backup->update([
                    'status' => 'failed',
                    'error_message' => $result['message'] ?? 'Unknown error',
                    'completed_at' => now(),
                ]);

                $errorMsg = $result['message'] ?? 'Unknown error';
                $this->error("Backup failed: {$errorMsg}");
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $backup->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            $this->error("Backup failed: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    private function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $size = $bytes;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        return round($size, 1) . ' ' . $units[$i];
    }
}
