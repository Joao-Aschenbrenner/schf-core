<?php

namespace App\Services\Backup;

use App\Models\Backup;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;
use Exception;

class RestoreService
{
    protected BackupService $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    public function restore(Backup $backup, User $user, string $password): array
    {
        // Verificar senha
        if ($backup->encrypted && !$this->backupService->verifyPassword($backup, $password)) {
            throw new Exception('Senha incorreta para este backup');
        }

        // Verificar integridade
        if (!$this->backupService->verifyIntegrity($backup)) {
            throw new Exception('Arquivo de backup corrompido ou modificado');
        }

        // Criar snapshot do estado atual antes da restauração
        $snapshot = $this->createSnapshot($user);

        try {
            $restoredPath = $this->extractBackup($backup);

            $results = [
                'database' => false,
                'files' => false,
                'storage' => false,
                'config' => false,
            ];

            // Processar baseado no tipo
            switch ($backup->type) {
                case 'full':
                    $results['database'] = $this->restoreDatabase($restoredPath);
                    $results['files'] = $this->restoreFiles($restoredPath);
                    $results['storage'] = $this->restoreStorage($restoredPath);
                    $results['config'] = $this->restoreConfig($restoredPath);
                    break;
                case 'database':
                    $results['database'] = $this->restoreDatabase($restoredPath);
                    break;
                case 'files':
                    $results['files'] = $this->restoreFiles($restoredPath);
                    $results['storage'] = $this->restoreStorage($restoredPath);
                    break;
            }

            // Limpar arquivos temporários
            $this->cleanup($restoredPath);

            return [
                'success' => true,
                'restored' => $results,
                'snapshot_id' => $snapshot->id ?? null,
            ];
        } catch (Exception $e) {
            // Rollback automático
            if (isset($snapshot)) {
                $this->rollback($snapshot);
            }
            throw $e;
        }
    }

    protected function createSnapshot(User $user): Backup
    {
        // Criar backup do estado atual antes da restauração
        return $this->backupService->createFullBackup($user, 'restore-snapshot');
    }

    protected function extractBackup(Backup $backup): string
    {
        $encryptedPath = Storage::disk('local')->path($backup->file_path);
        $tempDir = sys_get_temp_dir() . '/restore_' . time();
        
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $extractedPath = $tempDir . '/extracted';
        mkdir($extractedPath, 0755, true);

        // Se criptografado, descriptografar primeiro
        if ($backup->encrypted) {
            $zipPath = $tempDir . '/backup.zip';
            $decrypted = $this->backupService->decryptFile($encryptedPath, $zipPath, $backup->password_hash);
            
            if (!$decrypted) {
                throw new Exception('Falha ao descriptografar backup');
            }
        } else {
            $zipPath = $encryptedPath;
        }

        // Extrair ZIP
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new Exception('Não foi possível abrir arquivo zip');
        }

        $zip->extractTo($extractedPath);
        $zip->close();

        return $extractedPath;
    }

    protected function restoreDatabase(string $extractedPath): bool
    {
        $dumpFile = $extractedPath . '/database/dump.sql';
        
        if (!file_exists($dumpFile)) {
            throw new Exception('Arquivo de dump do banco não encontrado no backup');
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return true;
        }

        $connection = config('database.default');
        $dbConfig = config("database.connections.{$connection}");
        if (($dbConfig['driver'] ?? null) !== 'mysql') {
            return true;
        }
        
        $command = sprintf(
            'mysql -h%s -u%s %s %s < %s',
            escapeshellarg($dbConfig['host']),
            escapeshellarg($dbConfig['username']),
            !empty($dbConfig['password']) ? '-p' . escapeshellarg($dbConfig['password']) : '',
            escapeshellarg($dbConfig['database']),
            escapeshellarg($dumpFile)
        );

        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception('Falha ao restaurar banco de dados: ' . implode("\n", $output));
        }

        return true;
    }

    protected function restoreFiles(string $extractedPath): bool
    {
        $sourcePath = $extractedPath . '/app';
        
        if (!is_dir($sourcePath)) {
            return false; // Não há arquivos para restaurar
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourcePath, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relativePath = $file->getPathname();
                $relativePath = str_replace($extractedPath . '/app/', '', $relativePath);
                $targetPath = base_path($relativePath);
                
                $targetDir = dirname($targetPath);
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                
                copy($file->getPathname(), $targetPath);
            }
        }

        return true;
    }

    protected function restoreStorage(string $extractedPath): bool
    {
        $sourcePath = $extractedPath . '/storage';
        
        if (!is_dir($sourcePath)) {
            return false;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourcePath, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relativePath = $file->getPathname();
                $relativePath = str_replace($extractedPath . '/storage/', '', $relativePath);
                $targetPath = storage_path('app/' . $relativePath);
                
                $targetDir = dirname($targetPath);
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                
                copy($file->getPathname(), $targetPath);
            }
        }

        return true;
    }

    protected function restoreConfig(string $extractedPath): bool
    {
        $sourcePath = $extractedPath . '/config';
        
        if (!is_dir($sourcePath)) {
            return false;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourcePath, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relativePath = $file->getPathname();
                $relativePath = str_replace($extractedPath . '/config/', '', $relativePath);
                $targetPath = base_path('config/' . $relativePath);
                
                $targetDir = dirname($targetPath);
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                
                copy($file->getPathname(), $targetPath);
            }
        }

        // Limpar cache de configuração
        exec('php artisan config:clear');
        
        return true;
    }

    protected function rollback(Backup $snapshot): void
    {
        $this->restore($snapshot, $snapshot->user, $snapshot->password_hash);
    }

    protected function cleanup(string $tempDir): void
    {
        if (is_dir($tempDir)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    unlink($file->getPathname());
                } else {
                    rmdir($file->getPathname());
                }
            }
            
            rmdir($tempDir);
        }
    }
}
