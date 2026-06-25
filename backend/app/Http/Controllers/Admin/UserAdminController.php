<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');
        $isActive = $request->input('is_active');
        $isMaster = $request->input('is_master');

        $query = User::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
            });
        }

        if (isset($isActive)) {
            $query->where('is_active', $isActive);
        }

        if (isset($isMaster)) {
            $query->where('is_master', $isMaster);
        }

        $users = $query->with('roles')->paginate($perPage);

        return response()->json($users);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'is_active' => 'boolean',
            'is_master' => 'boolean',
            'roles' => 'array',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'is_active' => $request->boolean('is_active', true),
            'is_master' => $request->boolean('is_master', false),
        ]);

        if ($request->has('roles')) {
            $user->assignRole($request->roles);
        }

        $this->logActivity($request->user()?->id, 'user_created', User::class, $user->id);

        return response()->json([
            'message' => 'Usuário criado com sucesso.',
            'user' => $user->load('roles'),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $user = User::with(['roles', 'permissions'])->findOrFail($id);

        return response()->json([
            'user' => $user,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $rules = [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'is_active' => 'boolean',
        ];

        $request->validate($rules);

        $changes = [];

        if ($request->has('name') && $request->name !== $user->name) {
            $changes['name'] = ['old' => $user->name, 'new' => $request->name];
            $user->name = $request->name;
        }

        if ($request->has('email') && $request->email !== $user->email) {
            $changes['email'] = ['old' => $user->email, 'new' => $request->email];
            $user->email = $request->email;
        }

        if ($request->has('is_active') && $request->is_active !== $user->is_active) {
            $changes['is_active'] = ['old' => $user->is_active, 'new' => $request->is_active];
            $user->is_active = $request->is_active;
        }

        $user->save();

        if ($request->has('roles')) {
            $user->syncRoles($request->roles);
        }

        $this->logActivity($request->user()?->id, 'user_updated', User::class, $user->id, json_encode($changes));

        return response()->json([
            'message' => 'Usuário atualizado com sucesso.',
            'user' => $user->fresh()->load('roles'),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $currentUser = request()->user();

        if ($user->id === $currentUser->id) {
            return response()->json(['message' => 'Você não pode excluir seu próprio usuário.'], 422);
        }

        if ($user->is_master && !$currentUser->is_master) {
            return response()->json(['message' => 'Apenas outro MASTER pode excluir usuários MASTER.'], 403);
        }

        $user->delete();

        $this->logActivity($currentUser?->id, 'user_deleted', User::class, $id);

        return response()->json(['message' => 'Usuário excluído com sucesso.']);
    }

    public function resetPassword(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $newPassword = Str::random(12);

        $user->password = bcrypt($newPassword);
        $user->save();

        $this->logActivity($request->user()?->id, 'password_reset', User::class, $user->id);

        return response()->json([
            'message' => 'Senha redefinida com sucesso.',
            'nova_senha' => $newPassword,
            'aviso' => 'Esta senha é temporária. Solicite que o usuário altere após o login.',
        ]);
    }

    public function toggleMaster(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $currentUser = $request->user();

        if ($user->id === $currentUser->id) {
            return response()->json(['message' => 'Você não pode alterar seu próprio status MASTER.'], 422);
        }

        $user->is_master = !$user->is_master;
        $user->save();

        $this->logActivity($currentUser->id, 'toggle_master', User::class, $user->id, json_encode(['is_master' => $user->is_master]));

        return response()->json([
            'message' => $user->is_master ? 'Usuário definido como MASTER.' : 'Status MASTER removido.',
            'is_master' => $user->is_master,
        ]);
    }

    public function getPermissions(): JsonResponse
    {
        $permissions = Permission::where('guard_name', 'sanctum')
            ->orderBy('name')
            ->get(['id', 'name', 'description']);

        return response()->json(['permissions' => $permissions]);
    }

    public function getRoles(): JsonResponse
    {
        $roles = Role::where('guard_name', 'sanctum')
            ->with('permissions')
            ->get();

        return response()->json(['roles' => $roles]);
    }

    public function assignRole(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $user = User::findOrFail($id);
        $user->assignRole($request->role);

        $this->logActivity($request->user()?->id, 'role_assigned', User::class, $user->id, "role: {$request->role}");

        return response()->json([
            'message' => 'Role atribuída com sucesso.',
            'roles' => $user->getRoleNames(),
        ]);
    }

    public function removeRole(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $user = User::findOrFail($id);
        $user->removeRole($request->role);

        $this->logActivity($request->user()?->id, 'role_removed', User::class, $user->id, "role: {$request->role}");

        return response()->json([
            'message' => 'Role removida com sucesso.',
            'roles' => $user->getRoleNames(),
        ]);
    }

    private function logActivity(?int $userId, string $action, ?string $modelType, ?int $modelId, ?string $reason = null): void
    {
        try {
            DB::table('activity_log')->insert([
                'description' => $action,
                'subject_type' => $modelType,
                'subject_id' => $modelId,
                'causer_type' => User::class,
                'causer_id' => $userId,
                'properties' => json_encode(['reason' => $reason]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Silently fail - activity log is not critical
        }
    }
}