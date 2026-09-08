<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Bind all tests to the Laravel TestCase + auto-refresh the DB
uses(TestCase::class, RefreshDatabase::class)->in('Feature', 'Unit');

// NOTE: CSRF exemption is handled in tests/TestCase.php setUp() via
// $this->withoutMiddleware(ValidateCsrfToken::class). Doing it there
// (rather than in Pest's beforeEach) is bulletproof — setUp runs after
// Laravel is fully bootstrapped, before every test, on every test class
// that extends TestCase. No scoping issues, no env-var-timing issues.
//
// We previously tried:
//   - bootstrap/app.php with Env::get('APP_ENV') === 'testing'  (didn't take)
//   - Pest beforeEach with $this->withoutMiddleware(...)          (didn't take)
// Both left 65 tests failing with HTTP 419. The TestCase setUp approach
// is the standard Laravel pattern for global test setup.
