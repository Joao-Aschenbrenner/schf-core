<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\ExpenseCategory;
use App\Models\Organization;
use App\Models\Payable;
use App\Models\Supplier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

class MigrationBundleImportService
{
    private const REQUIRED_FILES = [
        'manifest.json',
        'organization.json',
        'users.json',
        'roles.json',
        'permissions.json',
        'suppliers.json',
        'accounts.json',
        'banks.json',
        'categories.json',
        'payments.json',
        'expenses.json',
        'report.json',
        'checksum.sha256',
    ];

    public function validate(UploadedFile $bundle): array
    {
        return $this->withExtractedBundle($bundle, function (string $dir) {
            return $this->validateExtractedBundle($dir);
        });
    }

    public function preview(UploadedFile $bundle): array
    {
        return $this->withExtractedBundle($bundle, function (string $dir) {
            $validation = $this->validateExtractedBundle($dir);
            if (! $validation['valid']) {
                return $validation;
            }

            return [
                'valid' => true,
                'manifest' => $validation['manifest'],
                'summary' => $this->summarize($dir),
                'warnings' => $validation['warnings'],
                'errors' => [],
            ];
        });
    }

    public function import(UploadedFile $bundle, ?int $operatorId = null): array
    {
        return $this->withExtractedBundle($bundle, function (string $dir) use ($operatorId) {
            $validation = $this->validateExtractedBundle($dir);
            if (! $validation['valid']) {
                return $validation;
            }

            return DB::transaction(function () use ($dir, $validation, $operatorId) {
                $report = [
                    'valid' => true,
                    'manifest' => $validation['manifest'],
                    'summary' => $this->summarize($dir),
                    'imported' => [],
                    'skipped' => [],
                    'warnings' => $validation['warnings'],
                    'errors' => [],
                    'operator_id' => $operatorId,
                ];

                $organization = $this->importOrganization($this->loadJson($dir, 'organization.json'));
                $report['imported']['organization'] = $organization->id;

                $supplierMap = $this->importSuppliers($this->loadJson($dir, 'suppliers.json'));
                $report['imported']['suppliers'] = count($supplierMap);

                $categoryMap = $this->importCategories($this->loadJson($dir, 'categories.json'));
                $report['imported']['categories'] = count($categoryMap);

                $bankMap = $this->loadBankMap($this->loadJson($dir, 'banks.json'));
                $accountMap = $this->importAccounts($this->loadJson($dir, 'accounts.json'), $bankMap);
                $report['imported']['accounts'] = count($accountMap);

                $paymentResult = $this->importPayments(
                    $this->loadJson($dir, 'payments.json'),
                    $supplierMap,
                    $categoryMap,
                    $accountMap,
                    $operatorId
                );
                $report['imported']['payments'] = $paymentResult['imported'];
                $report['skipped']['payments'] = $paymentResult['skipped'];
                $report['warnings'] = [...$report['warnings'], ...$paymentResult['warnings']];

                $expenseResult = $this->importExpenses(
                    $this->loadJson($dir, 'expenses.json'),
                    $supplierMap,
                    $categoryMap,
                    $accountMap,
                    $operatorId
                );
                $report['imported']['expenses'] = $expenseResult['imported'];
                $report['warnings'] = [...$report['warnings'], ...$expenseResult['warnings']];

                return $report;
            });
        });
    }

    private function withExtractedBundle(UploadedFile $bundle, callable $callback): array
    {
        if (! class_exists(ZipArchive::class)) {
            return [
                'valid' => false,
                'errors' => ['PHP ZipArchive extension is required to import Migration Bundles.'],
                'warnings' => [],
            ];
        }

        $workDir = storage_path('app/migration_imports/tmp/' . (string) Str::uuid());
        File::ensureDirectoryExists($workDir);

        try {
            $this->extractSafely($bundle->getRealPath(), $workDir);

            return $callback($workDir);
        } catch (\Throwable $e) {
            return [
                'valid' => false,
                'errors' => [$e->getMessage()],
                'warnings' => [],
            ];
        } finally {
            File::deleteDirectory($workDir);
        }
    }

    private function validateExtractedBundle(string $dir): array
    {
        $errors = [];
        $warnings = [];

        foreach (self::REQUIRED_FILES as $file) {
            if (! File::exists("{$dir}/{$file}")) {
                $errors[] = "Missing required file: {$file}";
            }
        }

        if (! empty($errors)) {
            return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        $checksumErrors = $this->validateChecksums($dir);
        $errors = [...$errors, ...$checksumErrors];

        $manifest = $this->loadJson($dir, 'manifest.json');
        foreach (['bundle_version', 'sdk_version', 'core_min_version', 'generated_at', 'generator', 'organization', 'source', 'files'] as $field) {
            if (! array_key_exists($field, $manifest)) {
                $errors[] = "Missing manifest field: {$field}";
            }
        }

        if (($manifest['bundle_version'] ?? null) !== '1.0.0') {
            $warnings[] = 'Bundle version is not 1.0.0. Import will continue only if compatible.';
        }

        return [
            'valid' => empty($errors),
            'manifest' => $manifest,
            'summary' => $this->summarize($dir),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    private function extractSafely(string $zipPath, string $targetDir): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('Invalid or unreadable Migration Bundle ZIP.');
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);
            $safePath = $this->safePath($entryName);
            $targetPath = $targetDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $safePath);

            if (str_ends_with($entryName, '/')) {
                File::ensureDirectoryExists($targetPath);
                continue;
            }

            File::ensureDirectoryExists(dirname($targetPath));
            $source = $zip->getStream($entryName);
            if (! $source) {
                throw new \RuntimeException("Unable to read ZIP entry: {$entryName}");
            }

            $destination = fopen($targetPath, 'wb');
            stream_copy_to_stream($source, $destination);
            fclose($source);
            fclose($destination);
        }

        $zip->close();
    }

    private function safePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);

        if ($path === '' || str_starts_with($path, '/') || str_contains($path, '..') || str_contains($path, ':')) {
            throw new \RuntimeException("Unsafe bundle path: {$path}");
        }

        return $path;
    }

    private function validateChecksums(string $dir): array
    {
        $errors = [];
        $lines = preg_split('/\r\n|\r|\n/', trim(File::get("{$dir}/checksum.sha256")));

        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }

            if (! preg_match('/^([A-Fa-f0-9]{64})\s+(.+)$/', trim($line), $matches)) {
                $errors[] = "Invalid checksum line: {$line}";
                continue;
            }

            $expected = strtoupper($matches[1]);
            $relative = $this->safePath(trim($matches[2]));
            $path = "{$dir}/{$relative}";

            if (! File::exists($path)) {
                $errors[] = "Checksum references missing file: {$relative}";
                continue;
            }

            $actual = strtoupper(hash_file('sha256', $path));
            if ($actual !== $expected) {
                $errors[] = "Checksum mismatch: {$relative}";
            }
        }

        return $errors;
    }

    private function summarize(string $dir): array
    {
        $summary = [];
        foreach (self::REQUIRED_FILES as $file) {
            if (! str_ends_with($file, '.json') || ! File::exists("{$dir}/{$file}")) {
                continue;
            }

            $payload = $this->loadJson($dir, $file);
            $summary[$file] = is_array($payload) && array_is_list($payload) ? count($payload) : 1;
        }

        return $summary;
    }

    private function loadJson(string $dir, string $file): array
    {
        return json_decode(File::get("{$dir}/{$file}"), true, 512, JSON_THROW_ON_ERROR);
    }

    private function importOrganization(array $record): Organization
    {
        return Organization::firstOrCreate(
            ['name' => $record['name']],
            [
                'is_active' => true,
                'is_primary' => false,
                'settings' => ['migration_external_id' => $record['external_id'] ?? null],
            ]
        );
    }

    private function importSuppliers(array $records): array
    {
        $map = [];
        foreach ($records as $record) {
            $supplier = Supplier::updateOrCreate(
                ['legacy_id' => $record['external_id']],
                [
                    'name' => $record['name'],
                    'is_active' => $record['active'] ?? true,
                    'notes' => 'Imported from SCHF Migration Bundle',
                ]
            );
            $map[$record['external_id']] = $supplier->id;
        }

        return $map;
    }

    private function importCategories(array $records): array
    {
        $map = [];
        foreach ($records as $record) {
            $category = ExpenseCategory::updateOrCreate(
                ['legacy_id' => $record['external_id']],
                [
                    'name' => $record['name'],
                    'code' => $record['external_id'],
                    'description' => 'Imported from SCHF Migration Bundle',
                    'is_allowed_by_default' => true,
                    'is_active' => true,
                ]
            );
            $map[$record['external_id']] = $category->id;
        }

        return $map;
    }

    private function loadBankMap(array $records): array
    {
        $map = [];
        foreach ($records as $record) {
            $map[$record['external_id']] = $record;
        }

        return $map;
    }

    private function importAccounts(array $records, array $bankMap): array
    {
        $map = [];
        foreach ($records as $record) {
            $bank = $bankMap[$record['bank_external_id'] ?? ''] ?? [];
            $account = BankAccount::updateOrCreate(
                ['legacy_id' => $record['external_id']],
                [
                    'bank_code' => $bank['code'] ?? null,
                    'bank_name' => $bank['name'] ?? ($record['name'] ?? 'Imported account'),
                    'agency' => data_get($record, 'metadata.agency'),
                    'account' => data_get($record, 'metadata.account_number', $record['external_id']),
                    'type' => $this->bankAccountType($record['type'] ?? 'other'),
                    'holder_name' => data_get($record, 'metadata.holder_name'),
                    'current_balance' => $record['opening_balance'] ?? 0,
                    'is_active' => true,
                ]
            );
            $map[$record['external_id']] = $account->id;
        }

        return $map;
    }

    private function importPayments(array $records, array $supplierMap, array $categoryMap, array $accountMap, ?int $operatorId): array
    {
        $imported = 0;
        $skipped = 0;
        $warnings = [];

        foreach ($records as $record) {
            if (($record['direction'] ?? null) !== 'payable') {
                $skipped++;
                $warnings[] = "Skipped receivable payment {$record['external_id']} - receivables import is not enabled yet.";
                continue;
            }

            Payable::updateOrCreate(
                ['legacy_id' => $record['external_id']],
                [
                    'description' => $record['description'] ?? 'Imported payable',
                    'supplier_id' => $supplierMap[$record['supplier_external_id'] ?? ''] ?? null,
                    'expense_category_id' => $categoryMap[$record['category_external_id'] ?? ''] ?? null,
                    'bank_account_id' => $accountMap[$record['account_external_id'] ?? ''] ?? null,
                    'amount' => $record['amount'],
                    'paid_amount' => ($record['status'] ?? null) === 'paid' ? $record['amount'] : 0,
                    'due_date' => $record['due_date'],
                    'payment_date' => $record['paid_at'] ?? null,
                    'paid_at' => $record['paid_at'] ?? null,
                    'status' => $this->payableStatus($record['status'] ?? 'pending'),
                    'notes' => 'Imported from SCHF Migration Bundle',
                    'created_by' => $operatorId,
                ]
            );
            $imported++;
        }

        return compact('imported', 'skipped', 'warnings');
    }

    private function importExpenses(array $records, array $supplierMap, array $categoryMap, array $accountMap, ?int $operatorId): array
    {
        $warnings = [];
        $imported = 0;

        foreach ($records as $record) {
            Payable::updateOrCreate(
                ['legacy_id' => $record['external_id']],
                [
                    'description' => $record['description'] ?? 'Imported expense',
                    'supplier_id' => $supplierMap[$record['supplier_external_id'] ?? ''] ?? null,
                    'expense_category_id' => $categoryMap[$record['category_external_id'] ?? ''] ?? null,
                    'bank_account_id' => $accountMap[$record['account_external_id'] ?? ''] ?? null,
                    'amount' => $record['amount'],
                    'paid_amount' => $record['amount'],
                    'due_date' => $record['date'],
                    'payment_date' => $record['date'],
                    'paid_at' => $record['date'],
                    'status' => 'paid',
                    'notes' => 'Imported expense from SCHF Migration Bundle',
                    'created_by' => $operatorId,
                ]
            );
            $imported++;
        }

        return compact('imported', 'warnings');
    }

    private function payableStatus(string $status): string
    {
        return match ($status) {
            'paid' => 'paid',
            'cancelled' => 'cancelled',
            default => 'pending',
        };
    }

    private function bankAccountType(string $type): string
    {
        return match ($type) {
            'investment' => 'investment',
            'savings' => 'savings',
            default => 'checking',
        };
    }
}
