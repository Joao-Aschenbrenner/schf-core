<?php

use App\Models\Nfe;
use App\Models\Supplier;
use App\Models\NfeItem;
use App\Models\Payable;

test('factory creates valid nfe', function () {
    $nfe = Nfe::factory()->create();

    expect($nfe)->toBeInstanceOf(Nfe::class);
    expect($nfe->nfe_key)->not->toBeEmpty();
    expect($nfe->nfe_number)->toBeNumeric();
    expect($nfe->total_value)->toBeNumeric();
    expect($nfe->status)->toBeIn(['pending', 'classified', 'linked', 'cancelled']);
});

test('nfe has correct fillable attributes', function () {
    $nfe = new Nfe();

    expect($nfe->getFillable())->toContain('nfe_key');
    expect($nfe->getFillable())->toContain('nfe_number');
    expect($nfe->getFillable())->toContain('total_value');
    expect($nfe->getFillable())->toContain('status');
});

test('nfe belongs to supplier', function () {
    $nfe = Nfe::factory()->create();

    expect($nfe->supplier)->toBeInstanceOf(Supplier::class);
    expect($nfe->supplier_id)->toBe($nfe->supplier->id);
});

test('nfe has many items', function () {
    $nfe = Nfe::factory()->create();
    NfeItem::factory()->count(3)->create(['nfe_id' => $nfe->id]);

    expect($nfe->items)->toHaveCount(3);
    expect($nfe->items->first())->toBeInstanceOf(NfeItem::class);
});

test('nfe has many payables', function () {
    $nfe = Nfe::factory()->create();
    Payable::factory()->count(2)->create(['nfe_id' => $nfe->id]);

    expect($nfe->payables)->toHaveCount(2);
});

test('nfe casts date fields correctly', function () {
    $nfe = Nfe::factory()->create([
        'emission_date' => '2026-01-15',
        'entry_date' => '2026-01-16',
    ]);

    expect($nfe->emission_date)->toBeInstanceOf(\Carbon\Carbon::class);
    expect($nfe->entry_date)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('nfe casts decimal fields correctly', function () {
    $nfe = Nfe::factory()->create([
        'total_value' => 1234.56,
        'icms_value' => 200.00,
    ]);

    expect($nfe->total_value)->toBe('1234.56');
    expect($nfe->icms_value)->toBe('200.00');
});

test('nfe soft deletes', function () {
    $nfe = Nfe::factory()->create();
    $nfeId = $nfe->id;

    $nfe->delete();

    expect(Nfe::find($nfeId))->toBeNull();
    expect(Nfe::withTrashed()->find($nfeId))->not->toBeNull();
});

test('nfe can be restored', function () {
    $nfe = Nfe::factory()->create();
    $nfe->delete();

    $nfe->restore();

    expect(Nfe::find($nfe->id))->not->toBeNull();
});

test('nfe is manual entry flag works', function () {
    $nfeManual = Nfe::factory()->create(['is_manual_entry' => true]);
    $nfeXml = Nfe::factory()->create(['is_manual_entry' => false]);

    expect($nfeManual->is_manual_entry)->toBeTrue();
    expect($nfeXml->is_manual_entry)->toBeFalse();
});
