<?php

use App\Models\Backup;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use function Pest\Laravel\{actingAs, getJson, postJson};

beforeEach(function () {
    // Create roles and permissions directly
    $roles = ['super_admin', 'financeiro', 'tesoureiro', 'prestacao_contas', 'auditoria', 'visualizador'];
    $permissions = [
        'view_dashboard',
        'manage_users',
        'manage_suppliers',
        'manage_health_plans',
        'manage_expense_categories',
        'manage_bank_accounts',
        'manage_payables',
        'manage_nfe',
        'manage_dda',
        'manage_conciliation',
        'manage_reports',
        'manage_pre_launches',
        'view_audit_trail',
        'export_data',
        'manage_backups',
        'manage_historico',
    ];

    foreach (['web', 'sanctum'] as $guard) {
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => $guard]);
        }
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => $guard]);
        }
    }

    $superAdmin = Role::where('name', 'super_admin')->where('guard_name', 'sanctum')->first();
    $allPermissions = Permission::where('guard_name', 'sanctum')->get();
    $superAdmin->syncPermissions($allPermissions);

    $this->user = User::factory()->create();
    $this->user->assignRole('super_admin');
    $this->user->syncPermissions($allPermissions);
});

it('can restore a backup', function () {
    $checksum = hash('sha256', 'test');
    
    $backup = Backup::factory()->create([
        'user_id' => $this->user->id,
        'type' => 'database',
        'status' => 'completed',
        'encrypted' => false,
        'file_path' => 'backups/test_db.zip',
        'file_name' => 'test_db.zip',
        'checksum' => $checksum,
    ]);

    // Create dummy zip file
    Storage::fake('local');
    Storage::disk('local')->put('backups/test_db.zip', 'dummy zip content');

    actingAs($this->user)
        ->postJson("/api/backups/{$backup->id}/restore", [
            'password' => 'TestPassword123',
        ])
        ->assertOk();
});

it('can validate a backup', function () {
    $backup = Backup::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'completed',
        'file_path' => 'backups/test.zip',
        'file_name' => 'test.zip',
        'encrypted' => false,
    ]);

    // Create dummy file
    Storage::fake('local');
    Storage::disk('local')->put('backups/test.zip', 'dummy content');

    actingAs($this->user)
        ->getJson("/api/backups/{$backup->id}/validate")
        ->assertOk()
        ->assertJsonPath('file_exists', true);
});

it('requires password for restore', function () {
    $backup = Backup::factory()->create(['user_id' => $this->user->id]);

    actingAs($this->user)
        ->postJson("/api/backups/{$backup->id}/restore", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});