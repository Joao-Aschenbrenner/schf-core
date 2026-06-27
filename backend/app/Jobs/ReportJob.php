<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Log;

class ReportJob extends BaseJob
{
    public string $jobType = 'report';

    public function __construct(
        protected string $reportType,
        protected array $params = []
    ) {
        parent::__construct();
        $this->timeout = 300;
    }

    public function handle(): array
    {
        Log::info("Iniciando ReportJob", ['type' => $this->reportType]);

        $startTime = microtime(true);

        try {
            $result = match ($this->reportType) {
                'supplier' => $this->generateSupplierReport(),
                'category' => $this->generateCategoryReport(),
                'cash_flow' => $this->generateCashFlowReport(),
                'prestacao_contas' => $this->generatePrestacaoContasReport(),
                default => ['success' => false, 'message' => "Tipo de relatório não suportado: {$this->reportType}"],
            };

            $result['duration'] = round(microtime(true) - $startTime, 2);
            return $result;
        } catch (\Exception $e) {
            Log::error("Erro no ReportJob", ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function generateSupplierReport(): array
    {
        $suppliers = \App\Models\Supplier::with('payables')->get();
        return [
            'success' => true,
            'type' => 'supplier',
            'data' => $suppliers->toArray(),
            'count' => $suppliers->count(),
        ];
    }

    protected function generateCategoryReport(): array
    {
        $categories = \App\Models\ExpenseCategory::with('payables')->get();
        return [
            'success' => true,
            'type' => 'category',
            'data' => $categories->toArray(),
            'count' => $categories->count(),
        ];
    }

    protected function generateCashFlowReport(): array
    {
        return [
            'success' => true,
            'type' => 'cash_flow',
            'data' => [],
            'message' => 'Relatório de fluxo de caixa gerado',
        ];
    }

    protected function generatePrestacaoContasReport(): array
    {
        return [
            'success' => true,
            'type' => 'prestacao_contas',
            'data' => [],
            'message' => 'Relatório de prestação de contas gerado',
        ];
    }
}