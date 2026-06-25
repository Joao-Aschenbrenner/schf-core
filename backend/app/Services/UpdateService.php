<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class UpdateService
{
    protected string $repo = 'ORG/schf-core';
    protected string $githubApi = 'https://api.github.com';

    public function __construct()
    {
        $this->repo = config('app.update_repo', 'ORG/schf-core');
    }

    public function fetchLatestRelease(): ?array
    {
        try {
            $response = Http::timeout(10)
                ->accept('application/vnd.github.v3+json')
                ->get("{$this->githubApi}/repos/{$this->repo}/releases/latest");

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Falha ao buscar release mais recente', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar release', ['error' => $e->getMessage()]);
        }

        return null;
    }

    public function runUpdate(?string $targetVersion = null): array
    {
        $startTime = microtime(true);

        try {
            // 1. Verificar release alvo
            $release = $targetVersion
                ? $this->fetchSpecificRelease($targetVersion)
                : $this->fetchLatestRelease();

            if (!$release) {
                return ['success' => false, 'message' => 'Release não encontrado'];
            }

            $version = $release['tag_name'];

            // 2. Backup do banco antes da atualização
            $this->backupDatabase();

            // 3. Pull das novas imagens Docker
            $pullResult = $this->pullImages($version);
            if (!$pullResult['success']) {
                return $pullResult;
            }

            // 4. Rodar migrations
            $migrateResult = $this->runMigrations();
            if (!$migrateResult['success']) {
                $this->rollbackImages();
                return $migrateResult;
            }

            // 5. Health check pós-atualização
            $healthResult = $this->healthCheck();
            if (!$healthResult['success']) {
                $this->rollbackImages();
                $this->rollbackMigrations();
                return $healthResult;
            }

            // 6. Limpar cache
            $this->clearCache();

            $duration = round(microtime(true) - $startTime, 2);

            return [
                'success' => true,
                'version' => $version,
                'duration' => $duration,
                'message' => "Atualização para {$version} concluída com sucesso",
            ];
        } catch (\Exception $e) {
            Log::error('Erro durante atualização', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
        }
    }

    public function rollback(): array
    {
        try {
            // Rollback das imagens (usar tag anterior)
            $this->rollbackImages();

            // Rollback das migrations (última batch)
            $this->rollbackMigrations();

            // Health check
            $health = $this->healthCheck();

            return [
                'success' => $health['success'],
                'message' => $health['success'] ? 'Rollback concluído' : 'Rollback falhou no health check',
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Erro no rollback: ' . $e->getMessage()];
        }
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

    protected function fetchSpecificRelease(string $version): ?array
    {
        try {
            $response = Http::timeout(10)
                ->accept('application/vnd.github.v3+json')
                ->get("{$this->githubApi}/repos/{$this->repo}/releases/tags/{$version}");

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error('Erro ao buscar release específica', ['error' => $e->getMessage()]);
        }
        return null;
    }

    protected function pullImages(string $version): array
    {
        try {
            // Atualizar docker-compose com nova tag
            $composePath = base_path('docker-compose.yml');
            $compose = file_get_contents($composePath);

            // Substituir tags das imagens
            $images = ['backend', 'frontend', 'nginx', 'queue'];
            foreach ($images as $img) {
                $compose = preg_replace(
                    "/(image: .*schf-{$img}:)[^\s]+/",
                    '$1' . $version,
                    $compose
                );
            }

            file_put_contents($composePath, $compose);

            // Pull
            $result = Process::run('docker compose pull', base_path());
            if ($result->failed()) {
                return ['success' => false, 'message' => 'Falha no docker compose pull: ' . $result->errorOutput()];
            }

            // Restart containers
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