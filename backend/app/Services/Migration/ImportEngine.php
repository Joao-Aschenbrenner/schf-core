<?php

namespace App\Services\Migration;

use Illuminate\Support\Facades\Log;

class ImportEngine
{
    protected ValidationEngine $validator;

    public function __construct(ValidationEngine $validator)
    {
        $this->validator = $validator;
    }

    public function import(MigrationManifest $manifest, array $data): array
    {
        $startTime = microtime(true);
        $imported = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];

        $validationRules = $manifest->getValidationRules();
        $transformations = $manifest->getTransformations();

        foreach ($manifest->getTables() as $tableConfig) {
            $sourceTable = $tableConfig['source'] ?? null;
            $targetModel = $manifest->getTargetModel($sourceTable);

            if (!$sourceTable || !$targetModel) {
                continue;
            }

            $tableData = $data[$sourceTable] ?? [];
            $tableRules = $validationRules[$sourceTable] ?? [];

            if (!empty($tableRules)) {
                $validationResult = $this->validator->validate($tableData, $tableRules);
                if (!$validationResult['valid']) {
                    $errors[$sourceTable] = $validationResult['errors'];
                    $failed += count($validationResult['errors']);
                    $tableData = $validationResult['validated'];
                }
            }

            $tableTransforms = $transformations[$sourceTable] ?? [];
            $tableData = $this->applyTransformations($tableData, $tableTransforms);

            $fieldMap = $manifest->getFieldMap($sourceTable);

            foreach ($tableData as $index => $row) {
                try {
                    $mappedData = $this->mapFields($row, $fieldMap);
                    $this->upsertRecord($targetModel, $mappedData, $tableConfig);
                    $imported++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[$sourceTable][$index] = $e->getMessage();
                    Log::error('Erro ao importar registro', [
                        'table' => $sourceTable,
                        'row' => $index,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $duration = round(microtime(true) - $startTime, 2);

        return [
            'success' => $failed === 0,
            'imported' => $imported,
            'skipped' => $skipped,
            'failed' => $failed,
            'duration_seconds' => $duration,
            'errors' => $errors,
        ];
    }

    public function importFile(MigrationManifest $manifest, string $filePath): array
    {
        $format = $this->detectFormat($filePath);
        $data = $this->loadFile($filePath, $format);

        if (empty($data)) {
            return [
                'success' => false,
                'message' => 'Nenhum dado encontrado no arquivo',
            ];
        }

        return $this->import($manifest, $data);
    }

    protected function mapFields(array $row, array $fieldMap): array
    {
        $mapped = [];
        foreach ($fieldMap as $sourceField => $targetField) {
            if (isset($row[$sourceField])) {
                $mapped[$targetField] = $row[$sourceField];
            }
        }
        return $mapped;
    }

    protected function applyTransformations(array $data, array $transformations): array
    {
        if (empty($transformations)) {
            return $data;
        }

        foreach ($data as &$row) {
            foreach ($transformations as $field => $transform) {
                if (!isset($row[$field])) continue;

                $value = $row[$field];

                if (isset($transform['trim'])) {
                    $value = trim($value);
                }

                if (isset($transform['upper'])) {
                    $value = strtoupper($value);
                }

                if (isset($transform['lower'])) {
                    $value = strtolower($value);
                }

                if (isset($transform['replace'])) {
                    foreach ($transform['replace'] as $search => $replace) {
                        $value = str_replace($search, $replace, $value);
                    }
                }

                if (isset($transform['format'])) {
                    $value = $this->formatValue($value, $transform['format']);
                }

                if (isset($transform['default']) && empty($value)) {
                    $value = $transform['default'];
                }

                if (isset($transform['map'])) {
                    $value = $transform['map'][$value] ?? $value;
                }

                $row[$field] = $value;
            }
        }
        unset($row);

        return $data;
    }

    protected function formatValue(mixed $value, string $format): mixed
    {
        return match ($format) {
            'cnpj' => preg_replace('/\D/', '', $value),
            'cpf' => preg_replace('/\D/', '', $value),
            'phone' => preg_replace('/\D/', '', $value),
            'currency' => (float) str_replace([',', '.'], ['.', ','], $value),
            'date_br' => $this->convertBrDate($value),
            default => $value,
        };
    }

    protected function convertBrDate(string $date): ?string
    {
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $date, $matches)) {
            return "{$matches[3]}-{$matches[2]}-{$matches[1]}";
        }
        return $date;
    }

    protected function upsertRecord(string $modelClass, array $data, array $tableConfig): void
    {
        $uniqueKey = $tableConfig['unique_key'] ?? 'id';
        $uniqueValue = $data[$uniqueKey] ?? null;

        if ($uniqueKey !== 'id' && $uniqueValue) {
            $existing = $modelClass::where($uniqueKey, $uniqueValue)->first();
            if ($existing) {
                $existing->update($data);
                return;
            }
        }

        $modelClass::create($data);
    }

    protected function detectFormat(string $filePath): string
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        return match ($ext) {
            'csv' => 'csv',
            'json' => 'json',
            'xlsx', 'xls' => 'excel',
            default => 'unknown',
        };
    }

    protected function loadFile(string $filePath, string $format): array
    {
        return match ($format) {
            'csv' => $this->loadCsv($filePath),
            'json' => $this->loadJson($filePath),
            default => [],
        };
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