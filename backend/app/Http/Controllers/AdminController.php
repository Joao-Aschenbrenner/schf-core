<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function masterLogin(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)
            ->where('is_active', true)
            ->first();

        if (!$user) {
            return response()->json(['message' => 'UsuÃ¡rio nÃ£o encontrado ou inativo.'], 401);
        }

        if (!$user->is_master) {
            return response()->json(['message' => 'Acesso negado. Este usuÃ¡rio nÃ£o possui privilÃ©gios MASTER.'], 403);
        }

        if (!Hash::check($request->password, $user->password)) {
            $this->logActivity($user->id, 'master_login_failed', null, null, 'Senha incorreta');
            return response()->json(['message' => 'Credenciais invÃ¡lidas.'], 401);
        }

        $token = $user->createToken('master-token-'.$user->id, ['*'], now()->addHours(4));

        $user->last_master_login = now();
        $user->save();

        $this->logActivity($user->id, 'master_login', null, null);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_master' => $user->is_master,
            ],
            'token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at,
        ]);
    }

    public function masterLogout(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user) {
            $this->logActivity($user->id, 'master_logout', null, null);
            $user->currentAccessToken()?->delete();
        }
        return response()->json(['message' => 'Logout MASTER realizado.']);
    }

    public function dashboard(): JsonResponse
    {
        $stats = [
            'database' => [
                'fornecedores' => DB::table('historico_fornecedores')->count(),
                'notas' => DB::table('historico_notas')->count(),
                'operacoes_banco' => DB::table('historico_operacoes_banco')->count(),
                'baixas_perdidas' => DB::table('historico_baixas_perdidas')->count(),
                'caixas' => DB::table('historico_caixa')->count(),
                'usuarios' => DB::table('historico_usuarios')->count(),
                'contas' => DB::table('historico_contas')->count(),
                'convenios' => DB::table('historico_convenios')->count(),
            ],
            'operacional' => [
                'receivables' => DB::table('receivables')->count(),
                'provisions' => DB::table('provisions')->count(),
                'cash_registers' => DB::table('cash_registers')->count(),
                'bank_investments' => DB::table('bank_investments')->count(),
            ],
            'usuarios_sistema' => [
                'total' => User::count(),
                'ativos' => User::where('is_active', true)->count(),
                'inativos' => User::where('is_active', false)->count(),
                'masters' => User::where('is_master', true)->count(),
            ],
            'backup' => $this->getBackupStats(),
            'security' => $this->getSecurityStats(),
        ];

        return response()->json($stats);
    }

    public function systemHealth(): JsonResponse
    {
        $checks = [];

        // Backend API
        try {
            $check = app()->runningInConsole()
                ? ['status' => 'unknown', 'latency_ms' => null]
                : ['status' => 'ok', 'latency_ms' => 0];
            $start = microtime(true);
            $health = app()->make('cache')->get('health_check');
            $latency = round((microtime(true) - $start) * 1000, 2);
            $checks['api'] = ['status' => 'ok', 'latency_ms' => $latency];
        } catch (\Exception $e) {
            $checks['api'] = ['status' => 'error', 'message' => $e->getMessage()];
        }

        // MySQL
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $latency = round((microtime(true) - $start) * 1000, 2);
            $checks['mysql'] = ['status' => 'ok', 'latency_ms' => $latency];
        } catch (\Exception $e) {
            $checks['mysql'] = ['status' => 'error', 'message' => $e->getMessage()];
        }

        // Redis
        try {
            $start = microtime(true);
            Redis::ping();
            $latency = round((microtime(true) - $start) * 1000, 2);
            $checks['redis'] = ['status' => 'ok', 'latency_ms' => $latency];
        } catch (\Exception $e) {
            $checks['redis'] = ['status' => 'error', 'message' => $e->getMessage()];
        }

        // Queue (check if there's a recent job)
        try {
            $queueStatus = Cache::get('queue:status', 'unknown');
            $checks['queue'] = [
                'status' => $queueStatus === 'running' ? 'ok' : 'warning',
                'info' => $queueStatus,
            ];
        } catch (\Exception $e) {
            $checks['queue'] = ['status' => 'error', 'message' => $e->getMessage()];
        }

        // Storage
        try {
            $storageUsed = $this->getStorageUsage();
            $checks['storage'] = [
                'status' => 'ok',
                'used_bytes' => $storageUsed,
                'used_formatted' => $this->formatBytes($storageUsed),
            ];
        } catch (\Exception $e) {
            $checks['storage'] = ['status' => 'error', 'message' => $e->getMessage()];
        }

        // Docker containers
        try {
            $containers = $this->getContainersStatus();
            $checks['containers'] = [
                'status' => 'ok',
                'containers' => $containers,
            ];
        } catch (\Exception $e) {
            $checks['containers'] = ['status' => 'warning', 'message' => 'NÃ£o foi possÃ­vel verificar containers'];
        }

        $overallStatus = collect($checks)->every(fn($c) => ($c['status'] ?? null) === 'ok')
            ? 'ok'
            : (collect($checks)->contains(fn($c) => ($c['status'] ?? null) === 'error') ? 'error' : 'warning');

        return response()->json([
            'overall' => $overallStatus,
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function clearCache(Request $request): JsonResponse
    {
        $type = $request->input('type', 'all');

        $allowed = ['all', 'config', 'route', 'view', 'cache', 'optimize'];
        if (!in_array($type, $allowed)) {
            return response()->json(['message' => 'Tipo de limpeza invÃ¡lido.'], 422);
        }

        $this->logActivity($request->user()?->id, 'cache_clear', null, null, "type: $type");

        switch ($type) {
            case 'all':
                Artisan::call('optimize:clear');
                break;
            case 'config':
                Artisan::call('config:clear');
                break;
            case 'route':
                Artisan::call('route:clear');
                break;
            case 'view':
                Artisan::call('view:clear');
                break;
            case 'cache':
                Artisan::call('cache:clear');
                break;
            case 'optimize':
                Artisan::call('optimize');
                break;
        }

        return response()->json([
            'message' => "Cache limpo com sucesso (tipo: $type)",
            'type' => $type,
        ]);
    }

    public function getLogs(Request $request): JsonResponse
    {
        $level = $request->input('level', 'all');
        $lines = $request->input('lines', 100);
        $search = $request->input('search');

        $logPath = storage_path('logs/laravel.log');

        if (!file_exists($logPath)) {
            return response()->json(['logs' => [], 'total' => 0]);
        }

        $content = file_get_contents($logPath);
        $logLines = explode("\n", trim($content));
        $filtered = [];

        foreach (array_slice(array_reverse($logLines), 0, $lines * 2) as $line) {
            if (trim($line) === '') continue;

            if ($level !== 'all') {
                $levelUpper = strtoupper($level);
                if (!preg_match("/\\[$levelUpper\\]/", $line) && !preg_match("/$levelUpper:/", $line)) {
                    continue;
                }
            }

            if ($search && stripos($line, $search) === false) {
                continue;
            }

            $filtered[] = $this->parseLogLine($line);
        }

        $filtered = array_slice($filtered, 0, $lines);

        return response()->json([
            'logs' => $filtered,
            'total' => count($filtered),
            'level' => $level,
            'lines' => $lines,
        ]);
    }

    public function getContainers(): JsonResponse
    {
        $containers = $this->getContainersStatus();
        return response()->json(['containers' => $containers]);
    }

    public function restartContainer(Request $request, string $name): JsonResponse
    {
        $user = $request->user();
        if (!$user->is_master) {
            return response()->json(['message' => 'Apenas usuÃ¡rios MASTER podem reiniciar containers.'], 403);
        }

        $allowedContainers = ['schf-nginx', 'schf-backend', 'schf-frontend', 'schf-mysql', 'schf-redis', 'schf-queue'];

        if (!in_array($name, $allowedContainers)) {
            return response()->json(['message' => 'Container nÃ£o permitido.'], 403);
        }

        $this->logActivity($user->id, 'container_restart', 'Container', $name);

        exec("docker restart $name 2>&1", $output, $exitCode);

        if ($exitCode !== 0) {
            return response()->json(['message' => 'Erro ao reiniciar container: ' . implode(' ', $output)], 500);
        }

        return response()->json(['message' => "Container $name reiniciado com sucesso."]);
    }

    public function getContainerLogs(Request $request, string $name): JsonResponse
    {
        $lines = $request->input('lines', 100);

        $allowedContainers = ['schf-nginx', 'schf-backend', 'schf-frontend', 'schf-mysql', 'schf-redis', 'schf-queue'];

        if (!in_array($name, $allowedContainers)) {
            return response()->json(['message' => 'Container nÃ£o permitido.'], 403);
        }

        exec("docker logs --tail $lines $name 2>&1", $output);
        $logs = implode("\n", $output);

        return response()->json([
            'container' => $name,
            'logs' => $logs,
            'lines' => $lines,
        ]);
    }

    public function restartQueue(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->is_master) {
            return response()->json(['message' => 'Apenas usuÃ¡rios MASTER podem reiniciar filas.'], 403);
        }

        $this->logActivity($user->id, 'queue_restart', null, null);

        Artisan::call('queue:restart');

        return response()->json(['message' => 'Fila reiniciada com sucesso.']);
    }

    private function getBackupStats(): array
    {
        try {
            $lastBackup = DB::table('backups')
                ->where('status', 'completed')
                ->orderBy('completed_at', 'desc')
                ->first();

            $totalBackups = DB::table('backups')
                ->where('status', 'completed')
                ->count();

            $totalSize = DB::table('backups')
                ->where('status', 'completed')
                ->sum('file_size');

            $nextScheduled = Cache::get('backup:next_scheduled');

            return [
                'ultimo_backup' => $lastBackup?->completed_at,
                'proximo_backup' => $nextScheduled,
                'total_backups' => $totalBackups,
                'tamanho_total_bytes' => $totalSize,
                'tamanho_total_formatado' => $this->formatBytes($totalSize),
            ];
        } catch (\Exception $e) {
            return [
                'ultimo_backup' => null,
                'proximo_backup' => null,
                'total_backups' => 0,
                'tamanho_total_bytes' => 0,
                'tamanho_total_formatado' => '0 B',
            ];
        }
    }

    private function getSecurityStats(): array
    {
        try {
            $failedLogins = DB::table('activity_log')
                ->where('description', 'like', '%login%failed%')
                ->where('created_at', '>=', now()->subDay())
                ->count();

            $activeUsers = DB::table('personal_access_tokens')
                ->where('last_used_at', '>=', now()->subHour())
                ->distinct('tokenable_id')
                ->count('tokenable_id');

            $lastAdminLogin = DB::table('activity_log')
                ->where('description', 'master_login')
                ->where('causer_type', User::class)
                ->orderBy('created_at', 'desc')
                ->value('created_at');

            return [
                'falhas_login_24h' => $failedLogins,
                'usuarios_ativos_1h' => $activeUsers,
                'ultimo_login_admin' => $lastAdminLogin,
            ];
        } catch (\Exception $e) {
            return [
                'falhas_login_24h' => 0,
                'usuarios_ativos_1h' => 0,
                'ultimo_login_admin' => null,
            ];
        }
    }

    private function getContainersStatus(): array
    {
        $containerNames = ['schf-nginx', 'schf-backend', 'schf-frontend', 'schf-mysql', 'schf-redis', 'schf-queue', 'schf-pma'];

        $status = [];
        foreach ($containerNames as $name) {
            exec("docker inspect -f '{{.State.Status}}' $name 2>/dev/null", $output, $exitCode);
            $containerStatus = $exitCode === 0 ? trim(implode('', $output)) : 'unknown';
            exec("docker inspect -f '{{.State.Health.Status}}' $name 2>/dev/null", $healthOutput);
            $healthStatus = $exitCode === 0 ? trim(implode('', $healthOutput)) : 'none';
            exec("docker ps --filter \"name=$name\" --format \"{{.Status}}\" 2>/dev/null", $uptimeOutput);

            $status[] = [
                'name' => $name,
                'status' => $containerStatus,
                'health' => $healthStatus ?: 'none',
                'uptime' => trim(implode('', $uptimeOutput)),
                'running' => $containerStatus === 'running',
            ];

            unset($output);
        }

        return $status;
    }

    private function getStorageUsage(): int
    {
        $storagePath = storage_path();
        $size = 0;
        foreach (glob_recursive($storagePath . '/*') as $file) {
            if (is_file($file)) {
                $size += filesize($file);
            }
        }
        return $size;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    private function parseLogLine(string $line): array
    {
        $result = [
            'raw' => $line,
            'timestamp' => null,
            'level' => 'info',
            'message' => $line,
        ];

        if (preg_match('/^\[(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}[^\]]*)\]\s*\[(\w+)\]/', $line, $matches)) {
            $result['timestamp'] = $matches[1];
            $result['level'] = strtolower($matches[2]);
            $result['message'] = preg_replace('/^\[([^\]]+)\]\s*\[' . preg_quote($matches[2], '/') . '\]/', '', $line);
            $result['message'] = trim($result['message']);
        }

        return $result;
    }

    private function logActivity(int $userId, string $action, ?string $modelType = null, ?string $modelId = null, ?string $reason = null): void
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
            Log::warning("Failed to log activity: " . $e->getMessage());
        }
    }
}

function glob_recursive($pattern, $flags = 0): array
{
    $files = glob($pattern, $flags);
    foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR) as $dir) {
        $files = array_merge($files, glob_recursive($dir . '/' . basename($pattern), $flags));
    }
    return $files ?? [];
}
