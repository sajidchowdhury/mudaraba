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
        // === Why we use a custom MUDARABA_TESTING env var (not APP_ENV) ===
        //
        // We tried 4 other approaches that did NOT work:
        //
        // 1. tests/TestCase.php setUp() with $this->withoutMiddleware(ValidateCsrfToken::class)
        //    → withoutMiddleware() only disables middleware on the GLOBAL stack,
        //      NOT middleware applied via the `web` middleware GROUP. Real app
        //      routes in routes/web.php get CSRF via the `web` group. No-op. 35 tests still 419.
        //
        // 2. tests/Pest.php beforeEach with $this->withoutMiddleware(...)
        //    → Same issue. 65 tests still 419.
        //
        // 3. bootstrap/app.php with Env::get('APP_ENV') === 'testing'
        //    → Env::get() goes through Laravel's env repository, which isn't
        //      initialized at withMiddleware boot stage. Didn't take effect.
        //
        // 4. bootstrap/app.php with getenv('APP_ENV') === 'testing'
        //    → APP_ENV gets overwritten by Dotenv loading .env (which has
        //      APP_ENV=local). Even though Dotenv's default is "don't
        //      overwrite existing env vars", some versions/configs do.
        //      Diagnostic test still showed 419.
        //
        // 5. THIS APPROACH: custom MUDARABA_TESTING env var
        //    → phpunit.xml sets MUDARABA_TESTING=1 via <env> tag (putenv).
        //    → .env does NOT have MUDARABA_TESTING, so Dotenv can't overwrite.
        //    → getenv('MUDARABA_TESTING') reliably returns '1' during tests.
        //    → In prod/dev, the var is unset, so getenv returns false → no bypass.
        //
        // validateCsrfTokens(except: ['*']) disables CSRF for ALL routes
        // (global + group + route-level), which is what we want for tests.
        if (getenv('MUDARABA_TESTING') === '1') {
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
