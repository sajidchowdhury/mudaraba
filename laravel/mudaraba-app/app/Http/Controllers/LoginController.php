<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    /**
     * Maximum login attempts per decay seconds (per IP + username).
     */
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    /**
     * Display the premium login page.
     */
    public function index(): Response
    {
        return Inertia::render('Login', [
            'appName' => config('app.name'),
        ]);
    }

    /**
     * Authenticate the user.
     *
     * Flow:
     *   1. Check rate limit (5 attempts / minute per IP+username)
     *   2. Attempt auth with username + password_hash + status=Active
     *   3. Enforce login time window (login_start / login_end)
     *   4. Regenerate session, redirect to /dashboard
     *   5. On failure: increment rate limiter, redirect back with error
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $username = $request->string('username')->toString();
        $password = $request->string('password')->toString();
        $remember = $request->boolean('remember');

        $throttleKey = $this->throttleKey($request, $username);

        // 1. Rate limit check
        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'username' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        // 2. Attempt authentication
        //    Only allow users with status='Active' to log in
        if (! Auth::attempt([
            'username' => $username,
            'password' => $password,
            'status' => 'Active',
        ], $remember)) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                'username' => 'Invalid username or password.',
            ]);
        }

        // 3. Enforce login time window (if set on the user)
        $user = Auth::user();
        if ($user && $this->outsideLoginWindow($user)) {
            Auth::logout();

            throw ValidationException::withMessages([
                'username' => 'You are not allowed to log in at this time. Please try during your allowed hours.',
            ]);
        }

        // 4. Success — clear rate limiter, regenerate session
        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        // Update last_login_at
        $user->update(['last_login_at' => now()]);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Log the user out.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Build a throttle key combining IP + username.
     */
    private function throttleKey(Request $request, string $username): string
    {
        return strtolower($username).'|'.$request->ip();
    }

    /**
     * Check if the current time is outside the user's allowed login window.
     */
    private function outsideLoginWindow($user): bool
    {
        // Superadmins bypass the time window restriction
        if ($user->isSuperadmin()) {
            return false;
        }

        $now = now()->format('H:i:s');
        $start = $user->login_start;
        $end = $user->login_end;

        // If no window is set, allow login at any time
        if ($start === null || $end === null) {
            return false;
        }

        // If start == end, allow all day
        if ($start === $end) {
            return false;
        }

        // Handle overnight windows (e.g. 18:00 to 06:00)
        if ($start < $end) {
            return $now < $start || $now > $end;
        }

        // Overnight: current time must be after start OR before end
        return $now < $start && $now > $end;
    }
}
