<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PermissionController extends Controller
{
    /**
     * Show the permission management page.
     * Admin-only — the route is guarded by middleware in web.php.
     */
    public function index(): Response
    {
        $users = User::with(['employee', 'permissions.menu'])
            ->where('role', '!=', 'superadmin')
            ->orderBy('username')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'username' => $u->username,
                'name' => $u->employee?->name ?? $u->username,
                'role' => $u->role,
                'status' => $u->status,
            ]);

        $menus = Menu::with('children')->whereNull('parent_id')->orderBy('sort_order')->get();

        $menuTree = $menus->map(fn (Menu $g) => [
            'id' => $g->id,
            'name' => $g->name,
            'icon' => $g->icon,
            'children' => $g->children->map(fn (Menu $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'route' => $c->route,
            ]),
        ]);

        // Existing permissions keyed by "userId-menuId"
        $permissions = UserPermission::all()->keyBy(fn (UserPermission $p) => "{$p->user_id}-{$p->menu_id}");

        return Inertia::render('Admin/Permissions', [
            'users' => $users,
            'menuTree' => $menuTree,
            'permissions' => $permissions->mapWithKeys(fn (UserPermission $p, string $key) => [
                $key => [
                    'can_view' => $p->can_view,
                    'can_edit' => $p->can_edit,
                    'can_delete' => $p->can_delete,
                    'can_backdate' => $p->can_backdate,
                ],
            ]),
        ]);
    }

    /**
     * Update a single permission cell (toggle).
     */
    public function update(Request $request, User $user, Menu $menu): RedirectResponse
    {
        $validated = $request->validate([
            'can_view' => ['boolean'],
            'can_edit' => ['boolean'],
            'can_delete' => ['boolean'],
            'can_backdate' => ['boolean'],
        ]);

        UserPermission::updateOrCreate(
            ['user_id' => $user->id, 'menu_id' => $menu->id],
            [
                'can_view' => $validated['can_view'] ?? false,
                'can_edit' => $validated['can_edit'] ?? false,
                'can_delete' => $validated['can_delete'] ?? false,
                'can_backdate' => $validated['can_backdate'] ?? false,
            ],
        );

        return redirect()->back()->with('success', "Permissions updated for {$user->username}.");
    }
}
