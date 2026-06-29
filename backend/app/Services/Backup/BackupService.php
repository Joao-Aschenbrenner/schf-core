<?php

namespace App\Services\Backup;

use App\Models\Backup;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ZipArchive;
use Exception;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class BackupService
{
    protected string $backupPath;
    protected ?string $password;

    public function __construct(?string $password = null)
    {
        $this->backupPath = storage_path('app/backups');
        $this->password = $password ?? config('app.backup_password', env('BACKUP_PASSWORD'));
        
        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function createFullBackup(User $user, ?string $password = null): Backup
    {
        return $this->createBackup($user, 'full', $password);
    }

    public function createDatabaseBackup(User $user, ?string $password = null): Backup
    {
        return $this->createBackup($user, 'database', $password);
    }

    public function createFilesBackup(User $user, ?string $password = null): Backup
    {
        return $this->createBackup($user, 'files', $password);
    }

    protected function createBackup(User $user, string $type, ?string $password = null): Backup
    {
        $password = $password ?? $this->password;
        $encrypted = !empty($password);
        
        $backup = Backup::create([
            'name' => "backup_{$type}_" . now()->format('Ymd_His'),
            'type' => $type,
            'status' => 'running',
            'encrypted' => $encrypted,
            'password_hash' => $encrypted ? Hash::make($password) : null,
            'user_id' => $user->id,
            'started_at' => now(),
        ]);

        try {
            $fileName = $backup->name . ($encrypted ? '.enc' : '.zip');
            $filePath = $this->backupPath . '/' . $fileName;

            $zip = new ZipArchive();
            if (!$zip->open($filePath, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
                throw new Exception("Não foi possível criar arquivo zip: {$filePath}");
            }

            switch ($type) {
                case 'full':
                    $this->addDatabaseToZip($zip);
                    $this->addFilesToZip($zip);
                    $this->addStorageToZip($zip);
                    $this->addConfigToZip($zip);
                    break;
                case 'database':
                    $this->addDatabaseToZip($zip);
                    break;
                case 'files':
                    $this->addFilesToZip($zip);
                    $this->addStorageToZip($zip);
                    break;
            }

            $zip->close();

            // Criptografar se necessário
            $finalPath = $filePath;
            if ($encrypted) {
                $finalPath = $this->encryptFile($filePath, $password);
                unlink($filePath); // Remove zip não criptografado
            }

            $fileSize = filesize($finalPath);
            $checksum = hash_file('sha256', $finalPath);

            $backup->update([
                'status' => 'completed',
                'file_path' => str_replace(storage_path('app') . '/', '', $finalPath),
                'file_name' => basename($finalPath),
                'file_size' => $fileSize,
                'checksum' => $checksum,
                'completed_at' => now(),
                'metadata' => [
                    'type' => $type,
                    'encrypted' => $encrypted,
                    'original_size' => $fileSize,
                ],
            ]);

            return $backup->fresh();
        } catch (Exception $e) {
            $backup->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
            throw $e;
        }
    }

    protected function addDatabaseToZip(ZipArchive $zip): void
    {
        $connection = config('database.default');
        $dbConfig = config("database.connections.{$connection}");
        $dumpFile = sys_get_temp_dir() . '/db_dump_' . time() . '.sql';

        if (($dbConfig['driver'] ?? null) === 'sqlite') {
            $database = $dbConfig['database'] ?? null;
            if ($database && $database !== ':memory:' && file_exists($database)) {
                $dump = file_get_contents($database);
            } else {
                $dump = "-- SCHF SQLite in-memory backup placeholder\n";
            }

            $zip->addFromString('database/dump.sql', $dump);
            return;
        }

        if (($dbConfig['driver'] ?? null) !== 'mysql') {
            $zip->addFromString('database/dump.sql', '-- SCHF database backup for driver: ' . ($dbConfig['driver'] ?? 'unknown') . "\n");
            return;
        }
        
        $command = sprintf(
            'mysqldump --ssl=0 --no-tablespaces -h%s -u%s %s %s --single-transaction --routines --triggers 2>/dev/null > %s',
            escapeshellarg($dbConfig['host']),
            escapeshellarg($dbConfig['username']),
            !empty($dbConfig['password']) ? '-p' . escapeshellarg($dbConfig['password']) : '',
            escapeshellarg($dbConfig['database']),
            escapeshellarg($dumpFile)
        );

        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0 || !file_exists($dumpFile)) {
            throw new Exception('Falha ao gerar dump do banco de dados');
        }

        $zip->addFromString('database/dump.sql', file_get_contents($dumpFile));
        unlink($dumpFile);
    }

    protected function addFilesToZip(ZipArchive $zip): void
    {
        $filesPath = base_path();
        $exclude = [
            'vendor',
            'node_modules',
            '.git',
            'storage/logs',
            'storage/framework/cache',
            'storage/framework/sessions',
            'storage/framework/views',
            'storage/app/backups',
            'storage/app/testing',
            'bootstrap/cache',
            '.env',
            'backups',
        ];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($filesPath, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            $relativePath = $file->getPathname();
            $relativePath = str_replace($filesPath . '/', '', $relativePath);
            
            $skip = false;
            foreach ($exclude as $ex) {
                if (str_starts_with($relativePath, $ex . '/') || $relativePath === $ex) {
                    $skip = true;
                    break;
                }
            }
            
            if ($skip || !$file->isFile()) {
                continue;
            }

            $zip->addFile($file->getPathname(), 'app/' . $relativePath);
        }
    }

    protected function addStorageToZip(ZipArchive $zip): void
    {
        $storagePath = storage_path('app');
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($storagePath, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            $relativePath = str_replace($storagePath . '/', '', $file->getPathname());
            if (str_starts_with($relativePath, 'backups/') || str_starts_with($relativePath, 'testing/')) {
                continue;
            }

            if ($file->isFile()) {
                $zip->addFile($file->getPathname(), 'storage/' . $relativePath);
            }
        }
    }

    protected function addConfigToZip(ZipArchive $zip): void
    {
        $configPath = base_path('config');
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($configPath, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relativePath = str_replace($configPath . '/', '', $file->getPathname());
                $zip->addFile($file->getPathname(), 'config/' . $relativePath);
            }
        }
    }

    protected function encryptFile(string $filePath, string $password): string
    {
        $encryptedPath = $filePath . '.enc';
        $iv = random_bytes(16);
        $key = hash('sha256', $password, true);
        
        $input = fopen($filePath, 'rb');
        $output = fopen($encryptedPath, 'wb');
        
        // Escrever IV no início do arquivo
        fwrite($output, $iv);
        
        while (!feof($input)) {
            $chunk = fread($input, 8192);
            $encrypted = openssl_encrypt($chunk, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
            fwrite($output, $encrypted);
        }
        
        fclose($input);
        fclose($output);
        
        return $encryptedPath;
    }

    public function decryptFile(string $encryptedPath, string $outputPath, string $password): bool
    {
        $input = fopen($encryptedPath, 'rb');
        $output = fopen($outputPath, 'wb');
        
        // Ler IV (primeiros 16 bytes)
        $iv = fread($input, 16);
        if (strlen($iv) !== 16) {
            fclose($input);
            fclose($output);
            return false;
        }
        
        $key = hash('sha256', $password, true);
        
        while (!feof($input)) {
            $chunk = fread($input, 8192);
            $decrypted = openssl_decrypt($chunk, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
            
            if ($decrypted === false) {
                fclose($input);
                fclose($output);
                return false;
            }
            
            fwrite($output, $decrypted);
        }
        
        fclose($input);
        fclose($output);
        return true;
    }

    public function verifyPassword(Backup $backup, string $password): bool
    {
        if (!$backup->encrypted || !$backup->password_hash) {
            return false;
        }
        
        return Hash::check($password, $backup->password_hash);
    }

    public function verifyIntegrity(Backup $backup): bool
    {
        if (!$backup->file_path || !Storage::disk('local')->exists($backup->file_path)) {
            return false;
        }

        $fullPath = Storage::disk('local')->path($backup->file_path);
        
        if (!file_exists($fullPath)) {
            return false;
        }
        
        $currentChecksum = hash_file('sha256', $fullPath);
        return $currentChecksum === $backup->checksum;
    }

    public function cleanupOldBackups(): array
    {
        $deleted = [];
        
        // Manter últimos 30 diários
        $dailyBackups = Backup::completed()
            ->where('type', 'database')
            ->orderByDesc('created_at')
            ->offset(30)
            ->limit(PHP_INT_MAX)
            ->get();
        
        foreach ($dailyBackups as $backup) {
            $this->deleteBackupFiles($backup);
            $backup->delete();
            $deleted[] = $backup->name;
        }
        
        // Manter últimos 12 semanais (domingos)
        $weeklyBackups = Backup::completed()
            ->where('type', 'full')
            ->whereRaw($this->datePartSql('dow', 'created_at'))
            ->orderByDesc('created_at')
            ->offset(12)
            ->limit(PHP_INT_MAX)
            ->get();
        
        foreach ($weeklyBackups as $backup) {
            $this->deleteBackupFiles($backup);
            $backup->delete();
            $deleted[] = $backup->name;
        }
        
        // Manter últimos 12 mensais (primeiro dia do mês)
        $monthlyBackups = Backup::completed()
            ->where('type', 'full')
            ->whereRaw($this->datePartSql('day', 'created_at'))
            ->orderByDesc('created_at')
            ->offset(12)
            ->limit(PHP_INT_MAX)
            ->get();
        
        foreach ($monthlyBackups as $backup) {
            $this->deleteBackupFiles($backup);
            $backup->delete();
            $deleted[] = $backup->name;
        }
        
        return $deleted;
    }

    protected function deleteBackupFiles(Backup $backup): void
    {
        $fullPath = storage_path('app/' . $backup->file_path);
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }

    protected function datePartSql(string $part, string $column): string
    {
        $driver = DB::connection()->getDriverName();

        return match ([$driver, $part]) {
            ['sqlite', 'dow'] => "strftime('%w', {$column}) = '0'",
            ['sqlite', 'day'] => "strftime('%d', {$column}) = '01'",
            default => $part === 'dow' ? "DAYOFWEEK({$column}) = 1" : "DAY({$column}) = 1",
        };
    }
}
