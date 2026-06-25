<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class CreateAdminCommand extends Command
{
    protected $signature = 'create:admin
        {--email=admin@hospital.local : Email do administrador}
        {--name="Administrador Sistema" : Nome do administrador}
        {--password=ChangeMe#2026! : Senha inicial}
        {--force : ForÃ§ar atualizaÃ§Ã£o se usuÃ¡rio jÃ¡ existe}';

    protected $description = 'Criar ou atualizar usuÃ¡rio administrador com perfil super_admin';

    public function handle(): int
    {
        $email = $this->option('email');
        $name = $this->option('name');
        $password = $this->option('password');
        $force = $this->option('force');

        // Garantir roles e permissÃµes
        $this->ensureRolesAndPermissions();

        $admin = User::where('email', $this->option('email'))->first();

        if ($admin && !$force) {
            $this->warn("UsuÃ¡rio {$email} jÃ¡ existe. Use --force para atualizar.");
            return self::SUCCESS;
        }

        if ($admin) {
            $admin->update([
                'name' => $name,
                'password' => bcrypt($password),
                'is_active' => true,
            ]);
            $this->info("UsuÃ¡rio {$email} atualizado com sucesso.");
        } else {
            $admin = User::create([
                'name' => $name,
                'email' => $email,
                'password' => bcrypt($password),
                'is_active' => true,
            ]);
            $this->info("UsuÃ¡rio {$email} criado com sucesso.");
        }

        // Atribuir role super_admin
        $admin->assignRole('super_admin');

        $this->info("PermissÃµes de super_admin atribuÃ­das.");
        $this->line("Login: {$email}");
        $this->line("Senha: {$this->option('password')}");

        return self::SUCCESS;
    }

    private function ensureRolesAndPermissions(): void
    {
        $roles = [
            'super_admin' => 'Super Administrador Sistema',
            'financeiro' => 'inanceiro',
            'tesoureiro' => 'esoureiro',
            'prestacao_contas' => 'restaÃ§Ã£o de Contas',
            'auditoria' => 'uditoria',
            'visualizador' => 'isualizador',
        ];

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
            foreach ($roles as $roleName => $roleLabel) {
                Role::firstOrCreate(['name' => $roleName, 'guard_name' => $guard]);
            }
            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission, 'guard_name' => $guard]);
            }
        }

        // Sincronizar super_admin com todas as permissÃµes
        foreach (['web', 'sanctum'] as $guard) {
            $superAdmin = Role::where('name', 'super_admin')->where('guard_name', 'sanctum')->first();
            $allPermissions = Permission::where('guard_name', 'sanctum')->get();
            $superAdmin->syncPermissions($allPermissions);
        }
    }
}
