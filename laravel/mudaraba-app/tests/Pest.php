<?php

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Bind all tests to the Laravel TestCase + auto-refresh the DB
uses(TestCase::class, RefreshDatabase::class)->in('Feature', 'Unit');

// Disable CSRF protection for every Feature test.
//
// Pest tests submit POST/PUT/DELETE requests without a CSRF token —
// CSRF protection is the browser's job, not the test's. Without this,
// every POST/PUT/DELETE test fails with HTTP 419 (TokenMismatchException)
// before the controller even runs.
//
// This is the standard Laravel testing pattern. `withoutMiddleware($cls)`
// removes ONLY the named class from the middleware stack for the current
// test, leaving every other middleware (auth, permissions, etc.) intact.
//
// Note: a similar exemption was added in bootstrap/app.php via
// `Env::get('APP_ENV') === 'testing'`, but that runs at boot time and
// the env var may not yet be set by phpunit.xml — so this Pest-level
// disablement is the reliable path.
beforeEach(function () {
    $this->withoutMiddleware(ValidateCsrfToken::class);
})->in('Feature');
