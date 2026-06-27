<?php

namespace App\Services;

use App\Models\License;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LicenseService
{
    protected string $cachePrefix = 'license:';
    protected int $cacheTtl = 3600;

    public function generateKey(string $type = 'community'): string
    {
        $prefix = match ($type) {
            'trial' => 'SCHF-TRIAL',
            'community' => 'SCHF-COMM',
            'enterprise' => 'SCHF-ENT',
            default => 'SCHF',
        };

        $segments = [
            strtoupper(Str::random(4)),
            strtoupper(Str::random(4)),
            strtoupper(Str::random(4)),
            strtoupper(Str::random(4)),
        ];

        return $prefix . '-' . implode('-', $segments);
    }

    public function activate(array $data): array
    {
        $key = $data['key'] ?? null;
        if (!$key) {
            return ['success' => false, 'message' => 'Chave de licença obrigatória'];
        }

        $license = License::where('key', $key)->first();
        if (!$license) {
            return ['success' => false, 'message' => 'Licença não encontrada'];
        }

        if ($license->status === 'revoked') {
            return ['success' => false, 'message' => 'Licença revogada'];
        }

        if ($license->status === 'suspended') {
            return ['success' => false, 'message' => 'Licença suspensa'];
        }

        if ($license->isExpired()) {
            $license->update(['status' => 'expired']);
            return ['success' => false, 'message' => 'Licença expirada'];
        }

        if ($license->activation_count >= $license->max_activations) {
            return ['success' => false, 'message' => 'Limite de ativações atingido'];
        }

        $license->update([
            'status' => 'active',
            'activated_at' => $license->activated_at ?? now(),
            'organization_id' => $data['organization_id'] ?? $license->organization_id,
            'activation_count' => $license->activation_count + 1,
            'last_validated_at' => now(),
        ]);

        $this->clearCache($license->key);

        return [
            'success' => true,
            'license' => $license->toArray(),
            'message' => 'Licença ativada com sucesso',
        ];
    }

    public function validate(string $key): array
    {
        $cached = Cache::get($this->cachePrefix . $key);
        if ($cached) {
            return $cached;
        }

        $license = License::where('key', $key)->first();
        if (!$license) {
            $result = ['valid' => false, 'reason' => 'Licença não encontrada'];
            Cache::put($this->cachePrefix . $key, $result, 300);
            return $result;
        }

        $license->update([
            'last_validated_at' => now(),
            'validation_count' => $license->validation_count + 1,
        ]);

        $result = [
            'valid' => $license->isActive(),
            'type' => $license->type,
            'status' => $license->status,
            'expires_at' => $license->expires_at?->toISOString(),
            'features' => $license->features ?? [],
            'reason' => match (true) {
                $license->status === 'revoked' => 'Licença revogada',
                $license->status === 'suspended' => 'Licença suspensa',
                $license->isExpired() => 'Licença expirada',
                default => null,
            },
        ];

        Cache::put($this->cachePrefix . $key, $result, $this->cacheTtl);

        return $result;
    }

    public function suspend(int $licenseId, ?string $reason = null): array
    {
        $license = License::find($licenseId);
        if (!$license) {
            return ['success' => false, 'message' => 'Licença não encontrada'];
        }

        $license->update([
            'status' => 'suspended',
            'metadata' => array_merge($license->metadata ?? [], [
                'suspended_at' => now()->toISOString(),
                'suspended_reason' => $reason,
            ]),
        ]);

        $this->clearCache($license->key);

        return ['success' => true, 'message' => 'Licença suspensa'];
    }

    public function revoke(int $licenseId, ?string $reason = null): array
    {
        $license = License::find($licenseId);
        if (!$license) {
            return ['success' => false, 'message' => 'Licença não encontrada'];
        }

        $license->update([
            'status' => 'revoked',
            'metadata' => array_merge($license->metadata ?? [], [
                'revoked_at' => now()->toISOString(),
                'revoked_reason' => $reason,
            ]),
        ]);

        $this->clearCache($license->key);

        return ['success' => true, 'message' => 'Licença revogada'];
    }

    public function getLicenseInfo(): array
    {
        $license = License::valid()->first();

        if (!$license) {
            return [
                'licensed' => false,
                'type' => 'none',
                'status' => 'unlicensed',
            ];
        }

        return [
            'licensed' => true,
            'type' => $license->type,
            'status' => $license->status,
            'expires_at' => $license->expires_at?->toISOString(),
            'features' => $license->features ?? [],
            'customer' => $license->customer_name,
        ];
    }

    public function createTrial(int $organizationId, int $days = 14): array
    {
        $key = $this->generateKey('trial');
        $license = License::create([
            'key' => $key,
            'type' => 'trial',
            'status' => 'active',
            'organization_id' => $organizationId,
            'activated_at' => now(),
            'expires_at' => now()->addDays($days),
            'max_activations' => 1,
            'features' => ['all'],
        ]);

        return [
            'success' => true,
            'license' => $license->toArray(),
            'message' => "Trial de {$days} dias criado",
        ];
    }

    protected function clearCache(string $key): void
    {
        Cache::forget($this->cachePrefix . $key);
    }
}
