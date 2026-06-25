<?php

use App\Models\Supplier;

test('factory creates valid supplier', function () {
    $supplier = Supplier::factory()->create();

    expect($supplier)->toBeInstanceOf(Supplier::class);
    expect($supplier->name)->not->toBeEmpty();
    expect($supplier->cnpj)->not->toBeEmpty();
    expect($supplier->is_active)->toBeTrue();
});

test('supplier has correct fillable attributes', function () {
    $supplier = new Supplier();

    expect($supplier->getFillable())->toContain('name');
    expect($supplier->getFillable())->toContain('cnpj');
    expect($supplier->getFillable())->toContain('email');
    expect($supplier->getFillable())->toContain('phone');
    expect($supplier->getFillable())->toContain('is_active');
});

test('scope active filters suppliers', function () {
    Supplier::factory()->count(3)->create(['is_active' => true]);
    Supplier::factory()->count(2)->create(['is_active' => false]);

    expect(Supplier::active()->count())->toBe(3);
});

test('supplier soft deletes', function () {
    $supplier = Supplier::factory()->create();
    $id = $supplier->id;

    $supplier->delete();

    expect(Supplier::find($id))->toBeNull();
    expect(Supplier::withTrashed()->find($id))->not->toBeNull();
});

test('supplier can be restored', function () {
    $supplier = Supplier::factory()->create();
    $supplier->delete();

    $supplier->restore();

    expect(Supplier::find($supplier->id))->not->toBeNull();
});

test('supplier casts is_active correctly', function () {
    $supplier = Supplier::factory()->create(['is_active' => false]);

    expect($supplier->is_active)->toBeFalse();
});

test('supplier has many payables', function () {
    $supplier = Supplier::factory()->create();
    \App\Models\Payable::factory()->count(3)->create(['supplier_id' => $supplier->id]);

    expect($supplier->payables)->toHaveCount(3);
});
