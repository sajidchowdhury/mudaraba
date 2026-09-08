<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\SuperadminMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

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

        // Disable CSRF verification in the testing environment.
        //
        // Pest tests submit POST/PUT/DELETE requests without a CSRF token.
        // CSRF protection is the browser's job, not the test's. Without this
        // exemption, every POST/PUT/DELETE test fails with HTTP 419.
        //
        // === Why this approach (not TestCase setUp or Pest beforeEach) ===
        //
        // We tried 3 other approaches that did NOT fully work:
        //
        // 1. tests/TestCase.php setUp() with $this->withoutMiddleware(ValidateCsrfToken::class)
        //    → This disables the middleware for the global stack, but NOT for
        //      middleware applied via the `web` middleware GROUP (which is how
        //      routes/web.php routes get CSRF). Result: 35 tests still 419.
        //
        // 2. tests/Pest.php beforeEach with $this->withoutMiddleware(...)
        //    → Same issue — withoutMiddleware doesn't affect group middleware.
        //      Result: 65 tests still 419.
        //
        // 3. bootstrap/app.php with Env::get('APP_ENV') === 'testing'
        //    → Env::get() goes through Laravel's env repository which may not
        //      be initialized at this boot stage. Didn't take effect.
        //
        // 4. THIS APPROACH: native PHP getenv('APP_ENV') === 'testing'
        //    → Reads the OS env var directly. PHPUnit's <env> tag in phpunit.xml
        //      calls putenv() BEFORE Laravel boots, so this value is reliably
        //      'testing' during test runs and 'local'/'production' otherwise.
        //      validateCsrfTokens(except: ['*']) disables CSRF for ALL routes
        //      (global + group + route-level), which is what we want for tests.
        if (getenv('APP_ENV') === 'testing') {
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
