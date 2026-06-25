<?php

use App\Models\NfeItem;
use App\Models\Nfe;

test('factory creates valid nfe item', function () {
    $item = NfeItem::factory()->create();

    expect($item)->toBeInstanceOf(NfeItem::class);
    expect($item->code)->not->toBeEmpty();
    expect($item->description)->not->toBeEmpty();
    expect($item->quantity)->toBeNumeric();
    expect($item->unit_price)->toBeNumeric();
    expect($item->total_price)->toBeNumeric();
});

test('nfe item belongs to nfe', function () {
    $nfe = Nfe::factory()->create();
    $item = NfeItem::factory()->create(['nfe_id' => $nfe->id]);

    expect($item->nfe)->toBeInstanceOf(Nfe::class);
    expect($item->nfe_id)->toBe($nfe->id);
});

test('nfe item casts decimal fields correctly', function () {
    $item = NfeItem::factory()->create([
        'quantity' => 10.500,
        'unit_price' => 25.9900,
        'total_price' => 272.89,
    ]);

    expect($item->quantity)->toBe('10.500');
    expect($item->unit_price)->toBe('25.9900');
    expect($item->total_price)->toBe('272.89');
});

test('nfe item has correct fillable attributes', function () {
    $item = new NfeItem();

    expect($item->getFillable())->toContain('nfe_id');
    expect($item->getFillable())->toContain('code');
    expect($item->getFillable())->toContain('ncm');
    expect($item->getFillable())->toContain('cfop');
    expect($item->getFillable())->toContain('total_price');
});
