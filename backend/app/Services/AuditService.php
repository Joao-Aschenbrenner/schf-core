<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AuditService
{
    public function log(string $action, string $modelClass, ?int $modelId = null, array $old = [], array $new = [], ?int $userId = null): void
    {
        $audit = [
            'action' => $action,
            'model_class' => $modelClass,
            'model_id' => $modelId,
            'user_id' => $userId ?? auth()->id(),
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('audit')->info("Audit: {$action}", $audit);

        if (config('activitylog.enabled')) {
            activity()
                ->performedOn($modelClass::find($modelId))
                ->withProperties($audit)
                ->event($action)
                ->log("{$action} on {$modelClass}");
        }
    }

    public function logLogin(int $userId, bool $success, string $method = 'password'): void
    {
        $this->log($success ? 'login_success' : 'login_failed', \App\Models\User::class, $userId, [], [
            'method' => $method,
            'success' => $success,
        ]);
    }

    public function logLogout(int $userId): void
    {
        $this->log('logout', \App\Models\User::class, $userId);
    }

    public function logPasswordChange(int $userId): void
    {
        $this->log('password_changed', \App\Models\User::class, $userId);
    }

    public function logRoleAssigned(int $userId, array $roles): void
    {
        $this->log('role_assigned', \App\Models\User::class, $userId, [], ['roles' => $roles]);
    }

    public function logOrganizationActivated(int $orgId, bool $activated): void
    {
        $this->log($activated ? 'organization_activated' : 'organization_deactivated', \App\Models\Organization::class, $orgId, [], ['activated' => $activated]);
    }

    public function logDataExport(string $type, int $count): void
    {
        $this->log('data_export', null, null, [], ['type' => $type, 'record_count' => $count]);
    }

    public function logBackupCreated(string $filename, int $size): void
    {
        $this->log('backup_created', null, null, [], ['filename' => $filename, 'size_bytes' => $size]);
    }

    public function logSystemEvent(string $event, array $context = []): void
    {
        Log::channel('audit')->info("System: {$event}", $context);
    }
}