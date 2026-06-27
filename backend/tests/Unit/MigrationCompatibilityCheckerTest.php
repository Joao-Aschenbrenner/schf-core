<?php

namespace Tests\Unit;

use App\Services\Migration\CompatibilityChecker;
use App\Services\Migration\MigrationManifest;
use Tests\TestCase;

class MigrationCompatibilityCheckerTest extends TestCase
{
    protected CompatibilityChecker $checker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->checker = app(CompatibilityChecker::class);
    }

    protected function createManifest(array $overrides = []): MigrationManifest
    {
        $tempDir = sys_get_temp_dir() . '/test_manifest_compat_' . uniqid();
        mkdir($tempDir, 0755, true);

        $manifest = array_merge([
            'name' => 'Test Migration',
            'version' => '1.0.0',
            'source_type' => 'firebird',
            'target_core_min' => '1.0.0',
            'target_core_max' => '2.0.0',
            'tables' => [['source' => 'test']],
            'field_mappings' => ['test' => ['model' => 'TestModel']],
        ], $overrides);

        file_put_contents($tempDir . '/migration-manifest.json', json_encode($manifest));

        return MigrationManifest::fromPackage($tempDir);
    }

    public function test_check_returns_compatible(): void
    {
        $manifest = $this->createManifest();
        $result = $this->checker->check($manifest);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('compatible', $result);
    }

    public function test_check_batch_returns_array(): void
    {
        $manifest = $this->createManifest();
        $result = $this->checker->checkBatch([$manifest]);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('all_compatible', $result);
    }
}