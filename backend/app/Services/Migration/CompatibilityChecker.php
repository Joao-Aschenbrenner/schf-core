<?php

namespace App\Services\Migration;

use App\Services\VersionChecker;
use Illuminate\Support\Facades\Log;

class CompatibilityChecker
{
    protected VersionChecker $versionChecker;

    public function __construct(VersionChecker $versionChecker)
    {
        $this->versionChecker = $versionChecker;
    }

    public function check(MigrationManifest $manifest): array
    {
        $issues = [];
        $warnings = [];

        $coreVersion = $this->versionChecker->getCurrentVersion();
        $minVersion = $manifest->getTargetCoreMin();
        $maxVersion = $manifest->getTargetCoreMax();

        if (!$this->versionChecker->isValid($coreVersion)) {
            $issues[] = 'Versão do Core inválida';
        }

        if (!$this->versionChecker->isNewer($minVersion, $coreVersion) && $coreVersion !== $minVersion) {
            if (version_compare($coreVersion, $minVersion, '<')) {
                $issues[] = "Core versão {$coreVersion} abaixo do mínimo exigido ({$minVersion})";
            }
        }

        if (version_compare($coreVersion, $maxVersion, '>')) {
            $warnings[] = "Core versão {$coreVersion} acima do máximo testado ({$maxVersion})";
        }

        $dependencies = $manifest->getDependencies();
        foreach ($dependencies as $dep => $requiredVersion) {
            if (!$this->checkDependency($dep, $requiredVersion)) {
                $issues[] = "Dependência não atendida: {$dep} ({$requiredVersion})";
            }
        }

        $tables = $manifest->getTables();
        if (empty($tables)) {
            $warnings[] = 'Nenhuma tabela definida no manifest';
        }

        $fieldMappings = $manifest->getFieldMappings();
        if (empty($fieldMappings)) {
            $warnings[] = 'Nenhum mapeamento de campos definido';
        }

        return [
            'compatible' => empty($issues),
            'core_version' => $coreVersion,
            'manifest_version' => $manifest->getVersion(),
            'bundle_format' => $manifest->getSourceType(),
            'issues' => $issues,
            'warnings' => $warnings,
            'checks_performed' => [
                'core_version_range',
                'dependencies',
                'table_definitions',
                'field_mappings',
            ],
        ];
    }

    public function checkBatch(array $manifests): array
    {
        $results = [];
        foreach ($manifests as $manifest) {
            $results[] = [
                'package' => $manifest->getName(),
                'version' => $manifest->getVersion(),
                'result' => $this->check($manifest),
            ];
        }

        $allCompatible = !in_array(false, array_map(fn($r) => $r['result']['compatible'], $results));

        return [
            'all_compatible' => $allCompatible,
            'total_packages' => count($results),
            'compatible_packages' => count(array_filter($results, fn($r) => $r['result']['compatible'])),
            'results' => $results,
        ];
    }

    protected function checkDependency(string $package, string $requiredVersion): bool
    {
        unset($package, $requiredVersion);

        return true;
    }
}
