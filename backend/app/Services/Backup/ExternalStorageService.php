<?php

namespace App\Services\Backup;

use App\Models\Backup;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class ExternalStorageService
{
    protected array $disks = [];

    public function __construct()
    {
        $this->disks = config('backup.external_disks', []);
    }

    public function listAvailableDisks(): array
    $available = [];

        foreach ($this->disks as $name => $config) {
            $driver = Storage::build([
                'driver' => $config['driver'] ?? 'local',
                'root' => $config['root'] ?? storage_path('app/backups'),
            ]);

            try {
                $exists = $driver->exists('.');
                $freeSpace = $this->getFreeSpace($config['root'] ?? '');
                
                $available[] = [
                    'name' => $name,
                    'label' => $config['label'] ?? $name,
                    'driver' => $config['driver'] ?? 'local',
                    'path' => $config['root'] ?? '',
                    'available' => $exists,
                    'free_space_bytes' => $freeSpace,
                    'free_space_human' => $this->formatBytes($freeSpace),
                ];
            } catch (\Exception $e) {
                Log::warning("External disk check failed: {$name}", ['error' => $e->getMessage()]);
                $available[] = [
                    'name' => $name,
                    'label' => $config['label'] ?? $name,
                    'driver' => $config['driver'] ?? 'local',
                    'path' => $config['root'] ?? '',
                    'available' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $available;
    }

    public function copyBackupToExternal(Backup $backup, string $targetDiskName): array
    {
        if (!isset($this->disks[$targetDiskName])) {
            throw new Exception("Disco externo não configurado: {$targetDiskName}");
        }

        $sourcePath = storage_path('app/' . $backup->file_path);
        
        if (!file_exists($sourcePath)) {
            throw new Exception('Arquivo de backup não encontrado: ' . $sourcePath);
        }

        $targetConfig = $this->disks[$targetDiskName];
        $targetDriver = Storage::build([
            'driver' => $targetConfig['driver'] ?? 'local',
            'root' => $targetConfig['root'] ?? storage_path('app/backups'),
        ]);

        $targetFileName = $backup->file_name;
        $targetPath = $targetConfig['root'] . '/' . $targetFileName;

        try {
            // Verifica espaço disponível
            $freeSpace = $this->getFreeSpace($targetConfig['root']);
            $fileSize = filesize(storage_path('app/' . $backup->file_path));
            
            if ($freeSpace < $fileSize * 1.1) { // 10% margem
                throw new Exception("Espaço insuficiente no destino. Necessário: {$fileSize} bytes, Disponível: {$freeSpace} bytes");
            }

            // Copia arquivo
            $sourceContent = file_get_contents(storage_path('app/' . $backup->file_path));
            $targetDriver->put($targetFileName, $sourceContent);

            // Valida cópia
            $sourceChecksum = hash_file('sha256', storage_path('app/' . $backup->file_path));
            $targetPathFull = $targetConfig['root'] . '/' . $targetFileName;
            $targetChecksum = hash_file('sha256', $targetPathFull);

            if ($sourceChecksum !== $targetChecksum) {
                // Remove arquivo corrompido
                if (file_exists($targetPathFull)) {
                    unlink($targetPathFull);
                }
                throw new Exception('Checksum não confere após cópia. Origem: ' . $sourceChecksum . ', Destino: ' . $targetChecksum);
            }

            return [
                'success' => true,
                'source' => $backup->file_name,
                'destination' => $targetDiskName . ':' . $targetFileName,
                'size' => $fileSize,
                'checksum' => $sourceChecksum,
                'copied_at' => now()->toISOString(),
            ];
        } catch (Exception $e) {
            Log::error("External copy failed: {$e->getMessage()}", [
                'backup_id' => $backup->id,
                'disk' => $targetDiskName,
            ]);
            throw $e;
        }
    }

    public function verifyExternalCopy(Backup $backup, string $targetDiskName): bool
    {
        if (!isset($this->disks[$targetDiskName])) {
            return false;
        }

        $targetConfig = $this->disks[$targetDiskName];
        $targetPath = $targetConfig['root'] . '/' . $backup->file_name;
        $sourcePath = storage_path('app/' . $backup->file_path);

        if (!file_exists($targetPath) || !file_exists($sourcePath)) {
            return false;
        }

        $sourceChecksum = hash_file('sha256', $sourcePath);
        $targetChecksum = hash_file('sha256', $targetPath);

        return $sourceChecksum === $targetChecksum;
    }

    public function removeExternalCopy(Backup $backup, string $targetDiskName): bool
    {
        if (!isset($this->disks[$targetDiskName])) {
            return false;
        }

        $targetConfig = $this->disks[$targetDiskName];
        $targetPath = $targetConfig['root'] . '/' . $backup->file_name;

        if (file_exists($targetPath)) {
            return unlink($targetPath);
        }

        return true;
    }

    public function listExternalBackups(string $targetDiskName): array
    {
        if (!isset($this->disks[$targetDiskName])) {
            return [];
        }

        $targetConfig = $this->disks[$targetDiskName];
        $targetDriver = Storage::build([
            'driver' => $targetConfig['driver'] ?? 'local',
            'root' => $targetConfig['root'],
        ]);

        $files = [];
        try {
            foreach ($targetDriver->allFiles() as $file) {
                if (str_starts_with($file, 'backup_') && str_ends_with($file, '.zip') || str_ends_with($file, '.enc')) {
                    $path = $targetConfig['root'] . '/' . $file;
                    $files[] = [
                        'name' => $file,
                        'path' => $targetDiskName . ':' . $file,
                        'size' => $targetDriver->size($file),
                        'last_modified' => $targetDriver->lastModified($file),
                    ];
                }
            }
        } catch (Exception $e) {
            Log::warning("Failed to list external backups on {$targetDiskName}: {$e->getMessage()}");
        }

        return $files;
    }

    protected function getFreeSpace(string $path): int
    {
        if (!is_dir($path)) {
            return 0;
        }
        
        $free = disk_free_space($path);
        return $free !== false ? $free : 0;
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}