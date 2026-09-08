import { Link, usePage } from "@inertiajs/react";
import { LayoutDashboard, Users, TrendingUp, FileBarChart, MoreHorizontal } from "lucide-react";
import { cn } from "@/lib/utils";
import {
    Sheet,
    SheetContent,
    SheetTrigger,
    SheetHeader,
    SheetTitle,
} from "@/Components/ui/Sheet";
import { navigation } from "@/config/navigation";
import * as React from "react";

interface TabItem {
    label: string;
    href: string;
    icon: typeof LayoutDashboard;
}

const TABS: TabItem[] = [
    { label: "Home", href: "/dashboard", icon: LayoutDashboard },
    { label: "Investors", href: "/investors", icon: Users },
    { label: "Profit", href: "/profit/investor", icon: TrendingUp },
    { label: "Reports", href: "/reports/investor-ledger", icon: FileBarChart },
];

export function MobileTabBar() {
    const { url } = usePage();
    const [moreOpen, setMoreOpen] = React.useState(false);

    return (
        <>
            <nav
                className={cn(
                    "lg:hidden fixed bottom-0 inset-x-0 z-30 h-16 border-t border-border bg-surface/95 backdrop-blur-md",
                    "grid grid-cols-5 pb-[env(safe-area-inset-bottom)]",
                )}
                aria-label="Mobile navigation"
            >
                {TABS.map((tab) => {
                    const Icon = tab.icon;
                    const active = isActive(url || "/", tab.href);
                    return (
                        <Link
                            key={tab.href}
                            href={tab.href}
                            className={cn(
                                "flex flex-col items-center justify-center gap-1 text-[10px] transition-colors",
                                active ? "text-primary" : "text-muted",
                            )}
                        >
                            <Icon className="size-5" />
                            <span>{tab.label}</span>
                        </Link>
                    );
                })}
                <Sheet open={moreOpen} onOpenChange={setMoreOpen}>
                    <SheetTrigger asChild>
                        <button
                            className={cn(
                                "flex flex-col items-center justify-center gap-1 text-[10px] transition-colors",
                                moreOpen ? "text-primary" : "text-muted",
                            )}
                        >
                            <MoreHorizontal className="size-5" />
                            <span>More</span>
                        </button>
                    </SheetTrigger>
                    <SheetContent side="bottom" className="h-[60vh] rounded-t-2xl">
                        <SheetHeader>
                            <SheetTitle>All Modules</SheetTitle>
                        </SheetHeader>
                        <div className="px-6 pb-6 overflow-y-auto grid grid-cols-2 gap-2">
                            {navigation
                                .flatMap((g) => g.children)
                                .map((item) => (
                                    <Link
                                        key={item.href}
                                        href={item.href}
                                        onClick={() => setMoreOpen(false)}
                                        className="flex items-center gap-2 p-3 rounded-lg border border-border hover:bg-surface-2 text-sm"
                                    >
                                        <item.icon className="size-4 text-muted" />
                                        <span className="truncate">{item.label}</span>
                                    </Link>
                                ))}
                        </div>
                    </SheetContent>
                </Sheet>
            </nav>
        </>
    );
}

function isActive(currentUrl: string, href: string): boolean {
    if (href === "/dashboard") return currentUrl === "/" || currentUrl === "/dashboard";
    return currentUrl === href || currentUrl.startsWith(href + "/");
}
