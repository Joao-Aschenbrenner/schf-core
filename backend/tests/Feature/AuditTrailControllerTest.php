<?php

use App\Models\User;
use App\Models\AuditTrail;
use Database\Seeders\RolePermissionSeeder;
use function Pest\Laravel\{actingAs, getJson, seed};

beforeEach(function () {
    seed(RolePermissionSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('super_admin');
});

it('can list audit trail', function () {
    AuditTrail::factory()->count(5)->create();

    actingAs($this->user)
        ->getJson('/api/audit-trail')
        ->assertOk();
});

it('can filter audit trail by action', function () {
    AuditTrail::factory()->count(3)->create(['action' => 'created']);
    AuditTrail::factory()->count(2)->create(['action' => 'updated']);

    actingAs($this->user)
        ->getJson('/api/audit-trail?action=created')
        ->assertOk();
});

it('can filter audit trail by model type', function () {
    AuditTrail::factory()->count(2)->create(['model_type' => \App\Models\Payable::class]);
    AuditTrail::factory()->count(1)->create(['model_type' => \App\Models\Nfe::class]);

    actingAs($this->user)
        ->getJson('/api/audit-trail?model_type=' . urlencode(\App\Models\Payable::class))
        ->assertOk();
});

it('audit trail requires authentication', function () {
    getJson('/api/audit-trail')->assertUnauthorized();
});
