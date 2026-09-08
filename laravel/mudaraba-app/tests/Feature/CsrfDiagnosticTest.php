<?php

/**
 * CsrfDiagnosticTest — a diagnostic test that confirms the CSRF middleware
 * is actually being bypassed during the test run.
 *
 * If this test PASSES, the CSRF exemption is working.
 * If this test FAILS with HTTP 419, the exemption is NOT working.
 *
 * Run: php artisan test --filter=CsrfDiagnosticTest
 *
 * If you're seeing 65 CSRF failures and this test also fails with 419,
 * the most likely cause is that your local code is out of date —
 * run `git pull origin main` and `php artisan optimize:clear` before
 * re-running the tests.
 */

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    // Register a throwaway POST route that just returns 200 OK
    // (no controller logic — we're only testing whether the middleware
    // blocks the request with 419)
    Route::post('/__csrf_diagnostic_test', fn () => response('OK', 200));
});

it('POST requests to test routes do not get blocked by CSRF middleware', function () {
    $response = $this->post('/__csrf_diagnostic_test', ['any' => 'data']);

    // If CSRF is enabled, this returns 419 (TokenMismatchException).
    // If CSRF is bypassed (which is what we want for tests), this returns 200.
    expect($response->status())->toBe(200, "Expected 200 OK but got {$response->status()}. CSRF middleware is NOT being bypassed — see tests/TestCase.php setUp().");
});

it('the ValidateCsrfToken class exists (sanity check for the Laravel 11+ class name)', function () {
    // Sanity check: confirm the class name we're disabling in
    // tests/TestCase.php setUp() actually exists in this Laravel version.
    // In Laravel 11+, the canonical CSRF middleware is
    // Illuminate\Foundation\Http\Middleware\ValidateCsrfToken
    // (in older Laravel it was VerifyCsrfToken).
    //
    // If this fails, the class name changed in a future Laravel version —
    // update tests/TestCase.php to use the new class name.
    expect(class_exists(ValidateCsrfToken::class))->toBeTrue(
        'Class Illuminate\\Foundation\\Http\\Middleware\\ValidateCsrfToken not found. '.
        'In Laravel 11+, this is the canonical CSRF middleware class. '.
        'If the class name is different in your Laravel version, update tests/TestCase.php.'
    );
});
