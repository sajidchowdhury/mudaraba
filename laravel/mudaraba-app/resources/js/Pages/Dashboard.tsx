import { AuthenticatedLayout } from "@/Components/layout";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
    Button,
    Badge,
} from "@/Components/ui";
import { formatBDT, formatNumber } from "@/lib/utils";
import {
    Wallet,
    TrendingUp,
    CircleDollarSign,
    Users,
    ArrowRight,
    ChevronRight,
} from "lucide-react";

interface Kpi {
    label: string;
    value: number;
    change: string;
    tone: "primary" | "success" | "accent" | "info";
    hint: string;
}

interface DashboardProps {
    appName: string;
    kpis: Kpi[];
}

const ICONS = {
    primary: Wallet,
    success: TrendingUp,
    accent: CircleDollarSign,
    info: Users,
} as const;

export default function Dashboard({ appName, kpis }: DashboardProps) {
    return (
        <AuthenticatedLayout
            title="Dashboard"
            actions={
                <Button size="sm">
                    New Investor <ArrowRight className="size-3.5" />
                </Button>
            }
        >
            <div className="space-y-8">
                {/* Hero greeting */}
                <section>
                    <h1 className="font-display text-3xl font-bold tracking-tight">
                        Assalamu Alaikum, Sajid 👋
                    </h1>
                    <p className="mt-1 text-muted">
                        Here's the July 2026 profit cycle at a glance.
                    </p>
                </section>

                {/* KPI grid */}
                <section className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {kpis.map((kpi) => {
                        const Icon = ICONS[kpi.tone];
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
                                        {kpi.label.includes("Investors") ? formatNumber(kpi.value) : formatBDT(kpi.value)}
                                    </p>
                                    <p className="text-xs text-muted mt-1">{kpi.hint}</p>
                                </CardContent>
                            </Card>
                        );
                    })}
                </section>

                {/* Quick actions */}
                <section className="grid gap-4 md:grid-cols-3">
                    {[
                        { title: "Enter Sector Profit", desc: "Record this month's estimated & actual profit per sector", href: "/profit/sector", tone: "primary" as const },
                        { title: "Investor Profit", desc: "View & reconcile the per-investor distribution grid", href: "/profit/investor", tone: "success" as const },
                        { title: "M / Y Ledger", desc: "Review director transactions and retained earnings", href: "/reports/my-ledger", tone: "accent" as const },
                    ].map((qa) => (
                        <Card key={qa.title} className="cursor-pointer hover:border-primary/40 hover:shadow-[var(--shadow-lifted)] transition-all group">
                            <CardContent className="p-6">
                                <div className="flex items-start justify-between">
                                    <div>
                                        <Badge variant={qa.tone} className="mb-2">Quick action</Badge>
                                        <p className="font-display font-semibold text-lg">{qa.title}</p>
                                        <p className="text-sm text-muted mt-1">{qa.desc}</p>
                                    </div>
                                    <ChevronRight className="size-5 text-muted group-hover:text-primary group-hover:translate-x-1 transition-all" />
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </section>

                {/* Recent activity */}
                <section>
                    <Card>
                        <CardHeader>
                            <CardTitle>Recent Activity</CardTitle>
                            <CardDescription>Last 5 transactions across the system</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {[
                                { action: "Month finalized", detail: "July 2026 — all 17 sectors reconciled", time: "2m ago", tone: "success" as const },
                                { action: "Investor added", detail: "Siddik U · BDT 6,000,000 · Tier 100%", time: "1h ago", tone: "info" as const },
                                { action: "Variance detected", detail: "Bike X primary exceeds actual by ৳30,000", time: "3h ago", tone: "warning" as const },
                                { action: "Retained earnings applied", detail: "৳200,000 split 71/29 for July 2026", time: "5h ago", tone: "accent" as const },
                                { action: "Withdrawal", detail: "M/Y withdrew ৳50,000", time: "1d ago", tone: "default" as const },
                            ].map((item, i) => (
                                <div key={i} className="flex items-center gap-3 py-2 border-b border-border last:border-0">
                                    <Badge variant={item.tone} className="shrink-0">{item.action}</Badge>
                                    <p className="text-sm flex-1 truncate">{item.detail}</p>
                                    <span className="text-xs text-muted font-num shrink-0">{item.time}</span>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                </section>
            </div>
        </AuthenticatedLayout>
    );
}
