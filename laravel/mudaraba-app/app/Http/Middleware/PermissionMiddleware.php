<?php

namespace App\Http\Middleware;

use App\Models\Menu;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Enforce route-level permission for the authenticated user.
     *
     * Usage in routes:
     *   Route::get('/investors', [InvestorController::class, 'index'])
     *       ->middleware('permission:investors.index');
     *
     * The argument is the menu's 'route' name. If the user doesn't have
     * can_view on that menu, they get a 403.
     *
     * Superadmins bypass all checks.
     */
    public function handle(Request $request, Closure $next, string $menuRoute): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if ($user->isSuperadmin()) {
            return $next($request);
        }

        // Find the menu by route name
        $menu = Menu::where('route', $menuRoute)->first();

        if (! $menu) {
            // Unknown menu route — allow by default (don't block features that
            // don't have a menu entry yet)
            return $next($request);
        }

        if (! $user->canView($menu)) {
            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}
