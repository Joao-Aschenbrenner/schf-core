<?php

use App\Models\ExpenseCategory;

test('factory creates valid expense category', function () {
    $category = ExpenseCategory::factory()->create();

    expect($category)->toBeInstanceOf(ExpenseCategory::class);
    expect($category->name)->not->toBeEmpty();
    expect($category->code)->not->toBeEmpty();
    expect($category->is_active)->toBeTrue();
});

test('expense category has parent relationship', function () {
    $parent = ExpenseCategory::factory()->create();
    $child = ExpenseCategory::factory()->parent($parent)->create();

    expect($child->parent)->toBeInstanceOf(ExpenseCategory::class);
    expect($child->parent_id)->toBe($parent->id);
});

test('expense category has many children', function () {
    $parent = ExpenseCategory::factory()->create();
    ExpenseCategory::factory()->count(3)->parent($parent)->create();

    expect($parent->children)->toHaveCount(3);
});

test('expense category can be inactive', function () {
    $category = ExpenseCategory::factory()->inactive()->create();

    expect($category->is_active)->toBeFalse();
});

test('expense category soft deletes', function () {
    $category = ExpenseCategory::factory()->create();
    $id = $category->id;

    $category->delete();

    expect(ExpenseCategory::find($id))->toBeNull();
    expect(ExpenseCategory::withTrashed()->find($id))->not->toBeNull();
});
