<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)
            ->where('is_active', true)
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        activity()
            ->causedBy($user)
            ->log('login');

        return [
            'user' => $user->load('roles', 'permissions'),
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();
        if ($token && !($token instanceof \Laravel\Sanctum\TransientToken)) {
            $token->delete();
        }

        activity()
            ->causedBy($user)
            ->log('logout');
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Senha atual incorreta.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        activity()
            ->causedBy($user)
            ->log('password_changed');
    }

    public function revokeAllTokens(User $user): void
    {
        $user->tokens()->delete();

        activity()
            ->causedBy($user)
            ->log('all_tokens_revoked');
    }
}
