<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'is_master',
        'is_system_admin',
        'master_token',
        'last_master_login',
        'organization_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'master_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_master' => 'boolean',
            'is_system_admin' => 'boolean',
            'last_master_login' => 'datetime',
        ];
    }

    public function isMasterAdmin(): bool
    {
        return $this->is_master === true;
    }

    public function isSystemAdmin(): bool
    {
        return $this->is_system_admin === true || $this->is_master === true;
    }

    public function canAccessAdmin(): bool
    {
        return $this->is_master === true || $this->hasRole('super_admin');
    }

    public function canPerformCriticalActions(): bool
    {
        return $this->is_master === true;
    }

    public function generateMasterToken(): string
    {
        $this->master_token = Hash::make(bin2hex(random_bytes(32)));
        $this->save();
        return $this->master_token;
    }

    public function clearMasterToken(): void
    {
        $this->master_token = null;
        $this->last_master_login = now();
        $this->save();
    }

    public function verifyMasterToken(string $token): bool
    {
        if (!$this->master_token) {
            return false;
        }
        return Hash::check($token, $this->master_token);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}