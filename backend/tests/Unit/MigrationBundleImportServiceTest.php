<?php

namespace Tests\Unit;

use App\Services\MigrationBundleImportService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use ZipArchive;

class MigrationBundleImportServiceTest extends TestCase
{
    public function test_it_validates_a_minimal_bundle(): void
    {
        $file = $this->createBundle();
        $service = app(MigrationBundleImportService::class);

        $result = $service->validate($file);

        $this->assertTrue($result['valid']);
        $this->assertSame('1.0.0', $result['manifest']['bundle_version']);
        $this->assertEmpty($result['errors']);
    }

    public function test_it_previews_a_minimal_bundle(): void
    {
        $file = $this->createBundle();
        $service = app(MigrationBundleImportService::class);

        $result = $service->preview($file);

        $this->assertTrue($result['valid']);
        $this->assertSame(1, $result['summary']['organization.json']);
        $this->assertSame(0, $result['summary']['users.json']);
    }

    private function createBundle(): UploadedFile
    {
        $dir = sys_get_temp_dir() . '/schf_bundle_' . uniqid();
        mkdir($dir, 0755, true);

        $payloads = [
            'organization.json' => [
                'external_id' => 'test-org',
                'name' => 'Test Organization',
                'legal_name' => null,
                'metadata' => [],
            ],
            'users.json' => [],
            'roles.json' => [],
            'permissions.json' => [],
            'suppliers.json' => [],
            'accounts.json' => [],
            'banks.json' => [],
            'categories.json' => [],
            'payments.json' => [],
            'expenses.json' => [],
            'report.json' => [
                'status' => 'ready',
                'generated_at' => '2026-01-01T00:00:00Z',
                'summary' => [],
                'warnings' => [],
                'errors' => [],
            ],
        ];

        foreach ($payloads as $name => $payload) {
            file_put_contents($dir . '/' . $name, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
        }

        $manifest = [
            'bundle_version' => '1.0.0',
            'sdk_version' => '1.0.0',
            'core_min_version' => '1.5.0',
            'core_max_version' => null,
            'generated_at' => '2026-01-01T00:00:00Z',
            'generator' => ['name' => 'test', 'version' => '1.0.0', 'plugin' => null],
            'organization' => ['external_id' => 'test-org', 'name' => 'Test Organization'],
            'source' => [
                'type' => 'unknown',
                'product' => null,
                'version' => null,
                'inventory_hash' => str_repeat('0', 64),
            ],
            'files' => [],
        ];
        file_put_contents($dir . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);

        $checksums = [];
        foreach (glob($dir . '/*') as $path) {
            if (basename($path) === 'checksum.sha256') {
                continue;
            }
            $checksums[] = strtoupper(hash_file('sha256', $path)) . '  ' . basename($path);
        }
        sort($checksums);
        file_put_contents($dir . '/checksum.sha256', implode(PHP_EOL, $checksums) . PHP_EOL);

        $zipPath = $dir . '/migration-package.zip';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach (glob($dir . '/*') as $path) {
            if ($path === $zipPath) {
                continue;
            }
            $zip->addFile($path, basename($path));
        }
        $zip->close();

        return new UploadedFile($zipPath, 'migration-package.zip', 'application/zip', null, true);
    }
}
