<?php

namespace Tests\Unit;

use App\Models\Payable;
use App\Services\MigrationBundleImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use SCHF\SDK\Bundle\Builder;
use SCHF\SDK\Bundle\Contract;
use Tests\TestCase;

class MigrationBundleImportServiceTest extends TestCase
{
    use RefreshDatabase;

    private array $bundlePaths = [];

    protected function tearDown(): void
    {
        foreach ($this->bundlePaths as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }

        parent::tearDown();
    }

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

    public function test_it_imports_a_synthetic_sdk_bundle(): void
    {
        $file = $this->createBundle([
            'suppliers.json' => [
                ['external_id' => 'SUP-001', 'name' => 'Synthetic Supplier Alpha', 'active' => true],
            ],
            'categories.json' => [
                ['external_id' => 'CAT-001', 'name' => 'Synthetic Services', 'type' => 'expense'],
            ],
            'accounts.json' => [
                [
                    'external_id' => 'ACC-001',
                    'name' => 'Synthetic Operating Account',
                    'bank_name' => 'SYNBANK',
                    'opening_balance' => 1000,
                    'metadata' => ['agency' => '0001', 'account_number' => 'SYN-0001'],
                ],
            ],
            'payments.json' => [
                [
                    'external_id' => 'PAY-001',
                    'direction' => 'payable',
                    'supplier_external_id' => 'SUP-001',
                    'category_external_id' => 'CAT-001',
                    'account_external_id' => 'ACC-001',
                    'description' => 'Synthetic service invoice',
                    'amount' => 150,
                    'due_date' => '2026-07-10',
                    'status' => 'pending',
                ],
            ],
            'expenses.json' => [
                [
                    'external_id' => 'EXP-001',
                    'category_external_id' => 'CAT-001',
                    'account_external_id' => 'ACC-001',
                    'description' => 'Synthetic office expense',
                    'amount' => 10,
                    'date' => '2026-07-01',
                ],
            ],
        ]);
        $service = app(MigrationBundleImportService::class);

        $result = $service->import($file);

        $this->assertTrue($result['valid']);
        $this->assertSame(1, $result['imported']['suppliers']);
        $this->assertSame(1, $result['imported']['categories']);
        $this->assertSame(1, $result['imported']['accounts']);
        $this->assertSame(1, $result['imported']['payments']);
        $this->assertSame(1, $result['imported']['expenses']);
        $this->assertDatabaseHas('suppliers', ['name' => 'Synthetic Supplier Alpha']);
        $this->assertDatabaseHas('expense_categories', ['code' => 'CAT-001']);
        $this->assertDatabaseHas('bank_accounts', ['account' => 'SYN-0001']);
        $this->assertSame(2, Payable::count());
    }

    public function test_it_imports_users_from_bundle(): void
    {
        \Spatie\Permission\Models\Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);

        $file = $this->createBundle([
            'users.json' => [
                [
                    'external_id' => 'USR-S11-001',
                    'name' => 'Synthetic User A',
                    'email' => 'synthetic.user.a@sprint11.local',
                    'roles' => ['admin'],
                    'active' => true,
                ],
            ],
            'suppliers.json' => [
                ['external_id' => 'SUP-S11-001', 'name' => 'Synthetic Supplier A', 'active' => true],
            ],
            'categories.json' => [
                ['external_id' => 'CAT-S11-001', 'name' => 'Synthetic Services', 'type' => 'expense'],
            ],
            'accounts.json' => [
                [
                    'external_id' => 'ACC-S11-001',
                    'name' => 'Synthetic Operating Account',
                    'bank_name' => 'SYNBANK',
                    'opening_balance' => 1000,
                    'metadata' => ['agency' => '0001', 'account_number' => 'SYN-0001'],
                ],
            ],
            'payments.json' => [
                [
                    'external_id' => 'PAY-S11-001',
                    'direction' => 'payable',
                    'supplier_external_id' => 'SUP-S11-001',
                    'category_external_id' => 'CAT-S11-001',
                    'account_external_id' => 'ACC-S11-001',
                    'description' => 'Synthetic payable A',
                    'amount' => 100,
                    'due_date' => '2026-08-01',
                    'status' => 'pending',
                ],
            ],
            'expenses.json' => [
                [
                    'external_id' => 'EXP-S11-001',
                    'category_external_id' => 'CAT-S11-001',
                    'account_external_id' => 'ACC-S11-001',
                    'description' => 'Synthetic expense A',
                    'amount' => 25,
                    'date' => '2026-08-01',
                ],
            ],
        ]);

        $service = app(MigrationBundleImportService::class);
        $result = $service->import($file);

        $this->assertTrue($result['valid']);
        $this->assertSame(1, $result['imported']['users']);
        $this->assertSame(1, $result['imported']['suppliers']);
        $this->assertSame(1, $result['imported']['categories']);
        $this->assertSame(1, $result['imported']['accounts']);

        $this->assertDatabaseHas('users', [
            'email' => 'synthetic.user.a@sprint11.local',
            'name' => 'Synthetic User A',
            'is_active' => true,
            'is_master' => false,
            'is_system_admin' => false,
        ]);

        $importedUser = \App\Models\User::where('email', 'synthetic.user.a@sprint11.local')->first();
        $this->assertNotNull($importedUser);
        $this->assertNotNull($importedUser->organization_id);
        $this->assertTrue($importedUser->hasRole('admin'));
    }

    public function test_it_warns_when_bundle_user_role_is_missing(): void
    {
        $file = $this->createBundle([
            'users.json' => [
                [
                    'external_id' => 'USR-S11-001',
                    'name' => 'Synthetic User A',
                    'email' => 'synthetic.user.a@sprint11.local',
                    'roles' => ['admin'],
                    'active' => true,
                ],
            ],
        ]);

        $service = app(MigrationBundleImportService::class);
        $result = $service->import($file);

        $this->assertTrue($result['valid']);
        $this->assertSame(1, $result['imported']['users']);
        $this->assertNotEmpty($result['warnings']);
        $this->assertStringContainsString("Role 'admin' does not exist", implode('\n', $result['warnings']));

        $importedUser = \App\Models\User::where('email', 'synthetic.user.a@sprint11.local')->first();
        $this->assertNotNull($importedUser);
        $this->assertFalse($importedUser->hasRole('admin'));
    }

    public function test_it_rejects_bundle_with_invalid_extension(): void
    {
        $bundle = $this->createBundle();
        $file = new UploadedFile($bundle->getRealPath(), 'migration-package.zip', 'application/zip', null, true);

        $result = app(MigrationBundleImportService::class)->preview($file);

        $this->assertFalse($result['valid']);
        $this->assertContains('Migration Bundle must use the .schf extension.', $result['errors']);
    }

    public function test_it_rejects_bundle_with_corrupted_checksum(): void
    {
        $file = $this->createBundle([
            'users.json' => [
                [
                    'external_id' => 'USR-S11-001',
                    'name' => 'Synthetic User A',
                    'email' => 'synthetic.user.a@sprint11.local',
                    'roles' => ['admin'],
                    'active' => true,
                ],
            ],
        ]);
        $this->replaceZipEntry($file->getRealPath(), 'users.json', json_encode([
            [
                'external_id' => 'USR-S11-002',
                'name' => 'Tampered User',
                'email' => 'tampered@sprint11.local',
                'roles' => ['admin'],
                'active' => true,
            ],
        ], JSON_THROW_ON_ERROR));

        $result = app(MigrationBundleImportService::class)->preview($file);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Checksum mismatch: users.json', implode('\n', $result['errors']));
    }

    public function test_it_rejects_bundle_without_users_file(): void
    {
        $file = $this->createBundle([
            'users.json' => [
                [
                    'external_id' => 'USR-S11-001',
                    'name' => 'Synthetic User A',
                    'email' => 'synthetic.user.a@sprint11.local',
                    'roles' => ['admin'],
                    'active' => true,
                ],
            ],
        ]);
        $this->deleteZipEntry($file->getRealPath(), 'users.json');

        $result = app(MigrationBundleImportService::class)->preview($file);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Missing required file: users.json', implode('\n', $result['errors']));
    }

    public function test_it_rejects_bundle_with_invalid_users_json(): void
    {
        $file = $this->createBundle([
            'users.json' => [
                [
                    'external_id' => 'USR-S11-001',
                    'name' => 'Synthetic User A',
                    'email' => 'synthetic.user.a@sprint11.local',
                    'roles' => ['admin'],
                    'active' => true,
                ],
            ],
        ]);
        $this->replaceZipEntry($file->getRealPath(), 'users.json', '[{"external_id":');

        $result = app(MigrationBundleImportService::class)->preview($file);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Invalid JSON in users.json', implode('\n', $result['errors']));
    }

    private function createBundle(array $records = []): UploadedFile
    {
        $builder = new Builder();
        $builder->setGenerator('core-test', '1.0.0');
        $builder->setOrganization('test-org', 'Test Organization', [
            'legal_name' => null,
            'metadata' => [],
        ]);
        $builder->setSource('synthetic', null, null, str_repeat('0', 64));

        foreach ($records as $file => $rows) {
            $builder->addRecords($file, $rows);
        }

        $path = $builder->build();
        $this->bundlePaths[] = $path;

        return new UploadedFile($path, 'migration-package.schf', Contract::MIME_TYPE, null, true);
    }

    private function replaceZipEntry(string $path, string $entry, string $content): void
    {
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($path) === true, 'Unable to open bundle ZIP');
        $zip->deleteName($entry);
        $this->assertTrue($zip->addFromString($entry, $content));
        $zip->close();
    }

    private function deleteZipEntry(string $path, string $entry): void
    {
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($path) === true, 'Unable to open bundle ZIP');
        $this->assertTrue($zip->deleteName($entry));
        $zip->close();
    }
}
