import { Heart } from "lucide-react";

export function Footer() {
    const year = new Date().getFullYear();
    return (
        <footer
            className="mt-auto border-t border-border bg-surface py-4 px-6"
            role="contentinfo"
        >
            <div className="mx-auto max-w-7xl flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-muted">
                <p>© {year} Mudaraba Profit Management · v0.2.0 · Phase 0</p>
                <p className="flex items-center gap-1.5">
                    Built with Laravel + Inertia + React
                    <Heart className="size-3 fill-primary text-primary" />
                    Last sync 2m ago
                </p>
            </div>
        </footer>
    );
}
