<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\SuperadminMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Env;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        // Pest tests submit POST/PUT/DELETE requests without a CSRF token
        // (CSRF protection is the browser's job, not the test's). In the
        // testing environment, exempt every route from CSRF verification so
        // tests don't get HTTP 419 responses. phpunit.xml sets APP_ENV=testing.
        //
        // IMPORTANT: we must read APP_ENV directly from $_ENV via the Env
        // helper, NOT via app()->environment() — the env repository binding
        // is not yet registered when the withMiddleware closure runs, so
        // calling app()->environment() here throws
        // "Target class [env] does not exist" (BindingResolutionException).
        if (Env::get('APP_ENV') === 'testing') {
            $middleware->validateCsrfTokens(except: ['*']);
        }

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));

        $middleware->alias([
            'permission' => PermissionMiddleware::class,
            'superadmin' => SuperadminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
