import * as React from "react";
import { Link, usePage } from "@inertiajs/react";
import { ChevronRight, Home } from "lucide-react";
import { cn } from "@/lib/utils";
import { navigation } from "@/config/navigation";

export interface BreadcrumbItem {
    label: string;
    href?: string;
}

/**
 * Resolves the breadcrumb trail from the current URL using the navigation tree.
 */
function resolveBreadcrumbs(url: string): BreadcrumbItem[] {
    const items: BreadcrumbItem[] = [{ label: "Home", href: "/" }];
    if (url === "/" || url === "/dashboard") {
        items.push({ label: "Dashboard" });
        return items;
    }
    for (const group of navigation) {
        for (const child of group.children) {
            if (url === child.href || url.startsWith(child.href + "/")) {
                items.push({ label: group.label });
                items.push({ label: child.label, href: child.href });
                // trailing segment (e.g. edit/new) — last piece
                const extra = url.slice(child.href.length).replace(/^\/+/, "").split("/")[0];
                if (extra && extra !== child.href) {
                    items.push({ label: capitalize(extra) });
                }
                return items;
            }
        }
    }
    // Unknown URL — show raw segments
    const segs = url.split("/").filter(Boolean);
    if (segs.length) {
        items.push({ label: capitalize(segs[segs.length - 1]) });
    }
    return items;
}

function capitalize(s: string): string {
    return s.charAt(0).toUpperCase() + s.slice(1);
}

export function Breadcrumb({ className }: { className?: string }) {
    const { url } = usePage();
    const items = resolveBreadcrumbs(url || "/");
    if (items.length <= 1) return null;

    return (
        <nav aria-label="Breadcrumb" className={cn("flex items-center text-sm", className)}>
            <ol className="flex items-center gap-1 flex-wrap">
                {items.map((item, idx) => {
                    const isLast = idx === items.length - 1;
                    return (
                        <li key={idx} className="flex items-center gap-1">
                            {item.href && !isLast ? (
                                <Link
                                    href={item.href}
                                    className="text-muted hover:text-foreground transition-colors inline-flex items-center gap-1"
                                >
                                    {idx === 0 && <Home className="size-3.5" />}
                                    {item.label}
                                </Link>
                            ) : (
                                <span
                                    className={cn(
                                        "inline-flex items-center gap-1",
                                        isLast ? "text-foreground font-medium" : "text-muted",
                                    )}
                                >
                                    {idx === 0 && <Home className="size-3.5" />}
                                    {item.label}
                                </span>
                            )}
                            {!isLast && <ChevronRight className="size-3.5 text-muted-foreground/50" />}
                        </li>
                    );
                })}
            </ol>
        </nav>
    );
}
