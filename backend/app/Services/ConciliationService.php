<?php

namespace App\Services;

use App\Models\BankStatement;
use App\Models\BankStatementItem;
use App\Models\Payable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ConciliationService
{
    public function listStatements(array $filters = []): LengthAwarePaginator
    {
        $query = BankStatement::query()->with(['bankAccount', 'items']);

        if (isset($filters['bank_account_id'])) {
            $query->where('bank_account_id', $filters['bank_account_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('statement_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('statement_date', '<=', $filters['date_to']);
        }

        $sortField = $filters['sort_field'] ?? 'statement_date';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortField, $sortDirection);

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function importOfx(int $bankAccountId, string $ofxContent): BankStatement
    {
        return DB::transaction(function () use ($bankAccountId, $ofxContent) {
            $transactions = $this->parseOfx($ofxContent);

            $statement = BankStatement::create([
                'bank_account_id' => $bankAccountId,
                'statement_date' => now()->toDateString(),
                'source_type' => 'ofx',
                'opening_balance' => $transactions['opening_balance'] ?? 0,
                'closing_balance' => $transactions['closing_balance'] ?? 0,
                'status' => 'imported',
            ]);

            foreach ($transactions['items'] as $item) {
                $statement->items()->create([
                    'transaction_date' => $item['date'],
                    'amount' => abs($item['amount']),
                    'type' => $item['amount'] >= 0 ? 'credit' : 'debit',
                    'description' => $item['description'] ?? 'Sem descrição',
                    'document_id' => $item['document_number'] ?? $item['reference'] ?? null,
                    'is_reconciled' => false,
                ]);
            }

            activity()
                ->performedOn($statement)
                ->withProperties(['bank_account_id' => $bankAccountId, 'items_count' => count($transactions['items'])])
                ->log('bank_statement_imported');

            return $statement->load('items');
        });
    }

    public function conciliateItem(BankStatementItem $item, Payable $payable): BankStatementItem
    {
        return DB::transaction(function () use ($item, $payable) {
            $item->update([
                'payable_id' => $payable->id,
                'is_reconciled' => true,
                'reconciled_by' => auth()->id() ?? 1,
                'reconciled_at' => now(),
            ]);

            if ($item->isDebit()) {
                app(PayableService::class)->pay($payable, [
                    'paid_amount' => $item->amount,
                    'payment_date' => $item->transaction_date->toDateString(),
                    'payment_method' => 'bank_transfer',
                ]);
            }

            activity()
                ->performedOn($item)
                ->withProperties(['payable_id' => $payable->id])
                ->log('statement_item_conciliated');

            $this->checkStatementCompletion($item->bankStatement);

            return $item->fresh();
        });
    }

    public function autoMatch(BankStatement $statement): int
    {
        $matched = 0;

        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\BankStatementItem> $items */
        $items = $statement->items()->where('is_reconciled', false)->get();

        foreach ($items as $item) {
            $payable = $this->findMatchingPayable($item);
            if ($payable) {
                $this->conciliateItem($item, $payable);
                $matched++;
            }
        }

        $this->checkStatementCompletion($statement);

        return $matched;
    }

    public function unmatchItem(BankStatementItem $item): BankStatementItem
    {
        return DB::transaction(function () use ($item) {
            $payable = $item->payable;

            $item->update([
                'payable_id' => null,
                'is_reconciled' => false,
                'reconciled_by' => null,
                'reconciled_at' => null,
            ]);

            if ($payable && $payable->status === 'paid') {
                $payable->update([
                    'status' => 'pending',
                    'paid_amount' => null,
                    'paid_at' => null,
                ]);
            }

            activity()
                ->performedOn($item)
                ->log('statement_item_unmatched');

            return $item->fresh();
        });
    }

    private function findMatchingPayable(BankStatementItem $item): ?Payable
    {
        if (!$item->isDebit()) {
            return null;
        }

        return Payable::where('status', 'pending')
            ->where('amount', $item->amount)
            ->where('bank_account_id', $item->bankStatement->bank_account_id)
            ->where('due_date', '<=', $item->transaction_date->addDays(3)->toDateString())
            ->where('due_date', '>=', $item->transaction_date->subDays(10)->toDateString())
            ->first();
    }

    private function checkStatementCompletion(BankStatement $statement): void
    {
        $unreconciled = $statement->items()->where('is_reconciled', false)->count();

        if ($unreconciled === 0) {
            $statement->update(['status' => 'reconciled']);

            activity()
                ->performedOn($statement)
                ->log('bank_statement_fully_conciliated');
        }
    }

    private function parseOfx(string $content): array
    {
        $result = [
            'opening_balance' => 0,
            'closing_balance' => 0,
            'items' => [],
        ];

        if (preg_match('/<BALAMT>([-\d.]+)/', $content, $balanceMatch)) {
            $result['opening_balance'] = (float) $balanceMatch[1];
        }

        if (preg_match('/<LEDGERBAL>.*?<BALAMT>([-\d.]+)/s', $content, $ledgerMatch)) {
            $result['closing_balance'] = (float) $ledgerMatch[1];
        }

        preg_match_all('/<STMTTRN>(.*?)<\/STMTTRN>/s', $content, $transactions);

        foreach ($transactions[1] ?? [] as $txn) {
            $item = [];

            if (preg_match('/<TRNAMT>([-\d.]+)/', $txn, $amountMatch)) {
                $item['amount'] = (float) $amountMatch[1];
            }

            if (preg_match('/<DTPOSTED>(\d{8})/', $txn, $dateMatch)) {
                $year = substr($dateMatch[1], 0, 4);
                $month = substr($dateMatch[1], 4, 2);
                $day = substr($dateMatch[1], 6, 2);
                $item['date'] = "{$year}-{$month}-{$day}";
            }

            if (preg_match('/<NAME>(.*?)\s*(?:<|$)/', $txn, $nameMatch)) {
                $item['description'] = trim($nameMatch[1]);
            }

            if (preg_match('/<REFNUM>(.*?)\s*(?:<|$)/', $txn, $refMatch)) {
                $item['reference'] = trim($refMatch[1]);
            }

            if (preg_match('/<CHECKNUM>(.*?)\s*(?:<|$)/', $txn, $checkMatch)) {
                $item['document_number'] = trim($checkMatch[1]);
            }

            if (!empty($item['amount']) && !empty($item['date'])) {
                $result['items'][] = $item;
            }
        }

        return $result;
    }
}
