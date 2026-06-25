<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SetupWizardController extends Controller
{
    public function status()
    {
        $configured = Organization::count() > 0;

        return response()->json([
            'is_configured' => $configured,
            'organization' => $configured ? Organization::first() : null,
        ]);
    }

    public function createOrganization(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'cnpj' => 'nullable|string|size:18|unique:organizations,cnpj',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|size:2',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $org = Organization::create([
            'name' => $request->name,
            'cnpj' => $request->cnpj,
            'city' => $request->city,
            'state' => $request->state,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'is_primary' => true,
            'is_active' => true,
        ]);

        return response()->json(['organization' => $org]);
    }

    public function createAdmin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $org = Organization::where('is_primary', true)->firstOrFail();

        $user = User::create([
            'organization_id' => $org->id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => true,
            'is_master' => true,
            'is_system_admin' => true,
        ]);

        // Atribuir role super_admin
        $superAdminRole = Role::where('name', 'super_admin')->first();
        if ($superAdminRole) {
            $user->assignRole('super_admin');
        }

        // Gerar master token
        $user->generateMasterToken();

        return response()->json([
            'user' => $user,
            'master_token' => $user->master_token,
        ]);
    }

    public function complete(Request $request)
    {
        $org = Organization::where('is_primary', true)->firstOrFail();

        // Criar roles padrão se não existirem
        $this->ensureDefaultRolesAndPermissions();

        // Criar categorias de despesa padrão
        $this->createDefaultExpenseCategories($org);

        return response()->json([
            'message' => 'Configuração inicial concluída com sucesso',
            'organization' => $org->load('users'),
            'redirect' => '/login',
        ]);
    }

    private function ensureDefaultRolesAndPermissions(): void
    {
        $permissions = [
            'view_dashboard', 'view_reports',
            'manage_suppliers', 'manage_health_plans', 'manage_bank_accounts',
            'manage_expense_categories', 'manage_nfes', 'manage_payables',
            'manage_pre_launches', 'manage_conciliation', 'manage_cronograma',
            'manage_audit_trail', 'manage_users', 'manage_roles', 'manage_backups',
            'manage_integrity', 'manage_maintenance', 'access_admin_panel',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'api']);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'api']);
        $superAdmin->syncPermissions($permissions);

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $admin->syncPermissions(array_diff($permissions, ['manage_users', 'manage_roles', 'manage_backups', 'manage_integrity', 'manage_maintenance', 'access_admin_panel']));

        $operator = Role::firstOrCreate(['name' => 'operator', 'guard_name' => 'api']);
        $operator->syncPermissions(['view_dashboard', 'view_reports', 'manage_suppliers', 'manage_health_plans', 'manage_payables', 'manage_pre_launches', 'manage_conciliation']);
    }

    private function createDefaultExpenseCategories(Organization $org): void
    {
        $categories = [
            ['name' => 'Pessoal', 'description' => 'Despesas com pessoal', 'color' => '#3B82F6'],
            ['name' => 'Material', 'description' => 'Material de consumo', 'color' => '#10B981'],
            ['name' => 'Serviços', 'description' => 'Serviços de terceiros', 'color' => '#F59E0B'],
            ['name' => 'Equipamentos', 'description' => 'Equipamentos e manutenção', 'color' => '#EF4444'],
            ['name' => 'Outros', 'description' => 'Outras despesas', 'color' => '#6B7280'],
        ];

        foreach ($categories as $cat) {
            \App\Models\ExpenseCategory::firstOrCreate([
                'organization_id' => $org->id,
                'name' => $cat['name'],
            ], [
                'description' => $cat['description'],
                'color' => $cat['color'],
                'is_active' => true,
            ]);
        }
    }
}