<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\ExpenseCategory;
use App\Models\Organization;
use App\Models\Payable;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use SCHF\SDK\Bundle\Contract;
use SCHF\SDK\Bundle\Doctor;
use SCHF\SDK\Bundle\Inspector;
use SCHF\SDK\Bundle\Validator as BundleValidator;
use SCHF\SDK\Bundle\Verifier;
use Spatie\Permission\Models\Role;

class MigrationBundleImportService
{
    public function validate(UploadedFile $bundle): array
    {
        return $this->withSdkBundle($bundle, function (string $dir, array $validation, array $diagnostics) {
            return $this->validatedResponse($dir, $validation, $diagnostics);
        });
    }

    public function preview(UploadedFile $bundle): array
    {
        return $this->withSdkBundle($bundle, function (string $dir, array $validation, array $diagnostics) {
            return $this->validatedResponse($dir, $validation, $diagnostics);
        });
    }

    public function import(UploadedFile $bundle, ?int $operatorId = null): array
    {
        return $this->withSdkBundle($bundle, function (string $dir, array $validation, array $diagnostics) use ($operatorId) {
            return DB::transaction(function () use ($dir, $validation, $diagnostics, $operatorId) {
                $report = [
                    'valid' => true,
                    'manifest' => $validation['manifest'],
                    'summary' => $this->summarize($dir),
                    'doctor' => $diagnostics['doctor'],
                    'inspection' => $diagnostics['inspection'],
                    'signature' => $diagnostics['signature'],
                    'imported' => [],
                    'skipped' => [],
                    'warnings' => $this->warnings($validation, $diagnostics),
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

                $userResult = $this->importUsers($this->loadJson($dir, 'users.json'), $organization);
                $report['imported']['users'] = $userResult['imported'];
                $report['warnings'] = [...$report['warnings'], ...$userResult['warnings']];

                return $report;
            });
        });
    }

    private function withSdkBundle(UploadedFile $bundle, callable $callback): array
    {
        $workDir = storage_path('app/migration_imports/tmp/' . (string) Str::uuid());
        File::ensureDirectoryExists($workDir);

        if ($this->extension($bundle) !== Contract::EXTENSION) {
            File::deleteDirectory($workDir);

            return [
                'valid' => false,
                'manifest' => null,
                'summary' => null,
                'warnings' => [],
                'errors' => ['Migration Bundle must use the .schf extension.'],
            ];
        }

        $bundlePath = $workDir . DIRECTORY_SEPARATOR . 'migration-package.' . Contract::EXTENSION;

        try {
            File::copy($bundle->getRealPath(), $bundlePath);

            $validator = new BundleValidator();
            $validation = $validator->validate($bundlePath);
            $diagnostics = $this->diagnose($bundlePath);

            if (! $validation['valid']) {
                return [
                    'valid' => false,
                    'manifest' => $validation['manifest'],
                    'summary' => null,
                    'doctor' => $diagnostics['doctor'],
                    'inspection' => $diagnostics['inspection'],
                    'signature' => $diagnostics['signature'],
                    'warnings' => $this->warnings($validation, $diagnostics),
                    'errors' => $validation['errors'],
                ];
            }

            if ($signatureError = $this->signatureError($validator->getExtractDir(), $diagnostics['signature'])) {
                return [
                    'valid' => false,
                    'manifest' => $validation['manifest'],
                    'summary' => $this->summarize($validator->getExtractDir()),
                    'doctor' => $diagnostics['doctor'],
                    'inspection' => $diagnostics['inspection'],
                    'signature' => $diagnostics['signature'],
                    'warnings' => $this->warnings($validation, $diagnostics),
                    'errors' => [$signatureError],
                ];
            }

            return $callback($validator->getExtractDir(), $validation, $diagnostics);
        } catch (\Throwable $e) {
            return [
                'valid' => false,
                'manifest' => null,
                'summary' => null,
                'warnings' => [],
                'errors' => [$e->getMessage()],
            ];
        } finally {
            File::deleteDirectory($workDir);
        }
    }

    private function diagnose(string $bundlePath): array
    {
        $inspector = new Inspector();
        $inspection = $inspector->open($bundlePath);
        $inspector->close();

        return [
            'doctor' => (new Doctor())->diagnose($bundlePath, true),
            'inspection' => $inspection,
            'signature' => (new Verifier())->verify($bundlePath),
        ];
    }

    private function validatedResponse(string $dir, array $validation, array $diagnostics): array
    {
        return [
            'valid' => true,
            'manifest' => $validation['manifest'],
            'summary' => $this->summarize($dir),
            'doctor' => $diagnostics['doctor'],
            'inspection' => $diagnostics['inspection'],
            'signature' => $diagnostics['signature'],
            'warnings' => $this->warnings($validation, $diagnostics),
            'errors' => [],
        ];
    }

    private function warnings(array $validation, array $diagnostics): array
    {
        $warnings = $validation['warnings'];
        $signature = $diagnostics['signature'] ?? [];

        if (($signature['verified'] ?? false) === false && ($signature['error'] ?? null) === 'Bundle is not signed (no signature.sig found)') {
            $warnings[] = 'Bundle is unsigned; import will continue for synthetic SDK bundles.';
        }

        return array_values(array_unique($warnings));
    }

    private function signatureError(string $dir, array $signature): ?string
    {
        if (! File::exists("{$dir}/signature.sig")) {
            return null;
        }

        if (($signature['verified'] ?? false) === true) {
            return null;
        }

        return 'Bundle signature could not be verified: ' . ($signature['error'] ?? 'unknown verification error');
    }

    private function extension(UploadedFile $bundle): string
    {
        return strtolower($bundle->getClientOriginalExtension());
    }

    private function summarize(string $dir): array
    {
        $summary = [];
        foreach (Contract::REQUIRED_FILES as $file) {
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
                ['legacy_id' => $this->legacyId($record['external_id'])],
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
                ['legacy_id' => $this->legacyId($record['external_id'])],
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
                ['legacy_id' => $this->legacyId($record['external_id'])],
                [
                    'bank_code' => $bank['code'] ?? ($record['bank_code'] ?? '000'),
                    'bank_name' => $bank['name'] ?? ($record['bank_name'] ?? $record['name'] ?? 'Imported account'),
                    'agency' => data_get($record, 'metadata.agency', '0000'),
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
                ['legacy_id' => $this->legacyId($record['external_id'])],
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
                ['legacy_id' => $this->legacyId($record['external_id'])],
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

    private function importUsers(array $records, Organization $organization): array
    {
        $imported = 0;
        $warnings = [];

        foreach ($records as $record) {
            $user = User::updateOrCreate(
                ['email' => $record['email']],
                [
                    'name' => $record['name'],
                    'organization_id' => $organization->id,
                    'is_active' => $record['active'] ?? true,
                    'is_master' => false,
                    'is_system_admin' => false,
                    'password' => Hash::make(bin2hex(random_bytes(16))),
                ]
            );

            $roles = $record['roles'] ?? [];
            if (!empty($roles)) {
                try {
                    $existingRoles = $user->getRoleNames()->toArray();
                    $rolesToSync = array_diff($roles, $existingRoles);
                    if (!empty($rolesToSync)) {
                        foreach ($rolesToSync as $roleName) {
                            $roleModel = Role::query()
                                ->where('name', $roleName)
                                ->where('guard_name', 'sanctum')
                                ->first();

                            if (! $roleModel) {
                                $warnings[] = "Role '{$roleName}' does not exist for user {$record['email']}; role assignment skipped.";
                                continue;
                            }

                            $user->assignRole($roleModel);
                        }
                    }
                } catch (\Throwable $e) {
                    $warnings[] = "Role assignment failed for user {$record['email']}: {$e->getMessage()}";
                }
            }

            $imported++;
        }

        return compact('imported', 'warnings');
    }

    private function legacyId(string $externalId): int
    {
        return (int) hexdec(substr(hash('sha256', $externalId), 0, 15));
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
