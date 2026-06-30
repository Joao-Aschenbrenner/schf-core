<?php

namespace Tests\Feature;

use App\Services\MigrationBundleImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use SCHF\SDK\Bundle\Builder;
use SCHF\SDK\Bundle\Contract;
use Tests\TestCase;

class Sprint10E2ETest extends TestCase
{
    use RefreshDatabase;
    private string $bundlePath;

    protected function tearDown(): void
    {
        if (isset($this->bundlePath) && file_exists($this->bundlePath)) {
            unlink($this->bundlePath);
        }

        parent::tearDown();
    }

    public function test_synth_built_sdk_bundle_passes_core_importer(): void
    {
        $dir = sys_get_temp_dir() . '/sprint10_core_e2e_' . uniqid();
        mkdir($dir, 0755, true);
        $path = $dir . '/migration-package.' . Contract::EXTENSION;
        $this->bundlePath = $path;

        $builder = new Builder();
        $builder->setGenerator('core-e2e', '1.0.0', 'sprint-10');
        $builder->setOrganization('ORG-E2E', 'Sprint 10 E2E Organization');
        $builder->setSource('synthetic', 'SyntheticFinance', '1.0.0', str_repeat('0', 64));

        $builder->addRecords('suppliers.json', [
            ['external_id' => 'SUP-001', 'name' => 'E2E Supplier Alpha', 'active' => true],
        ]);
        $builder->addRecords('categories.json', [
            ['external_id' => 'CAT-001', 'name' => 'E2E Services', 'type' => 'expense'],
        ]);
        $builder->addRecords('accounts.json', [
            [
                'external_id' => 'ACC-001',
                'name' => 'E2E Operating Account',
                'bank_name' => 'SYNBANK',
                'opening_balance' => 1000,
                'metadata' => ['agency' => '0001', 'account_number' => 'SYN-0001'],
            ],
        ]);
        $builder->addRecords('payments.json', [
            [
                'external_id' => 'PAY-001',
                'direction' => 'payable',
                'supplier_external_id' => 'SUP-001',
                'category_external_id' => 'CAT-001',
                'account_external_id' => 'ACC-001',
                'description' => 'E2E payable invoice',
                'amount' => 150,
                'due_date' => '2026-07-10',
                'status' => 'pending',
            ],
        ]);

        $tmp = $builder->build();
        rename($tmp, $path);

        $file = new UploadedFile($path, 'migration-package.schf', Contract::MIME_TYPE, null, true);

        $service = app(MigrationBundleImportService::class);
        $preview = $service->preview($file);

        fwrite(STDERR, 'SPRINT10_CORE_E2E_PREVIEW=' . json_encode([
            'valid' => $preview['valid'],
            'manifest_bundle' => $preview['manifest']['bundle_version'] ?? null,
            'manifest_sdk' => $preview['manifest']['sdk_version'] ?? null,
            'summary_keys' => array_keys($preview['summary'] ?? []),
            'payments_count' => $preview['summary']['payments.json'] ?? null,
            'doctor_ready' => $preview['doctor']['ready_to_import'] ?? null,
            'inspection_ready' => $preview['inspection']['valid'] ?? null,
            'signature_verified' => $preview['signature']['verified'] ?? null,
        ]) . PHP_EOL);

        $import = $service->import($file);

        fwrite(STDERR, 'SPRINT10_CORE_E2E_IMPORT=' . json_encode([
            'valid' => $import['valid'],
            'imported' => $import['imported'] ?? null,
            'skipped' => $import['skipped'] ?? null,
            'warnings' => $import['warnings'] ?? null,
            'errors' => $import['errors'] ?? null,
        ]) . PHP_EOL);

        $this->assertTrue($preview['valid']);
        $this->assertTrue($preview['doctor']['ready_to_import']);
        $this->assertTrue($preview['inspection']['valid']);
        $this->assertSame(1, $preview['summary']['payments.json']);
        $this->assertTrue($import['valid']);
        $this->assertSame(1, $import['imported']['suppliers']);
        $this->assertSame(1, $import['imported']['payments']);
    }
}
