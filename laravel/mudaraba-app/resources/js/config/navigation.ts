import type { LucideIcon } from "lucide-react";
import {
    LayoutDashboard,
    Users,
    UserPlus,
    ListOrdered,
    ShoppingBag,
    PlusCircle,
    List,
    Wallet,
    Layers,
    TrendingUp,
    PieChart,
    Banknote,
    Building2,
    ArrowDownToLine,
    History,
    Settings2,
    CalendarClock,
    FileBarChart,
    ScrollText,
    BookOpen,
    ReceiptText,
    DollarSign,
} from "lucide-react";

export interface NavChild {
    label: string;
    href: string;
    icon: LucideIcon;
    /** permission key (matched server-side later in Phase 2) */
    permission?: string;
}

export interface NavGroup {
    label: string;
    icon: LucideIcon;
    /** Sort order */
    order: number;
    children: NavChild[];
}

/**
 * Navigation tree — mirrors the PHP `menus` table structure
 * (Dashboard / Investors / Sector / Investment / Profit / M/Y / Opening /
 *  Adv Profit Adjust / Reports) but cleaned up for the Laravel app.
 */
export const navigation: NavGroup[] = [
    {
        label: "Dashboard",
        icon: LayoutDashboard,
        order: 1,
        children: [
            { label: "Overview", href: "/dashboard", icon: LayoutDashboard },
        ],
    },
    {
        label: "Investors",
        icon: Users,
        order: 2,
        children: [
            { label: "New Investor", href: "/investors/new", icon: UserPlus },
            { label: "All Investors", href: "/investors", icon: ListOrdered },
        ],
    },
    {
        label: "Sectors",
        icon: ShoppingBag,
        order: 3,
        children: [
            { label: "New Sector", href: "/sectors/new", icon: PlusCircle },
            { label: "All Sectors", href: "/sectors", icon: List },
        ],
    },
    {
        label: "Investment",
        icon: Wallet,
        order: 4,
        children: [
            { label: "New / Return", href: "/investments", icon: Banknote },
            { label: "Sector Wise", href: "/investments/sector-wise", icon: Layers },
        ],
    },
    {
        label: "Profit",
        icon: TrendingUp,
        order: 5,
        children: [
            { label: "Sector Profit", href: "/profit/sector", icon: PieChart },
            { label: "Investor Profit", href: "/profit/investor", icon: ReceiptText },
        ],
    },
    {
        label: "M / Y",
        icon: Building2,
        order: 6,
        children: [
            { label: "New Director", href: "/directors/new", icon: UserPlus },
            { label: "Director List", href: "/directors", icon: List },
            { label: "Withdraw", href: "/my/withdraw", icon: ArrowDownToLine },
        ],
    },
    {
        label: "Opening",
        icon: CalendarClock,
        order: 7,
        children: [
            { label: "M / Y", href: "/opening/my", icon: Building2 },
            { label: "Investor Advance", href: "/opening/investor", icon: Users },
            { label: "Sector Advance", href: "/opening/sector", icon: ShoppingBag },
        ],
    },
    {
        label: "Adv Profit Adjust",
        icon: Settings2,
        order: 8,
        children: [
            { label: "Type A", href: "/adjustments/type-a", icon: History },
            { label: "Type B", href: "/adjustments/type-b", icon: History },
            { label: "Type C (General)", href: "/adjustments/type-c", icon: History },
        ],
    },
    {
        label: "Reports",
        icon: FileBarChart,
        order: 9,
        children: [
            { label: "Investor Ledger", href: "/reports/investor-ledger", icon: ScrollText },
            { label: "Sector Ledger", href: "/reports/sector-ledger", icon: ScrollText },
            { label: "M / Y Ledger", href: "/reports/my-ledger", icon: BookOpen },
            { label: "Investment Profit", href: "/reports/investment-profit", icon: DollarSign },
            { label: "Profit Adjustment", href: "/reports/adjustment", icon: FileBarChart },
        ],
    },
];

/** Flatten the tree for the command palette + breadcrumb matching */
export const flatNav: NavChild[] = navigation
    .flatMap((g) => g.children)
    .sort((a, b) => a.label.localeCompare(b.label));
