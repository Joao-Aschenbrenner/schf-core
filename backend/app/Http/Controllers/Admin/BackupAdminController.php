<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\BackupController;
use App\Models\Backup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class BackupAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $type = $request->input('type');
        $status = $request->input('status');

        $query = Backup::query()->orderBy('created_at', 'desc');

        if ($type) {
            $query->where('type', $type);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $backups = $query->paginate($perPage);

        return response()->json($backups);
    }

    public function manualBackup(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:full,database,files',
            'password' => 'required|string|min:8',
        ]);

        $user = $request->user();
        if (!$user->is_master) {
            return response()->json(['message' => 'Apenas usuários MASTER podem criar backups.'], 403);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Senha incorreta.'], 401);
        }

        $type = $request->input('type');
        $backupName = "manual_backup_{$type}_" . date('Y-m-d_His');

        $backup = Backup::create([
            'name' => $backupName,
            'type' => $type,
            'status' => 'pending',
            'user_id' => $user->id,
        ]);

        $this->logActivity($user->id, 'backup_started', Backup::class, $backup->id, "type: $type");

        return response()->json([
            'message' => 'Backup iniciado.',
            'backup_id' => $backup->id,
            'status' => 'pending',
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $backup = Backup::findOrFail($id);

        return response()->json(['backup' => $backup]);
    }

    public function download(int $id): JsonResponse
    {
        $backup = Backup::findOrFail($id);

        if ($backup->status !== 'completed' || !$backup->file_path) {
            return response()->json(['message' => 'Backup não disponível para download.'], 422);
        }

        if (!file_exists($backup->file_path)) {
            return response()->json(['message' => 'Arquivo de backup não encontrado.'], 404);
        }

        $this->logActivity(request()->user()?->id, 'backup_downloaded', Backup::class, $id);

        return response()->download($backup->file_path, $backup->file_name ?? basename($backup->file_path));
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();
        if (!$user->is_master) {
            return response()->json(['message' => 'Apenas usuários MASTER podem restaurar backups.'], 403);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Senha incorreta.'], 401);
        }

        $backup = Backup::findOrFail($id);

        if ($backup->status !== 'completed') {
            return response()->json(['message' => 'Backup não está completo.'], 422);
        }

        if (!$backup->file_path || !file_exists($backup->file_path)) {
            return response()->json(['message' => 'Arquivo de backup não encontrado.'], 404);
        }

        $this->logActivity($user->id, 'restore_started', Backup::class, $id);

        $backup->status = 'restoring';
        $backup->save();

        return response()->json([
            'message' => 'Restore iniciado. O sistema estará indisponível durante o processo.',
            'backup_id' => $id,
            'status' => 'restoring',
            'warning' => 'Aguarde a conclusão do restore.',
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $user = request()->user();
        if (!$user->is_master) {
            return response()->json(['message' => 'Apenas usuários MASTER podem excluir backups.'], 403);
        }

        $backup = Backup::findOrFail($id);

        if ($backup->file_path && file_exists($backup->file_path)) {
            unlink($backup->file_path);
        }

        $backup->delete();

        $this->logActivity($user->id, 'backup_deleted', Backup::class, $id);

        return response()->json(['message' => 'Backup excluído com sucesso.']);
    }

    private function logActivity(?int $userId, string $action, ?string $modelType, ?int $modelId, ?string $reason = null): void
    {
        try {
            DB::table('activity_log')->insert([
                'description' => $action,
                'subject_type' => $modelType,
                'subject_id' => $modelId,
                'causer_type' => 'App\\Models\\User',
                'causer_id' => $userId,
                'properties' => json_encode(['reason' => $reason]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Silently fail
        }
    }
}