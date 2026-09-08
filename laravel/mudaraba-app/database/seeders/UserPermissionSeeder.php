<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Database\Seeder;

class UserPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $superadmin = User::where('role', 'superadmin')->first();
        if (! $superadmin) {
            return;
        }

        $menus = Menu::all();
        foreach ($menus as $menu) {
            UserPermission::firstOrCreate(
                ['user_id' => $superadmin->id, 'menu_id' => $menu->id],
                [
                    'can_view' => true,
                    'can_backdate' => true,
                    'can_edit' => true,
                    'can_delete' => true,
                ],
            );
        }
    }
}
