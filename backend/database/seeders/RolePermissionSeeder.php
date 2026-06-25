<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    protected $guards = ['web', 'sanctum'];

    public function run(): void
    {
        $roles = [
            'super_admin',
            'financeiro',
            'tesoureiro',
            'prestacao_contas',
            'auditoria',
            'visualizador',
        ];

        foreach ($this->guards as $guard) {
            foreach ($roles as $role) {
                Role::firstOrCreate(['name' => $role, 'guard_name' => $guard]);
            }
        }

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

        foreach ($this->guards as $guard) {
            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission, 'guard_name' => $guard]);
            }
        }

        foreach ($this->guards as $guard) {
            $superAdmin = Role::where('name', 'super_admin')->where('guard_name', $guard)->first();
            $allPermissions = Permission::where('guard_name', $guard)->get();
            $superAdmin->syncPermissions($allPermissions);

            $financeiro = Role::where('name', 'financeiro')->where('guard_name', $guard)->first();
            $financeiro->syncPermissions(collect([
                'view_dashboard',
                'manage_suppliers',
                'manage_health_plans',
                'manage_payables',
                'manage_nfe',
                'manage_dda',
                'manage_pre_launches',
                'manage_reports',
                'export_data',
                'manage_backups',
            ])->map(fn ($name) => Permission::where('name', $name)->where('guard_name', $guard)->first())->filter());

            $tesoureiro = Role::where('name', 'tesoureiro')->where('guard_name', $guard)->first();
            $tesoureiro->syncPermissions(collect([
                'view_dashboard',
                'manage_payables',
                'manage_bank_accounts',
                'manage_conciliation',
                'manage_reports',
                'export_data',
            ])->map(fn ($name) => Permission::where('name', $name)->where('guard_name', $guard)->first())->filter());

            $prestacao = Role::where('name', 'prestacao_contas')->where('guard_name', $guard)->first();
            $prestacao->syncPermissions(collect([
                'view_dashboard',
                'manage_reports',
                'export_data',
            ])->map(fn ($name) => Permission::where('name', $name)->where('guard_name', $guard)->first())->filter());

            $auditoria = Role::where('name', 'auditoria')->where('guard_name', $guard)->first();
            $auditoria->syncPermissions(collect([
                'view_dashboard',
                'view_audit_trail',
                'manage_reports',
                'export_data',
            ])->map(fn ($name) => Permission::where('name', $name)->where('guard_name', $guard)->first())->filter());

            $visualizador = Role::where('name', 'visualizador')->where('guard_name', $guard)->first();
            $visualizador->syncPermissions(collect([
                'view_dashboard',
            ])->map(fn ($name) => Permission::where('name', $name)->where('guard_name', $guard)->first())->filter());
        }
    }
}
