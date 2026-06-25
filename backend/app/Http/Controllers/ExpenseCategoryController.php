<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseCategoryRequest;
use App\Http\Requests\UpdateExpenseCategoryRequest;
use App\Models\ExpenseCategory;
use App\Services\ExpenseCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function __construct(
        private ExpenseCategoryService $categoryService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ExpenseCategory::class);

        $categories = $this->categoryService->list($request->only([
            'is_active', 'parent_only'
        ]));

        return response()->json(['data' => $categories]);
    }

    public function store(StoreExpenseCategoryRequest $request): JsonResponse
    {
        $this->authorize('create', ExpenseCategory::class);

        $category = $this->categoryService->create($request->validated());

        return response()->json(['data' => $category], 201);
    }

    public function show(ExpenseCategory $expenseCategory): JsonResponse
    {
        $this->authorize('view', $expenseCategory);

        return response()->json(['data' => $expenseCategory->load('parent', 'children')]);
    }

    public function update(UpdateExpenseCategoryRequest $request, ExpenseCategory $expenseCategory): JsonResponse
    {
        $this->authorize('update', $expenseCategory);

        $category = $this->categoryService->update($expenseCategory, $request->validated());

        return response()->json(['data' => $category]);
    }

    public function destroy(ExpenseCategory $expenseCategory): JsonResponse
    {
        $this->authorize('delete', $expenseCategory);

        $this->categoryService->delete($expenseCategory);

        return response()->json(['message' => 'Categoria removida com sucesso.']);
    }
}
