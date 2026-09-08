<?php

/**
 * CsrfDiagnosticTest — confirms CSRF middleware is bypassed during tests.
 *
 * If this test PASSES, the CSRF exemption is working for real app routes
 * (not just dynamically-registered test routes).
 * If this test FAILS with HTTP 419, the exemption is NOT working.
 *
 * Run: php artisan test --filter=CsrfDiagnosticTest
 */

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

it('the MUDARABA_TESTING env var is set to 1 during tests', function () {
    // Direct env-var check — if this fails, PHPUnit's <env> tag isn't
    // being applied, or Dotenv is overwriting it.
    $value = getenv('MUDARABA_TESTING');
    expect($value)->toBe('1', "Expected MUDARABA_TESTING='1' but got: " . var_export($value, true) . ". Check phpunit.xml has <env name='MUDARABA_TESTING' value='1'/> and that no .env file sets MUDARABA_TESTING.");
});

it('APP_ENV is set to testing during tests (cross-check)', function () {
    // Cross-check: APP_ENV should be 'testing' during the test run.
    // If this returns 'local', Dotenv overwrote it — which explains why
    // the previous getenv('APP_ENV') approach didn't work.
    $value = getenv('APP_ENV');
    expect($value)->toBe('testing', "Expected APP_ENV='testing' but got: " . var_export($value, true));
});

it('POST to a REAL app route (/login) does not get blocked by CSRF', function () {
    // We POST to /login (defined in routes/web.php, which uses the `web`
    // middleware group that includes ValidateCsrfToken). If CSRF is
    // properly disabled, we should get a redirect (302 to /login with
    // errors — invalid credentials) or a validation error — NOT 419.
    $response = $this->post('/login', [
        'username' => 'nonexistent_user',
        'password' => 'wrong_password',
        'remember' => false,
    ]);

    // 419 = CSRF blocked the request (bad — exemption not working)
    // 302 = validation failed, redirected back with errors (good — CSRF bypassed)
    expect($response->status())->not->toBe(419, "POST /login returned 419 — CSRF middleware is NOT being bypassed for real app routes. See bootstrap/app.php → validateCsrfTokens(except: ['*']).");
});

it('the ValidateCsrfToken class exists (sanity check for the Laravel 11+ class name)', function () {
    expect(class_exists(ValidateCsrfToken::class))->toBeTrue(
        'Class Illuminate\\Foundation\\Http\\Middleware\\ValidateCsrfToken not found. '.
        'In Laravel 11+, this is the canonical CSRF middleware class.'
    );
});


