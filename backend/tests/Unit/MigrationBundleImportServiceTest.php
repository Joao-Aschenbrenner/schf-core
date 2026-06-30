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
}
