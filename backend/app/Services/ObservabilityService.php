<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Http;

class ObservabilityService
{
    public function healthCheck(): array
    {
        $checks = [];

        $checks['database'] = $this->checkDatabase();
        $checks['redis'] = $this->checkRedis();
        $checks['docker'] = $this->checkDocker();
        $checks['storage'] = $this->checkStorage();
        $checks['queue'] = $this->checkQueue();

        $healthy = !in_array(false, array_column($checks, 'healthy'));

        return [
            'status' => $healthy ? 'healthy' : 'degraded',
            'timestamp' => now()->toISOString(),
            'checks' => $checks,
        ];
    }

    public function metrics(): array
    {
        return [
            'timestamp' => now()->toISOString(),
            'system' => $this->getSystemMetrics(),
            'application' => $this->getApplicationMetrics(),
            'database' => $this->getDatabaseMetrics(),
        ];
    }

    protected function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $duration = round((microtime(true) - $start) * 1000, 2);

            return [
                'healthy' => true,
                'latency_ms' => $duration,
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function checkRedis(): array
    {
        try {
            $start = microtime(true);
            Redis::ping();
            $duration = round((microtime(true) - $start) * 1000, 2);

            return [
                'healthy' => true,
                'latency_ms' => $duration,
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function checkDocker(): array
    {
        try {
            $response = Http::timeout(5)->get('http://localhost:9080/api/health');
            return [
                'healthy' => $response->successful(),
                'status' => $response->status(),
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'error' => 'Backend não acessível',
            ];
        }
    }

    protected function checkStorage(): array
    {
        $path = storage_path('app');
        $free = disk_free_space($path);
        $total = disk_total_space($path);
        $used = $total - $free;
        $percent = round(($used / $total) * 100, 1);

        return [
            'healthy' => $percent < 90,
            'free_bytes' => $free,
            'total_bytes' => $total,
            'used_percent' => $percent,
        ];
    }

    protected function checkQueue(): array
    {
        try {
            $size = Redis::llen('queues:default');
            return [
                'healthy' => true,
                'pending_jobs' => $size,
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'error' => 'Queue não disponível',
            ];
        }
    }

    protected function getSystemMetrics(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'memory_peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'uptime' => $this->getUptime(),
        ];
    }

    protected function getApplicationMetrics(): array
    {
        return [
            'organizations' => \App\Models\Organization::count(),
            'users' => \App\Models\User::count(),
            'active_organizations' => \App\Models\Organization::where('is_active', true)->count(),
            'config_version' => config('app.version', 'unknown'),
        ];
    }

    protected function getDatabaseMetrics(): array
    {
        try {
            $prefix = config('database.connections.mysql.prefix', '');
            $tables = DB::select("SHOW TABLES LIKE '{$prefix}%'");
            return [
                'table_count' => count($tables),
                'prefix' => $prefix,
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    protected function getUptime(): string
    {
        $startTime = defined('APP_START_TIME') ? APP_START_TIME : now();
        $diff = now()->diff($startTime);
        return "{$diff->d}d {$diff->h}h {$diff->i}m";
    }
}