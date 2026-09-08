<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Disable CSRF verification for ALL tests.
     *
     * Pest tests submit POST/PUT/DELETE requests without a CSRF token —
     * CSRF protection is the browser's job, not the test's. Without this,
     * every POST/PUT/DELETE test fails with HTTP 419 (TokenMismatchException)
     * before the controller even runs.
     *
     * === Why this lives here, not in Pest.php or bootstrap/app.php ===
     *
     * We tried two other approaches that didn't work:
     *
     * 1. bootstrap/app.php: `if (Env::get('APP_ENV') === 'testing') { ... }`
     *    → Ran at boot time, but the env var from phpunit.xml wasn't reliably
     *      available when the withMiddleware closure executed. Test count
     *      stayed at 65 failed.
     *
     * 2. tests/Pest.php: `beforeEach(fn () => $this->withoutMiddleware(...))`
     *    → Standard Laravel testing pattern, but didn't take effect on the
     *      user's Windows + Docker setup. Likely a Pest scope issue with
     *      `->in('Feature')` not applying to all subdirectories or something
     *      env-related. Test count stayed at 65 failed.
     *
     * 3. THIS FILE (TestCase.php setUp) — runs after Laravel is fully
     *    bootstrapped, before each test, on EVERY test (no scoping issues).
     *    This is the bulletproof approach.
     *
     * `withoutMiddleware($cls)` removes ONLY the named class from the
     * middleware stack for the current test, leaving every other middleware
     * (auth, permissions, superadmin, Inertia) intact.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
    }
}

