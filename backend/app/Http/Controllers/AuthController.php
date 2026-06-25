<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->input('email'),
            $request->input('password')
        );

        return response()->json($result);
    }

    public function logout(): JsonResponse
    {
        $this->authService->logout(auth()->user());

        return response()->json(['message' => 'Logout realizado com sucesso.']);
    }

    public function me(): JsonResponse
    {
        return response()->json([
            'data' => auth()->user()->load('roles', 'permissions'),
        ]);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $this->authService->changePassword(
            auth()->user(),
            $request->input('current_password'),
            $request->input('new_password')
        );

        return response()->json(['message' => 'Senha alterada com sucesso.']);
    }

    public function revokeTokens(): JsonResponse
    {
        $this->authService->revokeAllTokens(auth()->user());

        return response()->json(['message' => 'Todas as sessões foram revogadas.']);
    }
}
