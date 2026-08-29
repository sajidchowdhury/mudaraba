import * as React from "react";
import { Head } from "@inertiajs/react";
import { Sidebar } from "@/Components/layout/Sidebar";
import { TopBar } from "@/Components/layout/TopBar";
import { Breadcrumb } from "@/Components/layout/Breadcrumb";
import { MobileTabBar } from "@/Components/layout/MobileTabBar";
import { Footer } from "@/Components/layout/Footer";
import { CommandPalette } from "@/Components/command-palette";
import { cn } from "@/lib/utils";

interface AuthenticatedLayoutProps {
    title: string;
    children: React.ReactNode;
    /** Optional right-aligned actions next to the breadcrumb */
    actions?: React.ReactNode;
    /** Optional className on the content area */
    contentClassName?: string;
    /** Hide the breadcrumb row (default: false) */
    hideBreadcrumb?: boolean;
}

export function AuthenticatedLayout({
    title,
    children,
    actions,
    contentClassName,
    hideBreadcrumb = false,
}: AuthenticatedLayoutProps) {
    const [sidebarOpen, setSidebarOpen] = React.useState(false);
    const [paletteOpen, setPaletteOpen] = React.useState(false);

    // Default to current month
    const now = new Date();
    const currentMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, "0")}`;
    const [month, setMonth] = React.useState(currentMonth);

    return (
        <>
            <Head title={title} />

            <div className="min-h-screen flex flex-col bg-background">
                <div className="flex flex-1 min-h-0">
                    {/* Sidebar (desktop static, mobile slide-over) */}
                    <Sidebar open={sidebarOpen} onOpenChange={setSidebarOpen} />

                    {/* Mobile backdrop when sidebar open */}
                    {sidebarOpen && (
                        <div
                            className="fixed inset-0 z-40 bg-black/60 lg:hidden"
                            onClick={() => setSidebarOpen(false)}
                            aria-hidden
                        />
                    )}

                    {/* Main column */}
                    <div className="flex-1 flex flex-col min-w-0">
                        <TopBar
                            onMenuClick={() => setSidebarOpen(true)}
                            month={month}
                            onMonthChange={setMonth}
                        />

                        {/* Breadcrumb row */}
                        {!hideBreadcrumb && (
                            <div className="flex items-center justify-between gap-4 px-4 sm:px-6 py-3 border-b border-border bg-surface/50">
                                <Breadcrumb />
                                {actions && <div className="flex items-center gap-2">{actions}</div>}
                            </div>
                        )}

                        {/* Page content */}
                        <main className={cn("flex-1 px-4 sm:px-6 py-6 pb-24 lg:pb-6", contentClassName)}>
                            {children}
                        </main>

                        <Footer />
                    </div>
                </div>

                {/* Mobile bottom tab bar */}
                <MobileTabBar />
            </div>

            <CommandPalette open={paletteOpen} onOpenChange={setPaletteOpen} />
        </>
    );
}
