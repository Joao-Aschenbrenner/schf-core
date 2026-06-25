<?php

use App\Models\User;
use App\Models\Payable;
use App\Models\Nfe;
use App\Models\Supplier;
use App\Models\HealthPlan;
use Database\Seeders\RolePermissionSeeder;
use function Pest\Laravel\{actingAs, getJson, seed};

beforeEach(function () {
    seed(RolePermissionSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('super_admin');
});

it('returns dashboard summary', function () {
    $supplier = Supplier::factory()->create();
    $plan = HealthPlan::factory()->create();

    Payable::factory()->count(3)->create(['status' => 'pending']);
    Payable::factory()->count(2)->create(['status' => 'paid']);
    Payable::factory()->count(1)->create(['status' => 'overdue']);

    actingAs($this->user)
        ->getJson('/api/dashboard/summary')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'total_balance',
                'pending_payables',
                'overdue_payables',
                'due_today',
                'pending_pre_launches',
                'total_suppliers',
                'active_health_plans',
                'total_bank_accounts',
            ],
        ]);
});

it('dashboard returns zeros when no data', function () {
    actingAs($this->user)
        ->getJson('/api/dashboard/summary')
        ->assertOk()
        ->assertJsonPath('data.pending_payables', 0);
});
