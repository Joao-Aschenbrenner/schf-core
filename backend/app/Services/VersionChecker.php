<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VersionChecker
{
    protected string $repo;
    protected string $githubApi = 'https://api.github.com';

    public function __construct()
    {
        $this->repo = config('app.update_repo', 'ORG/schf-core');
    }

    public function getCurrentVersion(): string
    {
        $composerPath = base_path('composer.json');
        if (file_exists($composerPath)) {
            $composer = json_decode(file_get_contents($composerPath), true);
            return $composer['version'] ?? '0.0.0';
        }
        return '0.0.0';
    }

    public function isNewer(string $current, string $latest): bool
    {
        return version_compare($current, $latest, '<');
    }

    public function isCompatible(string $current, string $target): bool
    {
        $currentParts = explode('.', $current);
        $targetParts = explode('.', $target);

        $currentMajor = (int) ($currentParts[0] ?? 0);
        $targetMajor = (int) ($targetParts[0] ?? 0);

        return $currentMajor === $targetMajor;
    }

    public function isValid(string $version): bool
    {
        return (bool) preg_match('/^\d+\.\d+\.\d+$/', $version);
    }

    public function listAvailableVersions(): array
    {
        try {
            $response = Http::timeout(10)
                ->accept('application/vnd.github.v3+json')
                ->get("{$this->githubApi}/repos/{$this->repo}/releases");

            if ($response->successful()) {
                $releases = $response->json();
                $versions = [];

                foreach ($releases as $release) {
                    $tag = $release['tag_name'] ?? null;
                    if ($tag && $this->isValid($tag)) {
                        $versions[] = [
                            'version' => $tag,
                            'name' => $release['name'] ?? $tag,
                            'published_at' => $release['published_at'] ?? null,
                            'prerelease' => $release['prerelease'] ?? false,
                            'draft' => $release['draft'] ?? false,
                        ];
                    }
                }

                usort($versions, fn($a, $b) => version_compare($b['version'], $a['version']));

                return $versions;
            }
        } catch (\Exception $e) {
            Log::error('Erro ao listar versões', ['error' => $e->getMessage()]);
        }

        return [];
    }

    public function checkLatest(): array
    {
        $currentVersion = $this->getCurrentVersion();

        try {
            $response = Http::timeout(10)
                ->accept('application/vnd.github.v3+json')
                ->get("{$this->githubApi}/repos/{$this->repo}/releases/latest");

            if (!$response->successful()) {
                return [
                    'current_version' => $currentVersion,
                    'latest_version' => null,
                    'update_available' => false,
                    'message' => 'Não foi possível verificar atualizações',
                    'error' => true,
                ];
            }

            $latest = $response->json();
            $latestVersion = $latest['tag_name'] ?? null;
            $updateAvailable = $latestVersion ? $this->isNewer($currentVersion, $latestVersion) : false;

            return [
                'current_version' => $currentVersion,
                'latest_version' => $latestVersion,
                'update_available' => $updateAvailable,
                'is_compatible' => $latestVersion ? $this->isCompatible($currentVersion, $latestVersion) : false,
                'release_name' => $latest['name'] ?? $latestVersion,
                'release_notes' => $latest['body'] ?? '',
                'published_at' => $latest['published_at'] ?? null,
                'html_url' => $latest['html_url'] ?? null,
                'assets' => $this->parseAssets($latest['assets'] ?? []),
                'error' => false,
            ];
        } catch (\Exception $e) {
            Log::error('Erro ao verificar atualização', ['error' => $e->getMessage()]);
            return [
                'current_version' => $currentVersion,
                'latest_version' => null,
                'update_available' => false,
                'message' => 'Erro de conexão: ' . $e->getMessage(),
                'error' => true,
            ];
        }
    }

    public function checkSpecific(string $targetVersion): array
    {
        $currentVersion = $this->getCurrentVersion();

        if (!$this->isValid($targetVersion)) {
            return [
                'current_version' => $currentVersion,
                'target_version' => $targetVersion,
                'valid' => false,
                'message' => 'Versão inválida',
            ];
        }

        try {
            $response = Http::timeout(10)
                ->accept('application/vnd.github.v3+json')
                ->get("{$this->githubApi}/repos/{$this->repo}/releases/tags/{$targetVersion}");

            if (!$response->successful()) {
                return [
                    'current_version' => $currentVersion,
                    'target_version' => $targetVersion,
                    'valid' => false,
                    'message' => 'Release não encontrado',
                ];
            }

            $release = $response->json();
            $isNewer = $this->isNewer($currentVersion, $targetVersion);
            $isCompatible = $this->isCompatible($currentVersion, $targetVersion);

            return [
                'current_version' => $currentVersion,
                'target_version' => $targetVersion,
                'valid' => true,
                'update_available' => $isNewer,
                'is_compatible' => $isCompatible,
                'is_downgrade' => version_compare($currentVersion, $targetVersion, '>'),
                'release_name' => $release['name'] ?? $targetVersion,
                'release_notes' => $release['body'] ?? '',
                'published_at' => $release['published_at'] ?? null,
                'html_url' => $release['html_url'] ?? null,
                'assets' => $this->parseAssets($release['assets'] ?? []),
            ];
        } catch (\Exception $e) {
            Log::error('Erro ao verificar versão específica', ['error' => $e->getMessage()]);
            return [
                'current_version' => $currentVersion,
                'target_version' => $targetVersion,
                'valid' => false,
                'message' => 'Erro: ' . $e->getMessage(),
            ];
        }
    }

    protected function parseAssets(array $assets): array
    {
        return array_map(fn($a) => [
            'id' => $a['id'] ?? null,
            'name' => $a['name'] ?? '',
            'size' => $a['size'] ?? 0,
            'download_count' => $a['download_count'] ?? 0,
            'browser_download_url' => $a['browser_download_url'] ?? '',
            'content_type' => $a['content_type'] ?? '',
        ], $assets);
    }
}