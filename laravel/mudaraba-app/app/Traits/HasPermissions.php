<?php

namespace App\Traits;

use App\Models\Menu;
use App\Models\UserPermission;
use Illuminate\Support\Collection;

/**
 * Permission helpers for the User model.
 *
 * Provides:
 *   - permissionFor(menu): UserPermission|null — single lookup
 *   - canView(route): bool — can the user see this menu?
 *   - canEdit(route): bool
 *   - canDelete(route): bool
 *   - canBackdate(route): bool
 *   - permittedMenus(): Collection<Menu> — menus the user can view (for sidebar)
 *   - permittedMenuTree(): array — hierarchical tree for sidebar rendering
 *
 * Superadmins automatically bypass all permission checks.
 */
trait HasPermissions
{
    /**
     * Get the UserPermission row for a given menu (by route name).
     */
    public function permissionFor(string|Menu $menu): ?UserPermission
    {
        if ($this->isSuperadmin()) {
            // Return a synthetic full-permission row
            return new UserPermission([
                'can_view' => true,
                'can_backdate' => true,
                'can_edit' => true,
                'can_delete' => true,
            ]);
        }

        $menuId = $menu instanceof Menu ? $menu->id : $this->menuIdByRoute($menu);

        if (! $menuId) {
            return null;
        }

        return $this->permissions()->where('menu_id', $menuId)->first();
    }

    public function canView(string|Menu $menu): bool
    {
        if ($this->isSuperadmin()) {
            return true;
        }

        return (bool) $this->permissionFor($menu)?->can_view;
    }

    public function canEdit(string|Menu $menu): bool
    {
        if ($this->isSuperadmin()) {
            return true;
        }

        return (bool) $this->permissionFor($menu)?->can_edit;
    }

    public function canDelete(string|Menu $menu): bool
    {
        if ($this->isSuperadmin()) {
            return true;
        }

        return (bool) $this->permissionFor($menu)?->can_delete;
    }

    public function canBackdate(string|Menu $menu): bool
    {
        if ($this->isSuperadmin()) {
            return true;
        }

        return (bool) $this->permissionFor($menu)?->can_backdate;
    }

    /**
     * All menus the user is allowed to view (used for sidebar rendering).
     * Superadmins get all menus; regular users get only menus with can_view=true.
     *
     * @return Collection<int, Menu>
     */
    public function permittedMenus(): Collection
    {
        if ($this->isSuperadmin()) {
            return Menu::orderBy('sort_order')->get();
        }

        $permittedMenuIds = $this->permissions()
            ->where('can_view', true)
            ->pluck('menu_id');

        // Also include parent menus that have at least one permitted child
        $parentIds = Menu::whereIn('id', $permittedMenuIds)
            ->whereNotNull('parent_id')
            ->pluck('parent_id')
            ->unique();

        $allPermittedIds = $permittedMenuIds->merge($parentIds)->unique();

        return Menu::whereIn('id', $allPermittedIds)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Build a hierarchical tree of permitted menus for sidebar rendering.
     *
     * @return array<int, array{
     *   id: int,
     *   name: string,
     *   route: string|null,
     *   icon: string|null,
     *   sort_order: int,
     *   is_parent: bool,
     *   children: array<int, array>
     * }>
     */
    public function permittedMenuTree(): array
    {
        $menus = $this->permittedMenus();

        $groups = $menus->whereNull('parent_id')->values();

        return $groups->map(function (Menu $group) use ($menus) {
            $children = $menus->where('parent_id', $group->id)
                ->sortBy('sort_order')
                ->values()
                ->map(fn (Menu $m) => $this->menuToArray($m))
                ->toArray();

            return array_merge($this->menuToArray($group), ['children' => $children]);
        })->toArray();
    }

    private function menuToArray(Menu $menu): array
    {
        return [
            'id' => $menu->id,
            'name' => $menu->name,
            'route' => $menu->route,
            'icon' => $menu->icon,
            'sort_order' => $menu->sort_order,
            'is_parent' => (bool) $menu->is_parent,
        ];
    }

    private function menuIdByRoute(string $route): ?int
    {
        $menu = Menu::where('route', $route)->first();

        return $menu?->id;
    }
}
