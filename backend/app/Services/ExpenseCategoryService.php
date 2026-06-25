<?php

namespace App\Services;

use App\Models\ExpenseCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExpenseCategoryService
{
    public function list(array $filters = []): Collection
    {
        $query = ExpenseCategory::query()->with('parent', 'children');

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['parent_only']) && $filters['parent_only']) {
            $query->whereNull('parent_id');
        }

        return $query->orderBy('code')->get();
    }

    public function create(array $data): ExpenseCategory
    {
        return DB::transaction(function () use ($data) {
            $category = ExpenseCategory::create($data);

            activity()
                ->performedOn($category)
                ->withProperties($data)
                ->log('expense_category_created');

            return $category;
        });
    }

    public function update(ExpenseCategory $category, array $data): ExpenseCategory
    {
        return DB::transaction(function () use ($category, $data) {
            $oldValues = $category->toArray();
            $category->update($data);

            activity()
                ->performedOn($category)
                ->withProperties(['old' => $oldValues, 'new' => $data])
                ->log('expense_category_updated');

            return $category->fresh();
        });
    }

    public function delete(ExpenseCategory $category): bool
    {
        if ($category->children()->exists()) {
            throw new \Exception('Não é possível excluir uma categoria que possui subcategorias.');
        }

        return DB::transaction(function () use ($category) {
            activity()
                ->performedOn($category)
                ->log('expense_category_deleted');

            return $category->delete();
        });
    }
}
