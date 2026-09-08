import * as React from "react";
import { Command as CommandPrimitive } from "cmdk";
import { Link } from "@inertiajs/react";
import { Search, CornerDownLeft, ArrowUp, ArrowDown } from "lucide-react";
import { navigation, flatNav } from "@/config/navigation";
import { cn } from "@/lib/utils";

interface CommandPaletteProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

type CommandItem = {
    id: string;
    label: string;
    group: string;
    href: string;
    keywords?: string;
};

const ALL_COMMANDS: CommandItem[] = [
    ...flatNav.map((c) => {
        const group = navigation.find((g) => g.children.includes(c))?.label ?? "Navigate";
        return {
            id: `nav-${c.href}`,
            label: c.label,
            group,
            href: c.href,
            keywords: `${c.label} ${group} navigate open`.toLowerCase(),
        };
    }),
    // Quick actions (stubs for Phase 2+)
    { id: "action-new-investor", label: "Add new investor", group: "Actions", href: "/investors/new", keywords: "add new investor create" },
    { id: "action-new-sector", label: "Add new sector", group: "Actions", href: "/sectors/new", keywords: "add new sector create" },
    { id: "action-reconcile", label: "Reconcile current month", group: "Actions", href: "/profit/investor", keywords: "reconcile finalize month profit" },
    { id: "action-export", label: "Export ledger (PDF)", group: "Actions", href: "#", keywords: "export pdf ledger download" },
    { id: "action-toggle-theme", label: "Toggle dark mode", group: "Actions", href: "#", keywords: "theme dark light toggle" },
];

export function CommandPalette({ open, onOpenChange }: CommandPaletteProps) {
    const [search, setSearch] = React.useState("");

    // Listen for global open event
    React.useEffect(() => {
        const handler = () => onOpenChange(true);
        window.addEventListener("open-command-palette", handler);
        return () => window.removeEventListener("open-command-palette", handler);
    }, [onOpenChange]);

    // ESC closes
    React.useEffect(() => {
        if (!open) return;
        const handler = (e: KeyboardEvent) => {
            if (e.key === "Escape") onOpenChange(false);
        };
        window.addEventListener("keydown", handler);
        return () => window.removeEventListener("keydown", handler);
    }, [open, onOpenChange]);

    // Reset search when opening
    React.useEffect(() => {
        if (open) setSearch("");
    }, [open]);

    if (!open) return null;

    const filtered = React.useMemo(() => {
        if (!search.trim()) return ALL_COMMANDS;
        const q = search.toLowerCase();
        return ALL_COMMANDS.filter((c) =>
            c.label.toLowerCase().includes(q) ||
            c.keywords?.includes(q) ||
            c.group.toLowerCase().includes(q),
        );
    }, [search]);

    // Group filtered results
    const grouped = filtered.reduce<Record<string, CommandItem[]>>((acc, item) => {
        (acc[item.group] = acc[item.group] || []).push(item);
        return acc;
    }, {});

    return (
        <div className="fixed inset-0 z-[100] flex items-start justify-center p-4 pt-[10vh] sm:pt-[15vh]">
            {/* Backdrop */}
            <div
                className="absolute inset-0 bg-black/60 backdrop-blur-sm animate-in fade-in-0"
                onClick={() => onOpenChange(false)}
            />
            {/* Command panel */}
            <div className="relative w-full max-w-xl sm:max-w-xl rounded-xl border border-border bg-surface shadow-[var(--shadow-lifted)] overflow-hidden animate-in fade-in-0 zoom-in-95">
                <CommandPrimitive
                    className="flex flex-col"
                    loop
                    shouldFilter={false}
                >
                    <div className="flex items-center gap-3 border-b border-border px-4">
                        <Search className="size-4 text-muted shrink-0" />
                        <CommandPrimitive.Input
                            autoFocus
                            value={search}
                            onValueChange={setSearch}
                            placeholder="Search modules, investors, sectors…"
                            className="h-14 flex-1 bg-transparent text-sm outline-none placeholder:text-muted-foreground/60"
                        />
                        <kbd className="font-num text-[10px] px-1.5 py-0.5 rounded border border-border bg-surface-2 text-muted">
                            ESC
                        </kbd>
                    </div>

                    <CommandPrimitive.List className="max-h-[400px] overflow-y-auto p-2">
                        {Object.keys(grouped).length === 0 && (
                            <div className="py-12 text-center text-sm text-muted">
                                No results for “{search}”
                            </div>
                        )}
                        {Object.entries(grouped).map(([group, items]) => (
                            <CommandPrimitive.Group
                                key={group}
                                heading={group}
                                className="text-muted text-[10px] font-medium uppercase tracking-wider px-2 py-1.5"
                            >
                                {items.map((item) => (
                                    <PaletteRow
                                        key={item.id}
                                        item={item}
                                        onSelect={() => {
                                            onOpenChange(false);
                                            if (item.href === "#") return; // action stub
                                            // visit via Inertia
                                            window.location.href = item.href;
                                        }}
                                    />
                                ))}
                            </CommandPrimitive.Group>
                        ))}
                    </CommandPrimitive.List>

                    <div className="flex items-center justify-between px-3 py-2 border-t border-border bg-surface-2/50 text-[10px] text-muted">
                        <div className="flex items-center gap-3">
                            <span className="flex items-center gap-1">
                                <ArrowUp className="size-3" /> <ArrowDown className="size-3" /> navigate
                            </span>
                            <span className="flex items-center gap-1">
                                <CornerDownLeft className="size-3" /> select
                            </span>
                        </div>
                        <span className="font-num">cmdk</span>
                    </div>
                </CommandPrimitive>
            </div>
        </div>
    );
}

function PaletteRow({ item, onSelect }: { item: CommandItem; onSelect: () => void }) {
    return (
        <CommandPrimitive.Item
            onSelect={onSelect}
            className={cn(
                "flex items-center gap-3 px-3 py-2 rounded-md text-sm cursor-pointer",
                "data-[selected=true]:bg-primary-soft data-[selected=true]:text-primary",
                "data-[selected=true]:font-medium",
            )}
        >
            <span className="flex-1 truncate">{item.label}</span>
            <span className="text-[10px] text-muted">{item.group}</span>
        </CommandPrimitive.Item>
    );
}
