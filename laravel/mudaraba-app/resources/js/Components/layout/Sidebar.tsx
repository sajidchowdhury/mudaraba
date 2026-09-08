import * as React from "react";
import { Link, usePage } from "@inertiajs/react";
import { route } from "ziggy-js";
import { ChevronRight, Layers } from "lucide-react";
import * as LucideIcons from "lucide-react";
import { cn } from "@/lib/utils";
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from "@/Components/ui/Collapsible";

interface SidebarMenu {
    id: number;
    name: string;
    route: string | null;
    icon: string;
    sort_order: number;
    is_parent: boolean;
    children?: SidebarMenu[];
}

interface SidebarProps {
    open?: boolean;
    onOpenChange?: (open: boolean) => void;
}

function useIcon(name: string) {
    return React.useMemo(() => {
        const icons = LucideIcons as unknown as Record<string, React.ComponentType<{ className?: string }>>;
        return icons[name] ?? Layers;
    }, [name]);
}

export function Sidebar({ open, onOpenChange }: SidebarProps) {
    const { menus, url } = usePage().props as unknown as { menus?: SidebarMenu[]; url: string };

    const initialExpanded = React.useMemo(() => {
        if (!menus) return [];
        return menus
            .filter((g) => g.children?.some((c) => isActive(url, c.route)))
            .map((g) => g.name);
    }, [menus, url]);

    const [expanded, setExpanded] = React.useState<string[]>(initialExpanded);

    const toggle = (label: string) =>
        setExpanded((prev) =>
            prev.includes(label) ? prev.filter((l) => l !== label) : [...prev, label],
        );

    return (
        <aside
            className={cn(
                "flex flex-col bg-surface border-r border-border h-full w-64 shrink-0",
                "fixed inset-y-0 left-0 z-50 transition-transform duration-300 lg:static lg:translate-x-0",
                open ? "translate-x-0" : "-translate-x-full",
            )}
        >
            <div className="flex items-center gap-3 h-16 px-6 border-b border-border shrink-0">
                <div className="size-9 rounded-xl bg-gradient-to-br from-primary to-emerald-600 flex items-center justify-center shadow-[var(--shadow-lifted)]">
                    <Layers className="size-5 text-white" />
                </div>
                <div className="min-w-0">
                    <p className="font-display font-semibold leading-none truncate">Mudaraba</p>
                    <p className="text-[11px] text-muted mt-0.5">Profit Management</p>
                </div>
            </div>

            <nav className="flex-1 overflow-y-auto px-3 py-4 space-y-1">
                {menus?.map((group) => (
                    <NavGroupItem
                        key={group.id}
                        group={group}
                        url={url}
                        expanded={expanded.includes(group.name)}
                        onToggle={() => toggle(group.name)}
                        onNavigate={() => onOpenChange?.(false)}
                    />
                ))}
            </nav>

            <div className="border-t border-border p-4 space-y-2">
                <div className="flex items-center justify-between text-xs">
                    <span className="text-muted">Phase 8</span>
                    <span className="font-num font-medium">95%</span>
                </div>
                <div className="h-1.5 rounded-full bg-surface-2 overflow-hidden">
                    <div className="h-full bg-primary transition-all duration-500" style={{ width: "95%" }} />
                </div>
                <p className="text-[10px] text-muted">Production Ready</p>
            </div>
        </aside>
    );
}

function NavGroupItem({
    group,
    url,
    expanded,
    onToggle,
    onNavigate,
}: {
    group: SidebarMenu;
    url: string;
    expanded: boolean;
    onToggle: () => void;
    onNavigate: () => void;
}) {
    const Icon = useIcon(group.icon);
    const hasActiveChild = group.children?.some((c) => isActive(url, c.route)) ?? false;

    if (!group.children || group.children.length === 0) {
        const active = isActive(url, group.route);
        const href = safeRoute(group.route);
        return (
            <Link
                href={href}
                onClick={onNavigate}
                className={cn(
                    "w-full flex items-center gap-3 rounded-md px-3 py-2 text-sm transition-colors",
                    "hover:bg-surface-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary",
                    active ? "bg-primary-soft text-primary font-medium" : "text-foreground",
                )}
            >
                <Icon className="size-4 shrink-0" />
                <span className="flex-1 text-left">{group.name}</span>
            </Link>
        );
    }

    return (
        <Collapsible open={expanded} onOpenChange={onToggle}>
            <CollapsibleTrigger
                className={cn(
                    "w-full flex items-center gap-3 rounded-md px-3 py-2 text-sm transition-colors",
                    "hover:bg-surface-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary",
                    hasActiveChild && "text-primary font-medium",
                )}
            >
                <Icon className="size-4 shrink-0" />
                <span className="flex-1 text-left">{group.name}</span>
                <ChevronRight
                    className={cn(
                        "size-4 shrink-0 text-muted transition-transform duration-200",
                        expanded && "rotate-90",
                    )}
                />
            </CollapsibleTrigger>
            <CollapsibleContent className="overflow-hidden data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0">
                <ul className="ml-4 pl-3 border-l border-border my-1 space-y-0.5">
                    {group.children.map((child) => {
                        const ChildIcon = useIcon(child.icon);
                        const active = isActive(url, child.route);
                        const href = safeRoute(child.route);
                        return (
                            <li key={child.id}>
                                <Link
                                    href={href}
                                    onClick={onNavigate}
                                    className={cn(
                                        "flex items-center gap-2.5 rounded-md px-3 py-1.5 text-sm transition-colors",
                                        "hover:bg-surface-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary",
                                        active
                                            ? "bg-primary-soft text-primary font-medium"
                                            : "text-muted hover:text-foreground",
                                    )}
                                >
                                    <ChildIcon className="size-3.5 shrink-0" />
                                    <span className="truncate">{child.name}</span>
                                </Link>
                            </li>
                        );
                    })}
                </ul>
            </CollapsibleContent>
        </Collapsible>
    );
}

/**
 * Safely resolve a Ziggy route — returns "#" if the route doesn't exist
 * (instead of throwing an error that crashes the entire app).
 */
function safeRoute(routeName: string | null): string {
    if (!routeName) return "#";
    try {
        return route(routeName);
    } catch {
        return "#";
    }
}

/**
 * Check if the current URL matches a route — returns false if the route
 * doesn't exist (instead of throwing).
 */
function isActive(currentUrl: string, routeName: string | null): boolean {
    if (!routeName) return false;
    if (routeName === "dashboard") return currentUrl === "/" || currentUrl === "/dashboard";
    try {
        const path = route(routeName);
        return currentUrl === path || currentUrl.startsWith(path + "/");
    } catch {
        return false;
    }
}
