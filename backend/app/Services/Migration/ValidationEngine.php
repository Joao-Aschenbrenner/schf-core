<?php

namespace App\Services\Migration;

use Illuminate\Support\Facades\Log;

class ValidationEngine
{
    public function validate(array $data, array $rules): array
    {
        $errors = [];
        $warnings = [];
        $validated = [];

        foreach ($data as $index => $row) {
            $rowErrors = [];
            $rowWarnings = [];

            foreach ($rules as $field => $fieldRules) {
                $value = $row[$field] ?? null;

                if (isset($fieldRules['required']) && $fieldRules['required'] && empty($value)) {
                    $rowErrors[$field] = "Campo obrigatório ausente";
                    continue;
                }

                if (empty($value) && !isset($fieldRules['required'])) {
                    continue;
                }

                if (isset($fieldRules['type'])) {
                    if (!$this->validateType($value, $fieldRules['type'])) {
                        $rowErrors[$field] = "Tipo inválido: esperado {$fieldRules['type']}";
                        continue;
                    }
                }

                if (isset($fieldRules['min_length']) && strlen($value) < $fieldRules['min_length']) {
                    $rowErrors[$field] = "Mínimo {$fieldRules['min_length']} caracteres";
                }

                if (isset($fieldRules['max_length']) && strlen($value) > $fieldRules['max_length']) {
                    $rowWarnings[$field] = "Truncado para {$fieldRules['max_length']} caracteres";
                    $row[$field] = substr($value, 0, $fieldRules['max_length']);
                }

                if (isset($fieldRules['pattern']) && !preg_match($fieldRules['pattern'], $value)) {
                    $rowErrors[$field] = "Formato inválido";
                }

                if (isset($fieldRules['enum']) && !in_array($value, $fieldRules['enum'])) {
                    $rowErrors[$field] = "Valor não permitido: {$value}";
                }

                if (isset($fieldRules['unique'])) {
                    if (!$this->checkUnique($fieldRules['unique'], $value)) {
                        $rowWarnings[$field] = "Registro duplicado detectado";
                    }
                }
            }

            if (!empty($rowErrors)) {
                $errors[$index] = [
                    'row' => $index,
                    'errors' => $rowErrors,
                    'data' => $row,
                ];
            } else {
                $validated[$index] = $row;
            }

            if (!empty($rowWarnings)) {
                $warnings[$index] = [
                    'row' => $index,
                    'warnings' => $rowWarnings,
                ];
            }
        }

        return [
            'valid' => empty($errors),
            'total_rows' => count($data),
            'valid_rows' => count($validated),
            'invalid_rows' => count($errors),
            'rows_with_warnings' => count($warnings),
            'validated' => $validated,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    public function validateFile(string $filePath, array $rules, string $format = 'csv'): array
    {
        if (!file_exists($filePath)) {
            return [
                'valid' => false,
                'errors' => ['Arquivo não encontrado: ' . $filePath],
            ];
        }

        $data = match ($format) {
            'csv' => $this->parseCsv($filePath),
            'json' => $this->parseJson($filePath),
            default => [],
        };

        return $this->validate($data, $rules);
    }

    protected function validateType(mixed $value, string $type): bool
    {
        return match ($type) {
            'string' => is_string($value),
            'integer', 'int' => is_numeric($value) && (int)$value == $value,
            'float', 'double' => is_numeric($value),
            'boolean', 'bool' => is_bool($value) || in_array(strtolower($value), ['true', 'false', '1', '0']),
            'date' => strtotime($value) !== false,
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'cnpj' => $this->validateCnpj($value),
            'cpf' => $this->validateCpf($value),
            default => true,
        };
    }

    protected function validateCnpj(string $cnpj): bool
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);
        if (strlen($cnpj) !== 14) return false;

        if (preg_match('/^(\d)\1{13}$/', $cnpj)) return false;

        $sum = 0;
        $weights = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        for ($i = 0; $i < 12; $i++) {
            $sum += $cnpj[$i] * $weights[$i];
        }
        $remainder = $sum % 11;
        $digit = $remainder < 2 ? 0 : 11 - $remainder;
        if ($cnpj[12] != $digit) return false;

        $sum = 0;
        $weights = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        for ($i = 0; $i < 13; $i++) {
            $sum += $cnpj[$i] * $weights[$i];
        }
        $remainder = $sum % 11;
        $digit = $remainder < 2 ? 0 : 11 - $remainder;

        return $cnpj[13] == $digit;
    }

    protected function validateCpf(string $cpf): bool
    {
        $cpf = preg_replace('/\D/', '', $cpf);
        if (strlen($cpf) !== 11) return false;
        if (preg_match('/^(\d)\1{10}$/', $cpf)) return false;

        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += $cpf[$i] * (10 - $i);
        }
        $remainder = $sum % 11;
        $digit = $remainder < 2 ? 0 : 11 - $remainder;
        if ($cpf[9] != $digit) return false;

        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += $cpf[$i] * (11 - $i);
        }
        $remainder = $sum % 11;
        $digit = $remainder < 2 ? 0 : 11 - $remainder;

        return $cpf[10] == $digit;
    }

    protected function checkUnique(string $table, mixed $value): bool
    {
        return true;
    }

    protected function parseCsv(string $filePath): array
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

    protected function parseJson(string $filePath): array
    {
        $content = file_get_contents($filePath);
        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }
}