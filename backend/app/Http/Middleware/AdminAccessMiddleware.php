<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasAdminAccess
{
    public function handle(Request $request, Closure $next, string $gateName = 'access-admin'): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (!Gate::allows($gateName, $user)) {
            return response()->json(['message' => 'Acesso negado. Privilégios administrativos necessários.'], 403);
        }

        return $next($request);
    }
}

class EnsureUserCanPerformCriticalAction
{
    public function handle(Request $request, Closure $next, string $gateName = 'critical-actions'): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (!Gate::allows($gateName, $user)) {
            return response()->json(['message' => 'Ação crítica. Apenas usuários MASTER podem executar.'], 403);
        }

        return $next($request);
    }
}