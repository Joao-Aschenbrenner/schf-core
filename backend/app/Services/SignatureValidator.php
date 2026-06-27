<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SignatureValidator
{
    protected string $repo;
    protected string $githubApi = 'https://api.github.com';

    public function __construct()
    {
        $this->repo = config('app.update_repo', 'ORG/schf-core');
    }

    public function verifySha256(string $filePath, string $expectedHash): bool
    {
        if (!file_exists($filePath)) {
            Log::error('Arquivo não encontrado para verificação', ['path' => $filePath]);
            return false;
        }

        $actualHash = hash_file('sha256', $filePath);

        if (!hash_equals($expectedHash, $actualHash)) {
            Log::warning('Hash SHA256 divergente', [
                'expected' => $expectedHash,
                'actual' => $actualHash,
                'file' => $filePath,
            ]);
            return false;
        }

        return true;
    }

    public function computeSha256(string $filePath): ?string
    {
        if (!file_exists($filePath)) {
            return null;
        }

        return hash_file('sha256', $filePath);
    }

    public function getReleaseChecksums(string $version): ?array
    {
        try {
            $release = $this->fetchRelease($version);
            if (!$release) {
                return null;
            }

            $assets = $release['assets'] ?? [];
            foreach ($assets as $asset) {
                $name = $asset['name'] ?? '';
                if (str_ends_with(strtolower($name), 'checksums.txt') ||
                    str_ends_with(strtolower($name), 'checksums.sha256') ||
                    str_ends_with(strtolower($name), 'SHA256SUMS')) {

                    $url = $asset['browser_download_url'] ?? null;
                    if ($url) {
                        $content = $this->downloadChecksumsFile($url);
                        if ($content) {
                            return $this->parseChecksums($content);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Erro ao buscar checksums', ['error' => $e->getMessage(), 'version' => $version]);
        }

        return null;
    }

    public function verifyReleaseAsset(string $version, string $assetName, string $expectedHash): array
    {
        $downloader = app(ReleaseDownloader::class);
        $assetPath = $downloader->getDownloadPath($version, $assetName);

        if (!$assetPath) {
            return [
                'verified' => false,
                'message' => "Asset não encontrado localmente: {$assetName}",
            ];
        }

        $computed = $this->computeSha256($assetPath);
        if (!$computed) {
            return [
                'verified' => false,
                'message' => 'Erro ao computar hash do arquivo',
            ];
        }

        $matches = hash_equals($expectedHash, $computed);

        return [
            'verified' => $matches,
            'asset_name' => $assetName,
            'expected_hash' => $expectedHash,
            'computed_hash' => $computed,
            'message' => $matches ? 'Hash verificado com sucesso' : 'Hash divergente',
        ];
    }

    public function verifyAllReleaseAssets(string $version): array
    {
        $checksums = $this->getReleaseChecksums($version);
        if (!$checksums) {
            return [
                'verified' => false,
                'message' => 'Checksums não disponíveis para esta versão',
            ];
        }

        $downloader = app(ReleaseDownloader::class);
        $results = [];
        $allValid = true;

        foreach ($checksums as $assetName => $hash) {
            $result = $this->verifyReleaseAsset($version, $assetName, $hash);
            $results[$assetName] = $result;
            if (!$result['verified']) {
                $allValid = false;
            }
        }

        return [
            'verified' => $allValid,
            'version' => $version,
            'total_assets' => count($results),
            'valid_assets' => count(array_filter($results, fn($r) => $r['verified'])),
            'results' => $results,
        ];
    }

    public function getSigningKey(): ?string
    {
        $keyPath = config('app.update_signing_key_path');

        if ($keyPath && file_exists($keyPath)) {
            return file_get_contents($keyPath);
        }

        return null;
    }

    public function hasSigningKey(): bool
    {
        return $this->getSigningKey() !== null;
    }

    protected function parseChecksums(string $content): array
    {
        $checksums = [];
        $lines = explode("\n", trim($content));

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            $parts = preg_split('/\s+/', $line);
            if (count($parts) >= 2) {
                $hash = $parts[0];
                $filename = $parts[1];

                $filename = preg_replace('/^\*\//', '', $filename);

                $checksums[$filename] = $hash;
            }
        }

        return $checksums;
    }

    protected function downloadChecksumsFile(string $url): ?string
    {
        try {
            $response = Http::timeout(30)->get($url);
            if ($response->successful()) {
                return $response->body();
            }
        } catch (\Exception $e) {
            Log::error('Erro ao baixar checksums', ['error' => $e->getMessage(), 'url' => $url]);
        }

        return null;
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
            Log::error('Erro ao buscar release', ['error' => $e->getMessage()]);
        }

        return null;
    }
}