<?php

/**
 * CsrfDiagnosticTest — confirms CSRF middleware is bypassed during tests.
 *
 * If this test PASSES, the CSRF exemption is working for real app routes.
 * If this test FAILS with HTTP 419, the exemption is NOT working.
 *
 * Run: php artisan test --filter=CsrfDiagnosticTest
 *
 * === Why we use a custom MUDARABA_TESTING env var (not APP_ENV) ===
 *
 * APP_ENV gets overwritten by Dotenv loading .env (which has APP_ENV=local).
 * This was confirmed by the user's diagnostic run on 2026-09-08:
 *   getenv('APP_ENV') returned 'local' during tests, NOT 'testing'.
 * That's why all previous APP_ENV-based CSRF bypass attempts failed.
 *
 * MUDARABA_TESTING is a custom env var that exists ONLY in phpunit.xml
 * (set via <env name="MUDARABA_TESTING" value="1"/>), NOT in .env.
 * Dotenv can't overwrite what doesn't exist in .env, so
 * getenv('MUDARABA_TESTING') reliably returns '1' during tests.
 *
 * The bootstrap/app.php check is:
 *   if (getenv('MUDARABA_TESTING') === '1') {
 *       $middleware->validateCsrfTokens(except: ['*']);
 *   }
 */

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

it('the MUDARABA_TESTING env var is set to 1 during tests', function () {
    // Direct env-var check — confirms PHPUnit's <env> tag is being applied
    // AND that Dotenv isn't overwriting it (because it's not in .env).
    $value = getenv('MUDARABA_TESTING');
    expect($value)->toBe('1', "Expected MUDARABA_TESTING='1' but got: " . var_export($value, true) . ". Check phpunit.xml has <env name='MUDARABA_TESTING' value='1'/> and that no .env file sets MUDARABA_TESTING.");
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



