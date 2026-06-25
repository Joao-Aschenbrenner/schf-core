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

    // Clear permission cache
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    $this->user = User::factory()->create();
    $this->user->assignRole('super_admin');
    $this->user->syncPermissions($allPermissions);
});

it('can list backups', function () {
    Backup::factory()->count(3)->create(['user_id' => $this->user->id]);

    actingAs($this->user)
        ->getJson('/api/backups')
        ->assertOk();
});

it('can filter backups by type', function () {
    Backup::factory()->create(['type' => 'full', 'user_id' => $this->user->id]);
    Backup::factory()->create(['type' => 'database', 'user_id' => $this->user->id]);

    actingAs($this->user)
        ->getJson('/api/backups?filters[type]=full')
        ->assertOk();
});

it('can filter backups by status', function () {
    Backup::factory()->create(['status' => 'completed', 'user_id' => $this->user->id]);
    Backup::factory()->create(['status' => 'failed', 'user_id' => $this->user->id]);

    actingAs($this->user)
        ->getJson('/api/backups?filters[status]=completed')
        ->assertOk();
});

it('can create a full backup', function () {
    $data = [
        'type' => 'full',
        'password' => 'TestPassword123',
    ];

    actingAs($this->user)
        ->postJson('/api/backups', $data)
        ->assertCreated()
        ->assertJsonPath('data.type', 'full')
        ->assertJsonPath('data.encrypted', true);
});

it('can create a database backup', function () {
    $data = [
        'type' => 'database',
        'password' => 'TestPassword123',
    ];

    actingAs($this->user)
        ->postJson('/api/backups', $data)
        ->assertCreated()
        ->assertJsonPath('data.type', 'database');
});

it('can create a files backup', function () {
    $data = [
        'type' => 'files',
        'password' => 'TestPassword123',
    ];

    actingAs($this->user)
        ->postJson('/api/backups', $data)
        ->assertCreated()
        ->assertJsonPath('data.type', 'files');
});

it('validates backup creation', function () {
    actingAs($this->user)
        ->postJson('/api/backups', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['type', 'password']);
});

it('can show a backup', function () {
    $backup = Backup::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'completed',
        'file_path' => 'backups/test.zip',
        'file_name' => 'test.zip',
        'file_size' => 1024,
        'checksum' => 'abc123',
    ]);

    // Create dummy file
    Storage::fake('local');
    Storage::disk('local')->put('backups/test.zip', 'dummy content');

    actingAs($this->user)
        ->getJson("/api/backups/{$backup->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $backup->id);
});

it('can download a completed backup', function () {
    $backup = Backup::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'completed',
        'file_path' => 'backups/test.zip',
        'file_name' => 'test.zip',
    ]);

    // Create dummy file
    Storage::fake('local');
    Storage::disk('local')->put('backups/test.zip', 'dummy content');

    actingAs($this->user)
        ->getJson("/api/backups/{$backup->id}/download")
        ->assertOk();
});

it('can delete a backup', function () {
    $backup = Backup::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'completed',
        'file_path' => 'backups/test.zip',
        'file_name' => 'test.zip',
    ]);

    // Create dummy file
    Storage::fake('local');
    Storage::disk('local')->put('backups/test.zip', 'dummy content');

    actingAs($this->user)
        ->deleteJson("/api/backups/{$backup->id}")
        ->assertOk()
        ->assertJsonPath('message', 'Backup excluído com sucesso');
});

it('can verify backup integrity', function () {
    $backup = Backup::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'completed',
        'checksum' => 'abc123',
        'file_path' => 'backups/test.zip',
    ]);

    // Create dummy file with matching checksum
    Storage::fake('local');
    Storage::disk('local')->put('backups/test.zip', 'dummy content');

    actingAs($this->user)
        ->getJson("/api/backups/{$backup->id}/verify")
        ->assertOk();
});

it('can cleanup old backups', function () {
    actingAs($this->user)
        ->postJson('/api/backups/cleanup')
        ->assertOk()
        ->assertJsonPath('message', 'Limpeza concluída');
});