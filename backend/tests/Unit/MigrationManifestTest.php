<?php

namespace Tests\Unit;

use App\Services\Migration\MigrationManifest;
use Tests\TestCase;

class MigrationManifestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_from_package_with_valid_manifest(): void
    {
        $tempDir = sys_get_temp_dir() . '/test_manifest_' . uniqid();
        mkdir($tempDir, 0755, true);

        $manifest = [
            'name' => 'Test Migration',
            'version' => '1.0.0',
            'bundle_format' => 'migration_bundle',
            'tables' => [],
            'field_mappings' => [],
        ];

        file_put_contents($tempDir . '/migration-manifest.json', json_encode($manifest));

        $result = MigrationManifest::fromPackage($tempDir);
        $this->assertNotNull($result);
        $this->assertEquals('Test Migration', $result->getName());
        $this->assertEquals('1.0.0', $result->getVersion());

        @unlink($tempDir . '/migration-manifest.json');
        @rmdir($tempDir);
    }

    public function test_from_package_with_invalid_path_returns_null(): void
    {
        $result = MigrationManifest::fromPackage('/nonexistent/path');
        $this->assertNull($result);
    }

    public function test_is_valid_with_required_fields(): void
    {
        $tempDir = sys_get_temp_dir() . '/test_manifest_valid_' . uniqid();
        mkdir($tempDir, 0755, true);

        $manifest = [
            'name' => 'Test Migration',
            'version' => '1.0.0',
            'bundle_format' => 'migration_bundle',
            'tables' => [['source' => 'test']],
            'field_mappings' => ['test' => ['model' => 'TestModel']],
        ];

        file_put_contents($tempDir . '/migration-manifest.json', json_encode($manifest));

        $result = MigrationManifest::fromPackage($tempDir);
        $this->assertTrue($result->isValid());

        @unlink($tempDir . '/migration-manifest.json');
        @rmdir($tempDir);
    }

    public function test_is_valid_with_missing_fields(): void
    {
        $tempDir = sys_get_temp_dir() . '/test_manifest_invalid_' . uniqid();
        mkdir($tempDir, 0755, true);

        $manifest = [
            'name' => 'Test Migration',
            'version' => '1.0.0',
        ];

        file_put_contents($tempDir . '/migration-manifest.json', json_encode($manifest));

        $result = MigrationManifest::fromPackage($tempDir);
        $this->assertFalse($result->isValid());

        @unlink($tempDir . '/migration-manifest.json');
        @rmdir($tempDir);
    }

    public function test_get_target_model_returns_string(): void
    {
        $tempDir = sys_get_temp_dir() . '/test_manifest_model_' . uniqid();
        mkdir($tempDir, 0755, true);

        $manifest = [
            'name' => 'Test Migration',
            'version' => '1.0.0',
            'bundle_format' => 'migration_bundle',
            'tables' => [],
            'field_mappings' => ['users' => ['model' => 'App\\Models\\User']],
        ];

        file_put_contents($tempDir . '/migration-manifest.json', json_encode($manifest));

        $result = MigrationManifest::fromPackage($tempDir);
        $this->assertEquals('App\\Models\\User', $result->getTargetModel('users'));

        @unlink($tempDir . '/migration-manifest.json');
        @rmdir($tempDir);
    }

    public function test_get_field_map_returns_array(): void
    {
        $tempDir = sys_get_temp_dir() . '/test_manifest_fields_' . uniqid();
        mkdir($tempDir, 0755, true);

        $manifest = [
            'name' => 'Test Migration',
            'version' => '1.0.0',
            'bundle_format' => 'migration_bundle',
            'tables' => [],
            'field_mappings' => [
                'users' => [
                    'model' => 'App\\Models\\User',
                    'fields' => ['ID' => 'id', 'NAME' => 'name'],
                ],
            ],
        ];

        file_put_contents($tempDir . '/migration-manifest.json', json_encode($manifest));

        $result = MigrationManifest::fromPackage($tempDir);
        $this->assertEquals(['ID' => 'id', 'NAME' => 'name'], $result->getFieldMap('users'));

        @unlink($tempDir . '/migration-manifest.json');
        @rmdir($tempDir);
    }
}
