<?php

use App\Http\Middleware\EnsureUserHasAdminAccess;
use App\Http\Middleware\EnsureUserCanPerformCriticalAction;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
        $middleware->alias([
            'access-admin' => EnsureUserHasAdminAccess::class,
            'critical-actions' => EnsureUserCanPerformCriticalAction::class,
        ]);
    })
    ->withProviders([
        \Spatie\Permission\PermissionServiceProvider::class,
    ])
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
