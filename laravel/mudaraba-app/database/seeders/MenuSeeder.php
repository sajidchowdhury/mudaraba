<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        // Top-level groups (is_parent=true)
        $dashboard = Menu::firstOrCreate(['name' => 'Dashboard', 'is_parent' => false], [
            'route' => 'dashboard',
            'icon' => 'LayoutDashboard',
            'sort_order' => 1,
            'parent_id' => null,
        ]);

        $investors = Menu::firstOrCreate(['name' => 'Investors', 'is_parent' => true], [
            'icon' => 'Users',
            'sort_order' => 2,
            'parent_id' => null,
        ]);

        $sectors = Menu::firstOrCreate(['name' => 'Sectors', 'is_parent' => true], [
            'icon' => 'ShoppingBag',
            'sort_order' => 3,
            'parent_id' => null,
        ]);

        $investment = Menu::firstOrCreate(['name' => 'Investment', 'is_parent' => true], [
            'icon' => 'Wallet',
            'sort_order' => 4,
            'parent_id' => null,
        ]);

        $profit = Menu::firstOrCreate(['name' => 'Profit', 'is_parent' => true], [
            'icon' => 'TrendingUp',
            'sort_order' => 5,
            'parent_id' => null,
        ]);

        $my = Menu::firstOrCreate(['name' => 'M / Y', 'is_parent' => true], [
            'icon' => 'Building2',
            'sort_order' => 6,
            'parent_id' => null,
        ]);

        $opening = Menu::firstOrCreate(['name' => 'Opening', 'is_parent' => true], [
            'icon' => 'CalendarClock',
            'sort_order' => 7,
            'parent_id' => null,
        ]);

        $adjust = Menu::firstOrCreate(['name' => 'Adv Profit Adjust', 'is_parent' => true], [
            'icon' => 'Settings2',
            'sort_order' => 8,
            'parent_id' => null,
        ]);

        $reports = Menu::firstOrCreate(['name' => 'Reports', 'is_parent' => true], [
            'icon' => 'FileBarChart',
            'sort_order' => 9,
            'parent_id' => null,
        ]);

        // Children — route names must match actual Laravel route names
        $this->createChild($investors->id, 'New Investor', 'investors.new', 'UserPlus', 1);
        $this->createChild($investors->id, 'All Investors', 'investors.index', 'ListOrdered', 2);

        $this->createChild($sectors->id, 'New Sector', 'sectors.new', 'PlusCircle', 1);
        $this->createChild($sectors->id, 'All Sectors', 'sectors.index', 'List', 2);

        $this->createChild($investment->id, 'New / Return', 'investments.index', 'Banknote', 1);

        $this->createChild($profit->id, 'Sector Profit', 'profit.sector.index', 'PieChart', 1);
        $this->createChild($profit->id, 'Investor Profit', 'profit.investor.index', 'ReceiptText', 2);

        $this->createChild($my->id, 'New Director', 'directors.new', 'UserPlus', 1);
        $this->createChild($my->id, 'Director List', 'directors.index', 'List', 2);

        $this->createChild($opening->id, 'Opening Balances', 'opening.index', 'Building2', 1);

        $this->createChild($adjust->id, 'Profit Adjustments', 'adjustments.index', 'History', 1);

        $this->createChild($reports->id, 'Investor Ledger', 'reports.investor-ledger', 'ScrollText', 1);
        $this->createChild($reports->id, 'Sector Ledger', 'reports.sector-ledger', 'ScrollText', 2);
        $this->createChild($reports->id, 'M / Y Ledger', 'reports.my-ledger', 'BookOpen', 3);
        $this->createChild($reports->id, 'Investment Profit', 'reports.investment-profit', 'DollarSign', 4);

        // Special menu: Permission management (admin-only)
        $admin = Menu::firstOrCreate(['name' => 'Admin', 'is_parent' => true], [
            'icon' => 'ShieldCheck',
            'sort_order' => 10,
            'parent_id' => null,
        ]);
        $this->createChild($admin->id, 'Permissions', 'admin.permissions', 'Lock', 1);

        // Month Close
        $monthClose = Menu::firstOrCreate(['name' => 'Month Close', 'is_parent' => false], [
            'route' => 'month-close.index',
            'icon' => 'CalendarClock',
            'sort_order' => 11,
            'parent_id' => null,
        ]);
    }

    private function createChild(int $parentId, string $name, string $route, string $icon, int $sort): void
    {
        Menu::firstOrCreate(
            ['name' => $name, 'parent_id' => $parentId],
            [
                'route' => $route,
                'icon' => $icon,
                'sort_order' => $sort,
                'is_parent' => false,
            ],
        );
    }
}
