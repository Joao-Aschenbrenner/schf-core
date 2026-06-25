<?php

use App\Models\ExpenseCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use function Pest\Laravel\{actingAs, getJson, postJson, putJson, deleteJson, seed};

beforeEach(function () {
    seed(RolePermissionSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('super_admin');
});

it('can list expense categories', function () {
    ExpenseCategory::factory()->count(5)->create();

    actingAs($this->user)
        ->getJson('/api/expense-categories')
        ->assertOk();
});

it('can create an expense category', function () {
    $data = [
        'name' => 'Categoria Teste',
        'code' => '1001',
        'description' => 'Descrição da categoria',
    ];

    actingAs($this->user)
        ->postJson('/api/expense-categories', $data)
        ->assertCreated()
        ->assertJsonPath('data.name', 'Categoria Teste');
});

it('validates expense category creation', function () {
    actingAs($this->user)
        ->postJson('/api/expense-categories', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'code']);
});

it('can show an expense category', function () {
    $category = ExpenseCategory::factory()->create();

    actingAs($this->user)
        ->getJson("/api/expense-categories/{$category->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $category->id);
});

it('can update an expense category', function () {
    $category = ExpenseCategory::factory()->create();

    actingAs($this->user)
        ->putJson("/api/expense-categories/{$category->id}", [
            'name' => 'Nome Atualizado',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Nome Atualizado');
});

it('can delete an expense category', function () {
    $category = ExpenseCategory::factory()->create();

    actingAs($this->user)
        ->deleteJson("/api/expense-categories/{$category->id}")
        ->assertOk();
});

it('can list parent categories', function () {
    $parent = ExpenseCategory::factory()->create();
    ExpenseCategory::factory()->count(2)->parent($parent)->create();

    actingAs($this->user)
        ->getJson('/api/expense-categories')
        ->assertOk();
});
