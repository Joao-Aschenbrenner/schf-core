<?php

namespace App\Services\Migration;

use Illuminate\Support\Facades\Log;

class MigrationManifest
{
    protected array $manifest;
    protected string $packagePath;

    public function __construct(string $packagePath)
    {
        $this->packagePath = $packagePath;
        $this->manifest = $this->loadManifest();
    }

    public static function fromPackage(string $packagePath): ?self
    {
        $manifestPath = $packagePath . '/migration-manifest.json';
        if (!file_exists($manifestPath)) {
            Log::error('Manifest não encontrado', ['path' => $manifestPath]);
            return null;
        }

        return new self($packagePath);
    }

    public function getName(): string
    {
        return $this->manifest['name'] ?? 'unknown';
    }

    public function getVersion(): string
    {
        return $this->manifest['version'] ?? '0.0.0';
    }

    public function getSourceType(): string
    {
        return $this->manifest['bundle_format'] ?? 'migration_bundle';
    }

    public function getTargetCoreVersion(): string
    {
        return $this->manifest['target_core_version'] ?? '*';
    }

    public function getTargetCoreMin(): string
    {
        return $this->manifest['target_core_min'] ?? '0.0.0';
    }

    public function getTargetCoreMax(): string
    {
        return $this->manifest['target_core_max'] ?? '99.99.99';
    }

    public function getDescription(): string
    {
        return $this->manifest['description'] ?? '';
    }

    public function getAuthor(): string
    {
        return $this->manifest['author'] ?? '';
    }

    public function getLicense(): string
    {
        return $this->manifest['license'] ?? 'proprietary';
    }

    public function getDependencies(): array
    {
        return $this->manifest['dependencies'] ?? [];
    }

    public function getSourceConfig(): array
    {
        return $this->manifest['source_config'] ?? [];
    }

    public function getTables(): array
    {
        return $this->manifest['tables'] ?? [];
    }

    public function getFieldMappings(): array
    {
        return $this->manifest['field_mappings'] ?? [];
    }

    public function getValidationRules(): array
    {
        return $this->manifest['validation_rules'] ?? [];
    }

    public function getTransformations(): array
    {
        return $this->manifest['transformations'] ?? [];
    }

    public function getRequiredFields(): array
    {
        return $this->manifest['required_fields'] ?? [];
    }

    public function getOptionalFields(): array
    {
        return $this->manifest['optional_fields'] ?? [];
    }

    public function getTargetModel(string $sourceTable): ?string
    {
        $mappings = $this->getFieldMappings();
        return $mappings[$sourceTable]['model'] ?? null;
    }

    public function getFieldMap(string $sourceTable): array
    {
        $mappings = $this->getFieldMappings();
        return $mappings[$sourceTable]['fields'] ?? [];
    }

    public function getTransformationsFor(string $sourceTable): array
    {
        return $this->getTransformations()[$sourceTable] ?? [];
    }

    public function getValidationRulesFor(string $sourceTable): array
    {
        return $this->getValidationRules()[$sourceTable] ?? [];
    }

    public function toArray(): array
    {
        return $this->manifest;
    }

    public function isValid(): bool
    {
        $required = ['name', 'version', 'tables', 'field_mappings'];
        foreach ($required as $key) {
            if (!isset($this->manifest[$key])) {
                return false;
            }
        }
        return true;
    }

    protected function loadManifest(): array
    {
        $manifestPath = $this->packagePath . '/migration-manifest.json';
        $content = file_get_contents($manifestPath);

        if ($content === false) {
            Log::error('Erro ao ler manifest', ['path' => $manifestPath]);
            return [];
        }

        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('JSON inválido no manifest', [
                'error' => json_last_error_msg(),
                'path' => $manifestPath,
            ]);
            return [];
        }

        return $data;
    }
}
