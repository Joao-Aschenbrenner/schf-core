<?php

namespace App\Services;

use App\Models\UpdateHistory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class UpdateService
{
    protected string $repo;
    protected string $githubApi = 'https://api.github.com';

    protected VersionChecker $versionChecker;
    protected ReleaseDownloader $downloader;
    protected SignatureValidator $validator;

    public function __construct(
        VersionChecker $versionChecker,
        ReleaseDownloader $downloader,
        SignatureValidator $validator
    ) {
        $this->repo = config('app.update_repo', 'ORG/schf-core');
        $this->versionChecker = $versionChecker;
        $this->downloader = $downloader;
        $this->validator = $validator;
    }

    public function check(): array
    {
        return $this->versionChecker->checkLatest();
    }

    public function checkSpecific(string $version): array
    {
        return $this->versionChecker->checkSpecific($version);
    }

    public function versions(): array
    {
        return $this->versionChecker->listAvailableVersions();
    }

    public function download(string $version): array
    {
        return $this->downloader->downloadRelease($version);
    }

    public function verify(string $version): array
    {
        return $this->validator->verifyAllReleaseAssets($version);
    }

    public function runUpdate(?string $targetVersion = null): array
    {
        $currentVersion = $this->versionChecker->getCurrentVersion();

        $check = $targetVersion
            ? $this->versionChecker->checkSpecific($targetVersion)
            : $this->versionChecker->checkLatest();

        if ($check['error'] ?? true) {
            return ['success' => false, 'message' => $check['message'] ?? 'Erro ao verificar atualização'];
        }

        if (!($check['update_available'] ?? false)) {
            return ['success' => false, 'message' => 'Nenhuma atualização disponível'];
        }

        $version = $targetVersion ?? $check['latest_version'];

        $history = UpdateHistory::create([
            'from_version' => $currentVersion,
            'to_version' => $version,
            'status' => 'pending',
            'method' => 'docker_pull',
            'started_at' => now(),
        ]);

        $history->markRunning();

        $downloadResult = $this->downloader->downloadRelease($version);
        if (!$downloadResult['success']) {
            $history->markFailed($downloadResult['message']);
            return ['success' => false, 'message' => 'Falha no download: ' . $downloadResult['message']];
        }

        $verifyResult = $this->validator->verifyAllReleaseAssets($version);
        if (!$verifyResult['verified']) {
            $history->markFailed('Verificação de integridade falhou', $verifyResult);
            return ['success' => false, 'message' => 'Verificação de integridade falhou', 'details' => $verifyResult];
        }

        $this->backupDatabase();

        $pullResult = $this->pullImages($version);
        if (!$pullResult['success']) {
            $history->markFailed($pullResult['message']);
            return $pullResult;
        }

        $migrateResult = $this->runMigrations();
        if (!$migrateResult['success']) {
            $this->rollbackImages();
            $history->markFailed($migrateResult['message']);
            return $migrateResult;
        }

        $healthResult = $this->healthCheck();
        if (!$healthResult['success']) {
            $this->rollbackImages();
            $this->rollbackMigrations();
            $history->markFailed('Health check pós-atualização falhou');
            return $healthResult;
        }

        $this->clearCache();
        $history->markSuccess(['verified' => true]);

        return [
            'success' => true,
            'version' => $version,
            'duration' => $history->duration_seconds,
            'history_id' => $history->id,
            'message' => "Atualização para {$version} concluída com sucesso",
        ];
    }

    public function rollback(): array
    {
        $lastSuccessful = UpdateHistory::getLastSuccessful();
        if (!$lastSuccessful) {
            return ['success' => false, 'message' => 'Nenhuma atualização bem-sucedida encontrada'];
        }

        $currentVersion = $this->versionChecker->getCurrentVersion();
        $targetVersion = $lastSuccessful->from_version;

        $history = UpdateHistory::create([
            'from_version' => $currentVersion,
            'to_version' => $targetVersion,
            'status' => 'pending',
            'method' => 'rollback',
            'started_at' => now(),
        ]);

        $history->markRunning();

        $this->rollbackImages();
        $this->rollbackMigrations();

        $health = $this->healthCheck();

        if ($health['success']) {
            $history->markRolledBack($targetVersion);
            return [
                'success' => true,
                'version' => $targetVersion,
                'history_id' => $history->id,
                'message' => "Rollback para {$targetVersion} concluído",
            ];
        }

        $history->markFailed('Health check pós-rollback falhou');
        return ['success' => false, 'message' => 'Rollback falhou no health check'];
    }

    public function getChangelog(string $currentVersion): string
    {
        try {
            $response = Http::timeout(10)
                ->accept('application/vnd.github.v3+json')
                ->get("{$this->githubApi}/repos/{$this->repo}/releases");

            if ($response->successful()) {
                $releases = $response->json();
                $changelog = '';

                foreach ($releases as $release) {
                    if (version_compare($currentVersion, $release['tag_name'], '<')) {
                        $changelog .= "## {$release['tag_name']} ({$release['published_at']})\n";
                        $changelog .= $release['body'] ?? 'Sem notas de release';
                        $changelog .= "\n\n";
                    }
                }

                return $changelog ?: 'Nenhuma atualização disponível';
            }
        } catch (\Exception $e) {
            Log::error('Erro ao buscar changelog', ['error' => $e->getMessage()]);
        }

        return 'Erro ao buscar changelog';
    }

    public function history(int $limit = 20): array
    {
        return UpdateHistory::orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    protected function pullImages(string $version): array
    {
        try {
            $composePath = base_path('docker-compose.yml');
            $compose = file_get_contents($composePath);

            $images = ['backend', 'frontend', 'nginx', 'queue'];
            foreach ($images as $img) {
                $compose = preg_replace(
                    "/(image: .*schf-{$img}:)[^\s]+/",
                    '$1' . $version,
                    $compose
                );
            }

            file_put_contents($composePath, $compose);

            $result = Process::run('docker compose pull', base_path());
            if ($result->failed()) {
                return ['success' => false, 'message' => 'Falha no docker compose pull: ' . $result->errorOutput()];
            }

            $result = Process::run('docker compose up -d', base_path());
            if ($result->failed()) {
                return ['success' => false, 'message' => 'Falha ao reiniciar containers: ' . $result->errorOutput()];
            }

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Erro no pull: ' . $e->getMessage()];
        }
    }

    protected function runMigrations(): array
    {
        try {
            $result = Process::run('docker exec schf-backend php artisan migrate --force', base_path());
            if ($result->failed()) {
                return ['success' => false, 'message' => 'Falha nas migrations: ' . $result->errorOutput()];
            }
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Erro nas migrations: ' . $e->getMessage()];
        }
    }

    protected function rollbackMigrations(): array
    {
        try {
            $result = Process::run('docker exec schf-backend php artisan migrate:rollback --step=1 --force', base_path());
            return ['success' => !$result->failed()];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Erro no rollback migrations: ' . $e->getMessage()];
        }
    }

    protected function healthCheck(): array
    {
        try {
            $response = Http::timeout(30)->get('http://localhost:9080/api/health');
            return ['success' => $response->successful()];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Health check falhou: ' . $e->getMessage()];
        }
    }

    protected function clearCache(): void
    {
        try {
            Process::run('docker exec schf-backend php artisan config:clear', base_path());
            Process::run('docker exec schf-backend php artisan route:clear', base_path());
            Process::run('docker exec schf-backend php artisan view:clear', base_path());
        } catch (\Exception $e) {
            Log::warning('Erro ao limpar cache', ['error' => $e->getMessage()]);
        }
    }

    protected function backupDatabase(): void
    {
        try {
            $timestamp = now()->format('Ymd_His');
            $filename = "pre_update_{$timestamp}.sql";
            Process::run("docker exec schf-mysql mysqldump -u root -p\${MYSQL_ROOT_PASSWORD} schf > /backups/{$filename}", base_path());
        } catch (\Exception $e) {
            Log::warning('Backup pré-atualização falhou', ['error' => $e->getMessage()]);
        }
    }

    protected function rollbackImages(): void
    {
        try {
            Process::run('docker compose pull', base_path());
            Process::run('docker compose up -d', base_path());
        } catch (\Exception $e) {
            Log::error('Erro no rollback de imagens', ['error' => $e->getMessage()]);
        }
    }
}