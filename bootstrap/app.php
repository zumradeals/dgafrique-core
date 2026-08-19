<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use App\Http\Middleware\RequireCoreMember;
use App\Http\Middleware\RequirePortalAdministrator;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            require base_path('routes/project_brain.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'core.member' => RequireCoreMember::class,
            'portal.admin' => RequirePortalAdministrator::class,
        ]);
        $middleware->trimStrings(except: ['secret', 'password', 'password_confirmation', 'code']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['secret', 'password', 'password_confirmation', 'code']);
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
