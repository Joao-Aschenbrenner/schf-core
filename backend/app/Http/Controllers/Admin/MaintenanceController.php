<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MaintenanceController extends Controller
{
    public function clearCache(Request $request): JsonResponse
    {
        $type = $request->input('type', 'all');

        $user = $request->user();
        if (!$user->is_master) {
            return response()->json(['message' => 'Apenas usuários MASTER podem executar manutenção.'], 403);
        }

        $allowed = ['all', 'config', 'route', 'view', 'cache', 'optimize', 'compiled', 'events'];
        if (!in_array($type, $allowed)) {
            return response()->json(['message' => 'Tipo de limpeza inválido.', 'allowed' => $allowed], 422);
        }

        $this->logActivity($user->id, 'maintenance_cache_clear', null, null, "type: $type");

        switch ($type) {
            case 'all':
                Artisan::call('optimize:clear');
                $message = 'Todos os caches limpos com sucesso.';
                break;
            case 'config':
                Artisan::call('config:clear');
                $message = 'Config cache limpo.';
                break;
            case 'route':
                Artisan::call('route:clear');
                $message = 'Route cache limpo.';
                break;
            case 'view':
                Artisan::call('view:clear');
                $message = 'View cache limpo.';
                break;
            case 'cache':
                Artisan::call('cache:clear');
                $message = 'Application cache limpo.';
                break;
            case 'optimize':
                Artisan::call('optimize');
                $message = 'Cache de otimização gerado.';
                break;
            case 'compiled':
                Artisan::call('clear-compiled');
                $message = 'Compiled files limpos.';
                break;
            case 'events':
                Artisan::call('event:clear');
                $message = 'Events cache limpos.';
                break;
            default:
                $message = 'Nenhuma ação executada.';
        }

        return response()->json([
            'message' => $message,
            'type' => $type,
            'executed_by' => $user->id,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function restartQueue(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->is_master) {
            return response()->json(['message' => 'Apenas usuários MASTER podem reiniciar filas.'], 403);
        }

        $this->logActivity($user->id, 'maintenance_queue_restart', null, null);

        Artisan::call('queue:restart');

        return response()->json([
            'message' => 'Queue reiniciada com sucesso. Workers serão reabastecidos.',
            'executed_by' => $user->id,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function clearSessions(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->is_master) {
            return response()->json(['message' => 'Apenas usuários MASTER podem limpar sessões.'], 403);
        }

        $this->logActivity($user->id, 'maintenance_sessions_clear', null, null);

        try {
            Artisan::call('session:table');
            DB::table(config('session.table', 'sessions'))->truncate();
            $message = 'Sessões limpas com sucesso.';
        } catch (\Exception $e) {
            $message = 'Erro ao limpar sessões: ' . $e->getMessage();
        }

        return response()->json([
            'message' => $message,
            'executed_by' => $user->id,
        ]);
    }

    public function clearLogs(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->is_master) {
            return response()->json(['message' => 'Apenas usuários MASTER podem limpar logs.'], 403);
        }

        $confirm = $request->input('confirm', false);
        if (!$confirm) {
            return response()->json([
                'message' => 'Confirmação necessária para limpar logs.',
                'confirm' => true,
            ], 422);
        }

        $this->logActivity($user->id, 'maintenance_logs_clear', null, null);

        $logPath = storage_path('logs/laravel.log');
        if (file_exists($logPath)) {
            file_put_contents($logPath, '');
        }

        return response()->json([
            'message' => 'Logs limpos com sucesso.',
            'executed_by' => $user->id,
        ]);
    }

    public function systemInfo(): JsonResponse
    {
        $info = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'environment' => app()->environment(),
            'debug_mode' => config('app.debug'),
            'timezone' => config('app.timezone'),
            'locale' => app()->getLocale(),
            'host' => gethostname(),
            'os' => PHP_OS,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'extensions' => get_loaded_extensions(),
        ];

        return response()->json($info);
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