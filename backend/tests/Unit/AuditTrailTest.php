<?php

use App\Models\AuditTrail;
use App\Models\User;

test('factory creates valid audit trail', function () {
    $audit = AuditTrail::factory()->create();

    expect($audit)->toBeInstanceOf(AuditTrail::class);
    expect($audit->model_type)->not->toBeEmpty();
    expect($audit->model_id)->toBeNumeric();
    expect($audit->action)->toBeIn(['created', 'updated', 'deleted', 'restored']);
});

test('audit trail belongs to user', function () {
    $audit = AuditTrail::factory()->create();

    expect($audit->user)->toBeInstanceOf(User::class);
    expect($audit->user_id)->toBe($audit->user->id);
});

test('audit trail casts old_values and new_values as arrays', function () {
    $audit = AuditTrail::factory()->create([
        'old_values' => ['name' => 'Old Name'],
        'new_values' => ['name' => 'New Name'],
    ]);

    expect($audit->old_values)->toBeArray();
    expect($audit->old_values['name'])->toBe('Old Name');
    expect($audit->new_values['name'])->toBe('New Name');
});

test('audit trail has timestamps', function () {
    $audit = AuditTrail::factory()->create();

    expect($audit->created_at)->not->toBeNull();
    expect($audit->updated_at)->not->toBeNull();
});

test('audit trail stores ip address and user agent', function () {
    $audit = AuditTrail::factory()->create([
        'ip_address' => '192.168.1.100',
        'user_agent' => 'Mozilla/5.0',
    ]);

    expect($audit->ip_address)->toBe('192.168.1.100');
    expect($audit->user_agent)->toBe('Mozilla/5.0');
});
