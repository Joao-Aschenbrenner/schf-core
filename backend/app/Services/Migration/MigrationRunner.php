<?php

namespace App\Services\Migration;

use Illuminate\Support\Facades\Log;

class MigrationRunner
{
    protected CompatibilityChecker $compatibilityChecker;
    protected ValidationEngine $validator;
    protected ImportEngine $importer;
    protected MigrationReport $report;

    public function __construct(
        CompatibilityChecker $compatibilityChecker,
        ValidationEngine $validator,
        ImportEngine $importer
    ) {
        $this->compatibilityChecker = $compatibilityChecker;
        $this->validator = $validator;
        $this->importer = $importer;
    }

    public function run(string $packagePath): MigrationReport
    {
        $this->report = new MigrationReport();
        $startTime = microtime(true);

        $this->report->addStep('load_manifest', 'running');

        $manifest = MigrationManifest::fromPackage($packagePath);
        if (!$manifest) {
            $this->report->addStep('load_manifest', 'failed');
            $this->report->addError('Manifest não encontrado ou inválido');
            return $this->report;
        }

        if (!$manifest->isValid()) {
            $this->report->addStep('load_manifest', 'failed');
            $this->report->addError('Manifest inválido: campos obrigatórios ausentes');
            return $this->report;
        }

        $this->report->addStep('load_manifest', 'success');
        $this->report->setManifest([
            'name' => $manifest->getName(),
            'version' => $manifest->getVersion(),
            'source_type' => $manifest->getSourceType(),
        ]);

        $this->report->addStep('check_compatibility', 'running');
        $compatibility = $this->compatibilityChecker->check($manifest);
        $this->report->setCompatibility($compatibility);
        $this->report->addStep('check_compatibility', $compatibility['compatible'] ? 'success' : 'failed');

        if (!$compatibility['compatible']) {
            $this->report->addError('Pacote incompatível com o Core atual');
            return $this->report;
        }

        $this->report->addStep('load_data', 'running');
        $data = $this->loadData($packagePath, $manifest);
        $this->report->addStep('load_data', !empty($data) ? 'success' : 'failed');

        if (empty($data)) {
            $this->report->addError('Nenhum dado encontrado no pacote');
            return $this->report;
        }

        $this->report->addStep('validate_data', 'running');
        $validationResult = $this->validateData($data, $manifest);
        $this->report->setValidation($validationResult);
        $this->report->addStep('validate_data', $validationResult['valid'] ? 'success' : 'warning');

        if (!$validationResult['valid'] && !$validationResult['valid_rows']) {
            $this->report->addError('Todos os dados falharam na validação');
            return $this->report;
        }

        $this->report->addStep('import_data', 'running');
        $importResult = $this->importer->import($manifest, $validationResult['validated']);
        $this->report->setImportResult($importResult);
        $this->report->addStep('import_data', $importResult['success'] ? 'success' : 'failed');

        $duration = round(microtime(true) - $startTime, 2);

        $this->report->setSummary([
            'package' => $manifest->getName(),
            'version' => $manifest->getVersion(),
            'source_type' => $manifest->getSourceType(),
            'compatible' => $compatibility['compatible'],
            'imported' => $importResult['imported'],
            'failed' => $importResult['failed'],
            'duration_seconds' => $duration,
            'success' => $importResult['success'],
        ]);

        return $this->report;
    }

    public function runFromFile(string $packagePath, string $dataFile): MigrationReport
    {
        $this->report = new MigrationReport();

        $manifest = MigrationManifest::fromPackage($packagePath);
        if (!$manifest) {
            $this->report->addError('Manifest não encontrado');
            return $this->report;
        }

        $compatibility = $this->compatibilityChecker->check($manifest);
        $this->report->setCompatibility($compatibility);

        if (!$compatibility['compatible']) {
            $this->report->addError('Pacote incompatível');
            return $this->report;
        }

        $result = $this->importer->importFile($manifest, $dataFile);
        $this->report->setImportResult($result);

        return $this->report;
    }

    protected function loadData(string $packagePath, MigrationManifest $manifest): array
    {
        $data = [];
        $sourceConfig = $manifest->getSourceConfig();

        $dataDir = $packagePath . '/data';
        if (!is_dir($dataDir)) {
            return $data;
        }

        foreach ($manifest->getTables() as $tableConfig) {
            $source = $tableConfig['source'] ?? null;
            if (!$source) continue;

            $files = glob("{$dataDir}/{$source}.*");
            if (empty($files)) continue;

            $file = $files[0];
            $format = pathinfo($file, PATHINFO_EXTENSION);

            $data[$source] = match ($format) {
                'csv' => $this->loadCsv($file),
                'json' => $this->loadJson($file),
                default => [],
            };
        }

        return $data;
    }

    protected function validateData(array $data, MigrationManifest $manifest): array
    {
        $allValid = true;
        $totalRows = 0;
        $validRows = 0;
        $errors = [];
        $warnings = [];

        foreach ($manifest->getTables() as $tableConfig) {
            $source = $tableConfig['source'] ?? null;
            if (!$source || !isset($data[$source])) continue;

            $rules = $manifest->getValidationRulesFor($source);
            $tableData = $data[$source];
            $totalRows += count($tableData);

            if (!empty($rules)) {
                $result = $this->validator->validate($tableData, $rules);
                $validRows += $result['valid_rows'];
                if (!$result['valid']) {
                    $allValid = false;
                    $errors[$source] = $result['errors'];
                }
                if (!empty($result['warnings'])) {
                    $warnings[$source] = $result['warnings'];
                }
            } else {
                $validRows += count($tableData);
            }
        }

        return [
            'valid' => $allValid,
            'total_rows' => $totalRows,
            'valid_rows' => $validRows,
            'invalid_rows' => $totalRows - $validRows,
            'rows_with_warnings' => count($warnings),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    protected function loadCsv(string $filePath): array
    {
        $data = [];
        $handle = fopen($filePath, 'r');
        if ($handle === false) return [];

        $headers = fgetcsv($handle);
        if ($headers === false) return [];

        while (($row = fgetcsv($handle)) !== false) {
            $data[] = array_combine($headers, $row);
        }

        fclose($handle);
        return $data;
    }

    protected function loadJson(string $filePath): array
    {
        $content = file_get_contents($filePath);
        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }
}