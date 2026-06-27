<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ReleaseDownloader
{
    protected string $repo;
    protected string $githubApi = 'https://api.github.com';
    protected string $downloadDir;

    public function __construct()
    {
        $this->repo = config('app.update_repo', 'ORG/schf-core');
        $this->downloadDir = storage_path('app/updates');
        $this->ensureDirectoryExists();
    }

    protected function ensureDirectoryExists(): void
    {
        if (!is_dir($this->downloadDir)) {
            mkdir($this->downloadDir, 0755, true);
        }
    }

    public function downloadRelease(string $version): array
    {
        try {
            $release = $this->fetchRelease($version);
            if (!$release) {
                return ['success' => false, 'message' => 'Release não encontrado'];
            }

            $assets = $release['assets'] ?? [];
            if (empty($assets)) {
                return ['success' => false, 'message' => 'Release não possui assets para download'];
            }

            $downloaded = [];
            $errors = [];

            foreach ($assets as $asset) {
                $url = $asset['browser_download_url'] ?? null;
                $name = $asset['name'] ?? "release-{$version}";

                if (!$url) {
                    continue;
                }

                $result = $this->downloadAsset($url, $name, $version);
                if ($result['success']) {
                    $downloaded[] = $result;
                } else {
                    $errors[] = $result['message'];
                }
            }

            if (empty($downloaded)) {
                return ['success' => false, 'message' => 'Nenhum asset foi baixado: ' . implode(', ', $errors)];
            }

            return [
                'success' => true,
                'version' => $version,
                'assets' => $downloaded,
                'message' => count($downloaded) . ' asset(s) baixado(s) com sucesso',
            ];
        } catch (\Exception $e) {
            Log::error('Erro ao baixar release', ['error' => $e->getMessage(), 'version' => $version]);
            return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
        }
    }

    public function downloadAsset(string $url, string $name, string $version): array
    {
        try {
            $targetPath = $this->downloadDir . "/{$version}/{$name}";

            if (file_exists($targetPath)) {
                return [
                    'success' => true,
                    'name' => $name,
                    'path' => $targetPath,
                    'size' => filesize($targetPath),
                    'cached' => true,
                ];
            }

            $this->ensureDirectoryExists();
            $versionDir = $this->downloadDir . "/{$version}";
            if (!is_dir($versionDir)) {
                mkdir($versionDir, 0755, true);
            }

            $response = Http::timeout(300)
                ->sink($targetPath)
                ->withHeaders([
                    'Accept' => 'application/octet-stream',
                ])
                ->get($url);

            if (!$response->successful()) {
                @unlink($targetPath);
                return ['success' => false, 'message' => "Falha ao baixar {$name} (HTTP {$response->status()})"];
            }

            return [
                'success' => true,
                'name' => $name,
                'path' => $targetPath,
                'size' => filesize($targetPath),
                'cached' => false,
            ];
        } catch (\Exception $e) {
            Log::error('Erro ao baixar asset', ['error' => $e->getMessage(), 'name' => $name]);
            return ['success' => false, 'message' => "Erro ao baixar {$name}: " . $e->getMessage()];
        }
    }

    public function getDownloadPath(string $version, string $name): ?string
    {
        $path = $this->downloadDir . "/{$version}/{$name}";
        return file_exists($path) ? $path : null;
    }

    public function cleanupOldVersions(int $keepVersions = 3): array
    {
        $cleaned = [];
        $versions = $this->getDownloadedVersions();

        if (count($versions) <= $keepVersions) {
            return ['cleaned' => [], 'message' => 'Nenhuma limpeza necessária'];
        }

        $toDelete = array_slice($versions, $keepVersions);
        $currentVersion = app(VersionChecker::class)->getCurrentVersion();

        foreach ($toDelete as $version) {
            if ($version === $currentVersion) {
                continue;
            }

            $result = $this->deleteVersion($version);
            if ($result) {
                $cleaned[] = $version;
            }
        }

        return [
            'cleaned' => $cleaned,
            'message' => count($cleaned) . ' versão(ões) removida(s)',
        ];
    }

    public function getDownloadedVersions(): array
    {
        $versions = [];

        if (!is_dir($this->downloadDir)) {
            return $versions;
        }

        $dirs = scandir($this->downloadDir);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }
            $fullPath = $this->downloadDir . '/' . $dir;
            if (is_dir($fullPath) && preg_match('/^\d+\.\d+\.\d+$/', $dir)) {
                $versions[] = $dir;
            }
        }

        usort($versions, fn($a, $b) => version_compare($b, $a));

        return $versions;
    }

    public function getDownloadedAssets(string $version): array
    {
        $versionDir = $this->downloadDir . "/{$version}";
        if (!is_dir($versionDir)) {
            return [];
        }

        $assets = [];
        $files = scandir($versionDir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $versionDir . '/' . $file;
            if (is_file($path)) {
                $assets[] = [
                    'name' => $file,
                    'path' => $path,
                    'size' => filesize($path),
                    'modified' => filemtime($path),
                ];
            }
        }

        return $assets;
    }

    public function deleteVersion(string $version): bool
    {
        $versionDir = $this->downloadDir . "/{$version}";
        if (!is_dir($versionDir)) {
            return false;
        }

        $files = glob($versionDir . '/*');
        foreach ($files as $file) {
            @unlink($file);
        }
        @rmdir($versionDir);

        return true;
    }

    protected function fetchRelease(string $version): ?array
    {
        try {
            $response = Http::timeout(10)
                ->accept('application/vnd.github.v3+json')
                ->get("{$this->githubApi}/repos/{$this->repo}/releases/tags/{$version}");

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error('Erro ao buscar release para download', ['error' => $e->getMessage()]);
        }

        return null;
    }
}