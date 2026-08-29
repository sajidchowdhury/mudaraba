import * as React from "react";
import { Link } from "@inertiajs/react";
import { Menu, Search, Bell, Plus } from "lucide-react";
import { Button } from "@/Components/ui/Button";
import { Avatar, AvatarFallback } from "@/Components/ui/Avatar";
import { Badge } from "@/Components/ui/Badge";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/Components/ui/DropdownMenu";
import { ThemeToggle } from "@/Components/ThemeToggle";
import { MonthSwitcher } from "@/Components/layout/MonthSwitcher";
import { cn } from "@/lib/utils";

interface TopBarProps {
    onMenuClick: () => void;
    month: string;
    onMonthChange: (m: string) => void;
    className?: string;
}

export function TopBar({ onMenuClick, month, onMonthChange, className }: TopBarProps) {
    const [searchOpen, setSearchOpen] = React.useState(false);

    // Cmd+K opens command palette (handled by parent via custom event)
    React.useEffect(() => {
        const handler = (e: KeyboardEvent) => {
            if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === "k") {
                e.preventDefault();
                window.dispatchEvent(new CustomEvent("open-command-palette"));
                setSearchOpen(false);
            }
            if (e.key === "/" && !isTyping(e.target)) {
                e.preventDefault();
                window.dispatchEvent(new CustomEvent("open-command-palette"));
            }
        };
        window.addEventListener("keydown", handler);
        return () => window.removeEventListener("keydown", handler);
    }, []);

    return (
        <header
            className={cn(
                "sticky top-0 z-30 h-16 border-b border-border bg-surface/80 backdrop-blur-md",
                "flex items-center gap-2 px-4 sm:px-6",
                className,
            )}
        >
            {/* Mobile hamburger */}
            <Button
                variant="ghost"
                size="icon"
                className="lg:hidden shrink-0"
                onClick={onMenuClick}
                aria-label="Open menu"
            >
                <Menu className="size-5" />
            </Button>

            {/* Month switcher — prominent */}
            <MonthSwitcher value={month} onChange={onMonthChange} className="shrink-0" />

            {/* Spacer */}
            <div className="flex-1" />

            {/* Search trigger (desktop) */}
            <button
                onClick={() => window.dispatchEvent(new CustomEvent("open-command-palette"))}
                className={cn(
                    "hidden md:flex items-center gap-2 h-10 px-3 rounded-md border border-border bg-background",
                    "text-sm text-muted hover:bg-surface-2 transition-colors min-w-[240px]",
                )}
            >
                <Search className="size-4" />
                <span className="flex-1 text-left">Search or jump to…</span>
                <kbd className="font-num text-[10px] px-1.5 py-0.5 rounded border border-border bg-surface-2 text-muted">
                    ⌘K
                </kbd>
            </button>

            {/* Mobile search icon */}
            <Button
                variant="ghost"
                size="icon"
                className="md:hidden shrink-0"
                onClick={() => window.dispatchEvent(new CustomEvent("open-command-palette"))}
                aria-label="Search"
            >
                <Search className="size-5" />
            </Button>

            {/* Quick add */}
            <Button size="icon" className="shrink-0" aria-label="Quick add">
                <Plus className="size-5" />
            </Button>

            {/* Notifications */}
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button variant="ghost" size="icon" className="shrink-0 relative" aria-label="Notifications">
                        <Bell className="size-5" />
                        <span className="absolute top-1.5 right-1.5 size-2 rounded-full bg-danger ring-2 ring-surface" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-80">
                    <DropdownMenuLabel>Notifications</DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem className="flex flex-col items-start gap-1 py-2">
                        <div className="flex w-full items-center justify-between">
                            <span className="text-sm font-medium">Variance detected</span>
                            <span className="text-[10px] text-muted">2m ago</span>
                        </div>
                        <span className="text-xs text-muted">Bike X primary exceeds actual by ৳30,000</span>
                    </DropdownMenuItem>
                    <DropdownMenuItem className="flex flex-col items-start gap-1 py-2">
                        <div className="flex w-full items-center justify-between">
                            <span className="text-sm font-medium">New investor added</span>
                            <span className="text-[10px] text-muted">1h ago</span>
                        </div>
                        <span className="text-xs text-muted">Siddik U — BDT 6,000,000</span>
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem className="justify-center text-sm text-primary">View all</DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>

            <ThemeToggle />

            {/* User menu */}
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <button className="flex items-center gap-2 rounded-full hover:opacity-80 transition-opacity ml-1">
                        <Avatar className="size-9 ring-2 ring-border">
                            <AvatarFallback className="bg-primary-soft text-primary text-xs font-semibold">
                                MY
                            </AvatarFallback>
                        </Avatar>
                    </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-56">
                    <DropdownMenuLabel>
                        <div className="flex flex-col gap-0.5">
                            <span className="text-sm font-medium text-foreground">M / Y Owner</span>
                            <span className="text-xs text-muted font-normal">Sajid · superadmin</span>
                        </div>
                    </DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem>Profile</DropdownMenuItem>
                    <DropdownMenuItem>Settings</DropdownMenuItem>
                    <DropdownMenuItem>
                        Two-factor auth
                        <Badge variant="success" className="ml-auto text-[10px]">ON</Badge>
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem className="text-danger focus:text-danger">Sign out</DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </header>
    );
}

function isTyping(target: EventTarget | null): boolean {
    if (!(target instanceof HTMLElement)) return false;
    return ["INPUT", "TEXTAREA", "SELECT"].includes(target.tagName) || target.isContentEditable;
}
