import { Head } from "@inertiajs/react";
import { Button, Card, CardContent, CardDescription, CardHeader, CardTitle, Badge } from "@/Components/ui";
import { formatBDT, formatPercent } from "@/lib/utils";
import {
    TrendingUp,
    Wallet,
    Users,
    Layers,
    ArrowRight,
    Sparkles,
    CircleDollarSign,
} from "lucide-react";

interface HomeProps {
    appName: string;
}

export default function Home({ appName }: HomeProps) {
    const kpis = [
        {
            label: "Total Mudaraba Investment",
            value: formatBDT(157_475_000),
            change: "+4.2%",
            icon: Wallet,
            tone: "primary" as const,
            hint: "151 active investors",
        },
        {
            label: "July 2026 Actual Profit",
            value: formatBDT(1_635_000),
            change: "+8.6%",
            icon: TrendingUp,
            tone: "success" as const,
            hint: "17 sectors",
        },
        {
            label: "M/Y Profit (July)",
            value: formatBDT(476_220.07),
            change: formatPercent(29.13),
            icon: CircleDollarSign,
            tone: "accent" as const,
            hint: "of total actual profit",
        },
        {
            label: "Active Investors",
            value: "151",
            change: "+3",
            icon: Users,
            tone: "info" as const,
            hint: "across 3 tiers",
        },
    ];

    return (
        <>
            <Head title="Welcome" />

            <div className="min-h-screen bg-gradient-to-b from-surface to-background">
                {/* Hero */}
                <header className="border-b border-border bg-surface/80 backdrop-blur-sm">
                    <div className="mx-auto max-w-7xl px-6 py-4 flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className="size-9 rounded-xl bg-gradient-to-br from-primary to-emerald-600 flex items-center justify-center shadow-[var(--shadow-lifted)]">
                                <Layers className="size-5 text-white" />
                            </div>
                            <div>
                                <p className="font-display text-lg font-semibold leading-none">
                                    {appName}
                                </p>
                                <p className="text-xs text-muted">Money Management & Profit Distribution</p>
                            </div>
                        </div>
                        <Badge variant="primary" className="hidden sm:inline-flex">
                            <Sparkles className="size-3" /> Phase 0 · Foundation
                        </Badge>
                    </div>
                </header>

                <main className="mx-auto max-w-7xl px-6 py-12 md:py-20">
                    {/* Hero text */}
                    <section className="text-center max-w-3xl mx-auto mb-16">
                        <Badge variant="success" className="mb-4">
                            <span className="size-1.5 rounded-full bg-success animate-pulse" />
                            System online · July 2026 cycle active
                        </Badge>
                        <h1 className="font-display text-4xl md:text-6xl font-bold tracking-tight leading-tight">
                            The Mudaraba profit engine,{" "}
                            <span className="bg-gradient-to-r from-primary to-emerald-600 bg-clip-text text-transparent">
                                reimagined
                            </span>
                        </h1>
                        <p className="mt-6 text-lg text-muted max-w-2xl mx-auto">
                            Premium, mobile-friendly money management for Islamic-finance
                            profit-sharing pools. Eight-phase calculation engine, tier-based
                            profit sharing, retained earnings — all automated.
                        </p>
                        <div className="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
                            <Button size="lg">
                                View Dashboard <ArrowRight className="size-4" />
                            </Button>
                            <Button variant="outline" size="lg">
                                Read the Plan
                            </Button>
                        </div>
                    </section>

                    {/* KPI grid */}
                    <section className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-16">
                        {kpis.map((kpi) => {
                            const Icon = kpi.icon;
                            return (
                                <Card key={kpi.label} className="hover:shadow-[var(--shadow-lifted)] transition-shadow">
                                    <CardHeader className="pb-3">
                                        <div className="flex items-start justify-between">
                                            <div className={`size-10 rounded-lg flex items-center justify-center bg-${kpi.tone}-soft`}>
                                                <Icon className={`size-5 text-${kpi.tone}`} />
                                            </div>
                                            <Badge variant={kpi.tone}>{kpi.change}</Badge>
                                        </div>
                                    </CardHeader>
                                    <CardContent>
                                        <p className="text-sm text-muted">{kpi.label}</p>
                                        <p className="font-num text-2xl font-semibold mt-1 tracking-tight">
                                            {kpi.value}
                                        </p>
                                        <p className="text-xs text-muted mt-1">{kpi.hint}</p>
                                    </CardContent>
                                </Card>
                            );
                        })}
                    </section>

                    {/* Phase status */}
                    <section className="grid gap-6 lg:grid-cols-2">
                        <Card>
                            <CardHeader>
                                <CardTitle>Project Status</CardTitle>
                                <CardDescription>
                                    Current implementation phase of the Laravel rebuild
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {[
                                    { phase: "Phase 0 — Foundation & Design System", state: "in_progress", pct: 30 },
                                    { phase: "Phase 1 — Database Design & Migrations", state: "pending", pct: 0 },
                                    { phase: "Phase 2 — Authentication & RBAC", state: "pending", pct: 0 },
                                    { phase: "Phase 3 — Master Data Management", state: "pending", pct: 0 },
                                    { phase: "Phase 4 — The Profit Engine", state: "pending", pct: 0 },
                                    { phase: "Phase 5 — Advance Profit Adjustments", state: "pending", pct: 0 },
                                    { phase: "Phase 6 — Opening Balances", state: "pending", pct: 0 },
                                    { phase: "Phase 7 — Reports & Dashboards", state: "pending", pct: 0 },
                                    { phase: "Phase 8 — Polish & QA", state: "pending", pct: 0 },
                                ].map((item) => (
                                    <div key={item.phase} className="flex items-center justify-between text-sm">
                                        <div className="flex items-center gap-3">
                                            <span
                                                className={`size-2 rounded-full ${
                                                    item.state === "in_progress"
                                                        ? "bg-primary animate-pulse"
                                                        : item.state === "done"
                                                          ? "bg-success"
                                                          : "bg-muted-foreground/30"
                                                }`}
                                            />
                                            <span className={item.state === "pending" ? "text-muted" : ""}>
                                                {item.phase}
                                            </span>
                                        </div>
                                        <span className="font-num text-xs text-muted">{item.pct}%</span>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Design System Preview</CardTitle>
                                <CardDescription>Color tokens, typography & components</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <p className="text-xs text-muted mb-2">Color palette</p>
                                    <div className="flex flex-wrap gap-2">
                                        {[
                                            ["Emerald", "bg-primary"],
                                            ["Amber", "bg-accent"],
                                            ["Success", "bg-success"],
                                            ["Danger", "bg-danger"],
                                            ["Warning", "bg-warning"],
                                            ["Info", "bg-info"],
                                        ].map(([name, cls]) => (
                                            <div key={name} className="flex items-center gap-2">
                                                <span className={`size-5 rounded-md ${cls}`} />
                                                <span className="text-xs">{name}</span>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                                <div>
                                    <p className="text-xs text-muted mb-2">Typography</p>
                                    <p className="font-display text-2xl font-semibold">Display · Inter Tight</p>
                                    <p className="text-base">Body · Inter — clean readable text</p>
                                    <p className="font-num text-base">৳ 1,635,000.00 · numbers mono-aligned</p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted mb-2">Buttons</p>
                                    <div className="flex flex-wrap gap-2">
                                        <Button size="sm">Primary</Button>
                                        <Button variant="secondary" size="sm">Secondary</Button>
                                        <Button variant="outline" size="sm">Outline</Button>
                                        <Button variant="ghost" size="sm">Ghost</Button>
                                        <Button variant="accent" size="sm">Accent</Button>
                                        <Button variant="danger" size="sm">Danger</Button>
                                    </div>
                                </div>
                                <div>
                                    <p className="text-xs text-muted mb-2">Badges</p>
                                    <div className="flex flex-wrap gap-2">
                                        <Badge variant="primary">Primary</Badge>
                                        <Badge variant="success">Receivable</Badge>
                                        <Badge variant="danger">Payable</Badge>
                                        <Badge variant="warning">Variance</Badge>
                                        <Badge variant="info">Info</Badge>
                                        <Badge variant="outline">Outline</Badge>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </section>

                    {/* Footer */}
                    <footer className="mt-16 pt-8 border-t border-border text-center text-xs text-muted">
                        <p>
                            Mudaraba Profit Management · Laravel {`v13.29`} · Inertia + React + Tailwind 4
                        </p>
                        <p className="mt-1">
                            Phase 0 · Session 0.1 — Scaffolding complete. Design tokens applied.
                        </p>
                    </footer>
                </main>
            </div>
        </>
    );
}
