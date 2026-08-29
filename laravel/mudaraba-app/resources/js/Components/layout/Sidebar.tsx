import * as React from "react";
import { Link, usePage } from "@inertiajs/react";
import { ChevronRight, Layers } from "lucide-react";
import { navigation, type NavGroup } from "@/config/navigation";
import { cn } from "@/lib/utils";
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from "@/Components/ui/Collapsible";

interface SidebarProps {
    /** Mobile: controlled open state */
    open?: boolean;
    onOpenChange?: (open: boolean) => void;
}

export function Sidebar({ open, onOpenChange }: SidebarProps) {
    const { url } = usePage();
    // Auto-expand any group whose child is currently active
    const initialExpanded = navigation
        .filter((g) => g.children.some((c) => isActive(url, c.href)))
        .map((g) => g.label);
    const [expanded, setExpanded] = React.useState<string[]>(initialExpanded);

    const toggle = (label: string) =>
        setExpanded((prev) =>
            prev.includes(label) ? prev.filter((l) => l !== label) : [...prev, label],
        );

    return (
        <aside
            className={cn(
                "flex flex-col bg-surface border-r border-border h-full w-64 shrink-0",
                // Mobile slide-over behavior
                "fixed inset-y-0 left-0 z-50 transition-transform duration-300 lg:static lg:translate-x-0",
                open ? "translate-x-0" : "-translate-x-full",
            )}
        >
            {/* Brand */}
            <div className="flex items-center gap-3 h-16 px-6 border-b border-border shrink-0">
                <div className="size-9 rounded-xl bg-gradient-to-br from-primary to-emerald-600 flex items-center justify-center shadow-[var(--shadow-lifted)]">
                    <Layers className="size-5 text-white" />
                </div>
                <div className="min-w-0">
                    <p className="font-display font-semibold leading-none truncate">Mudaraba</p>
                    <p className="text-[11px] text-muted mt-0.5">Profit Management</p>
                </div>
            </div>

            {/* Nav */}
            <nav className="flex-1 overflow-y-auto px-3 py-4 space-y-1">
                {navigation
                    .sort((a, b) => a.order - b.order)
                    .map((group) => (
                        <NavGroupItem
                            key={group.label}
                            group={group}
                            url={url}
                            expanded={expanded.includes(group.label)}
                            onToggle={() => toggle(group.label)}
                            onNavigate={() => onOpenChange?.(false)}
                        />
                    ))}
            </nav>

            {/* Footer mini-stats */}
            <div className="border-t border-border p-4 space-y-2">
                <div className="flex items-center justify-between text-xs">
                    <span className="text-muted">Phase 0</span>
                    <span className="font-num font-medium">30%</span>
                </div>
                <div className="h-1.5 rounded-full bg-surface-2 overflow-hidden">
                    <div className="h-full bg-primary transition-all duration-500" style={{ width: "30%" }} />
                </div>
                <p className="text-[10px] text-muted">Foundation & Design</p>
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
    group: NavGroup;
    url: string;
    expanded: boolean;
    onToggle: () => void;
    onNavigate: () => void;
}) {
    const Icon = group.icon;
    const hasActiveChild = group.children.some((c) => isActive(url, c.href));

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
                <span className="flex-1 text-left">{group.label}</span>
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
                        const ChildIcon = child.icon;
                        const active = isActive(url, child.href);
                        return (
                            <li key={child.href}>
                                <Link
                                    href={child.href}
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
                                    <span className="truncate">{child.label}</span>
                                </Link>
                            </li>
                        );
                    })}
                </ul>
            </CollapsibleContent>
        </Collapsible>
    );
}

function isActive(currentUrl: string, href: string): boolean {
    if (href === "/dashboard") return currentUrl === "/" || currentUrl === "/dashboard";
    return currentUrl === href || currentUrl.startsWith(href + "/");
}
