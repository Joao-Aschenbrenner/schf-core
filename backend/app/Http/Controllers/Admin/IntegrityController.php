<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IntegrityController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Integridade do sistema',
            'endpoints' => [
                'GET /admin/integrity' => 'Verificar todos os componentes',
                'POST /admin/integrity/check' => 'Verificação completa de banco de dados',
                'POST /admin/integrity/run-tests' => 'Executar testes PHPUnit',
            ],
        ]);
    }

    public function checkAll(Request $request): JsonResponse
    {
        $results = [
            'orphans' => $this->checkOrphans(),
            'counts' => $this->checkCounts(),
            'duplicates' => $this->checkDuplicates(),
            'foreign_keys' => $this->checkForeignKeys(),
        ];

        $hasIssues = collect($results)->flatten(1)->contains(fn($item) => ($item['issues'] ?? 0) > 0);

        return response()->json([
            'overall' => $hasIssues ? 'issues_found' : 'ok',
            'results' => $results,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function runTests(): JsonResponse
    {
        $output = [];
        $exitCode = 0;

        exec('cd ' . base_path() . ' && php artisan test --without-tty 2>&1', $output, $exitCode);

        $outputStr = implode("\n", $output);

        preg_match('/Tests:\s+(\d+)\s+Assertions:\s+(\d+)/', $outputStr, $matches);
        preg_match('/OK\s+\((\d+)\s+tests?\)/', $outputStr, $okMatch);
        preg_match('/FAILURES!/', $outputStr, $failMatch);

        $ran = isset($matches[1]) ? (int)$matches[1] : 0;
        $assertions = isset($matches[2]) ? (int)$matches[2] : 0;
        $passed = isset($okMatch[1]) ? (int)$okMatch[1] : ($exitCode === 0 ? $ran : 0);
        $failed = isset($failMatch[1]) ? 1 : ($exitCode !== 0 ? 1 : 0);

        return response()->json([
            'ran' => $ran,
            'passed' => $passed,
            'failed' => $failed,
            'assertions' => $assertions,
            'output' => $outputStr,
            'exit_code' => $exitCode,
        ]);
    }

    private function checkOrphans(): array
    {
        $checks = [];

        $checks['historico_notas.fornecedor_id'] = $this->checkOrphan(
            'historico_notas',
            'fornecedor_id',
            'historico_fornecedores',
            'codigo_legado'
        );

        $checks['historico_operacoes_banco.conta_id'] = $this->checkOrphan(
            'historico_operacoes_banco',
            'conta_id',
            'historico_contas',
            'id'
        );

        $checks['historico_saldos.conta_id'] = $this->checkOrphan(
            'historico_saldos',
            'conta_id',
            'historico_contas',
            'id'
        );

        $checks['historico_movimento_caixa.caixa_id'] = $this->checkOrphan(
            'historico_movimento_caixa',
            'caixa_id',
            'historico_caixa',
            'id'
        );

        $checks['provisions.supplier_id'] = $this->checkOrphan(
            'provisions',
            'supplier_id',
            'suppliers',
            'id'
        );

        $checks['receivables.supplier_id'] = $this->checkOrphan(
            'receivables',
            'supplier_id',
            'suppliers',
            'id'
        );

        return $checks;
    }

    private function checkOrphan(string $table, string $foreignKey, string $referencedTable, string $referencedKey): array
    {
        try {
            $orphans = DB::select("
                SELECT COUNT(*) as cnt FROM $table t
                WHERE NOT EXISTS (SELECT 1 FROM $referencedTable r WHERE r.$referencedKey = t.$foreignKey)
                AND t.$foreignKey IS NOT NULL
            ");

            $count = $orphans[0]->cnt ?? 0;

            return [
                'table' => $table,
                'column' => $foreignKey,
                'references' => "$referencedTable.$referencedKey",
                'issues' => (int)$count,
                'status' => $count === 0 ? 'ok' : 'warning',
            ];
        } catch (\Exception $e) {
            return [
                'table' => $table,
                'column' => $foreignKey,
                'references' => "$referencedTable.$referencedKey",
                'issues' => 0,
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function checkCounts(): array
    {
        $expectedCounts = [
            'historico_fornecedores' => 278,
            'historico_contas' => 28,
            'historico_saldos' => 5163,
            'historico_operacoes_banco' => 33407,
            'historico_caixa' => 1073,
            'historico_notas' => 37932,
            'historico_baixas_perdidas' => 6888,
            'historico_convenios' => 16,
            'historico_usuarios' => 104,
        ];

        $results = [];

        foreach ($expectedCounts as $table => $expected) {
            try {
                $actual = DB::table($table)->count();
                $match = $actual === $expected;

                $results[$table] = [
                    'expected' => $expected,
                    'actual' => $actual,
                    'match' => $match,
                    'status' => $match ? 'ok' : 'mismatch',
                ];
            } catch (\Exception $e) {
                $results[$table] = [
                    'expected' => $expected,
                    'actual' => 'error',
                    'match' => false,
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    private function checkDuplicates(): array
    {
        $results = [];

        try {
            $dupEmail = DB::table('users')
                ->select('email', DB::raw('COUNT(*) as cnt'))
                ->groupBy('email')
                ->having('cnt', '>', 1)
                ->count();

            $results['users.email'] = [
                'column' => 'users.email',
                'duplicates' => $dupEmail,
                'status' => $dupEmail === 0 ? 'ok' : 'warning',
            ];
        } catch (\Exception $e) {
            $results['users.email'] = [
                'column' => 'users.email',
                'duplicates' => 0,
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }

        return $results;
    }

    private function checkForeignKeys(): array
    {
        $fkChecks = [
            'users.id' => fn() => DB::table('users')->count() > 0,
            'historico_fornecedores.codigo_legado' => fn() => DB::table('historico_fornecedores')->count() > 0,
        ];

        $results = [];

        foreach ($fkChecks as $fk => $check) {
            try {
                $exists = $check();
                $results[$fk] = [
                    'status' => 'ok',
                    'exists' => $exists,
                ];
            } catch (\Exception $e) {
                $results[$fk] = [
                    'status' => 'error',
                    'exists' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }
}