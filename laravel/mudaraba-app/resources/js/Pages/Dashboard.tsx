import { AuthenticatedLayout } from "@/Components/layout";
import { Head, Link } from "@inertiajs/react";
import { route } from "ziggy-js";
import {
    Card, CardContent, CardDescription, CardHeader, CardTitle,
    Button, Badge,
} from "@/Components/ui";
import { formatBDT, formatNumber, cn } from "@/lib/utils";
import {
    Wallet, TrendingUp, CircleDollarSign, Users,
    ArrowRight, ChevronRight, Activity,
    PieChart, ReceiptText, Settings2, CalendarClock,
} from "lucide-react";
import {
    LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip,
    ResponsiveContainer, PieChart as RechartsPie, Pie, Cell, Legend,
} from "recharts";
import { useState, useEffect, useRef } from "react";

interface Kpi {
    label: string;
    value: number;
    tone: "primary" | "success" | "accent" | "info";
    hint: string;
    icon: string;
}

interface TrendItem {
    month: string;
    label: string;
    estimated: number;
    actual: number;
    my_profit: number;
}

interface SectorItem {
    name: string;
    value: number;
}

interface TierItem {
    name: string;
    value: number;
    color: string;
}

interface ActivityItem {
    action: string;
    entity_type: string;
    user: string;
    created_at: string;
}

interface DashboardProps {
    appName: string;
    auth: { user: { id: number; username: string; role: string; name: string } | null };
    kpis: Kpi[];
    trend: TrendItem[];
    sectorAllocation: SectorItem[];
    tierDistribution: TierItem[];
    recentActivity: ActivityItem[];
    hasData: boolean;
}

const ICONS: Record<string, typeof Wallet> = {
    Wallet, TrendingUp, CircleDollarSign, Users,
};

function useCountUp(target: number, duration = 1000) {
    const [value, setValue] = useState(0);
    const rafRef = useRef<number>();
    useEffect(() => {
        const start = performance.now();
        const animate = (now: number) => {
            const elapsed = now - start;
            const progress = Math.min(elapsed / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3); // ease-out cubic
            setValue(target * eased);
            if (progress < 1) rafRef.current = requestAnimationFrame(animate);
        };
        rafRef.current = requestAnimationFrame(animate);
        return () => { if (rafRef.current) cancelAnimationFrame(rafRef.current); };
    }, [target, duration]);
    return value;
}

function KpiCard({ kpi }: { kpi: Kpi }) {
    const Icon = ICONS[kpi.icon] ?? Wallet;
    const animatedValue = useCountUp(kpi.value);
    const isCount = kpi.icon === "Users";
    const displayValue = isCount ? formatNumber(Math.round(animatedValue)) : formatBDT(animatedValue);

    return (
        <Card className="hover:shadow-[var(--shadow-lifted)] transition-shadow">
            <CardContent className="p-4">
                <div className="flex items-start justify-between">
                    <div className={cn("size-10 rounded-lg flex items-center justify-center bg-${kpi.tone}-soft")}>
                        <Icon className={cn("size-5 text-${kpi.tone}")} />
                    </div>
                </div>
                <p className="text-sm text-muted mt-3">{kpi.label}</p>
                <p className="font-num text-2xl font-semibold mt-1 tracking-tight">
                    {displayValue}
                </p>
                <p className="text-xs text-muted mt-1">{kpi.hint}</p>
            </CardContent>
        </Card>
    );
}

export default function Dashboard({ appName, auth, kpis, trend, sectorAllocation, tierDistribution, recentActivity, hasData }: DashboardProps) {
    const userName = auth?.user?.name ?? "M / Y Owner";

    // Chart colors
    const PIE_COLORS = ["#10B981", "#F59E0B", "#06B6D4", "#EF4444", "#8B5CF6", "#EC4899", "#14B8A6", "#F97316"];

    // Quick actions
    const quickActions = [
        { title: "Sector Profit", desc: "Enter this month's estimated & actual", href: route("profit.sector.index"), icon: TrendingUp, tone: "primary" },
        { title: "Investor Profit", desc: "View & reconcile distribution grid", href: route("profit.investor.index"), icon: ReceiptText, tone: "success" },
        { title: "Month Close", desc: "Checklist + lock the month", href: route("month-close.index"), icon: CalendarClock, tone: "accent" },
        { title: "Adjustments", desc: "Fund A/B + Direct adjustments", href: route("adjustments.index"), icon: Settings2, tone: "info" },
    ];

    return (
        <AuthenticatedLayout
            title="Dashboard"
            actions={
                <Link href={route("investors.new")}>
                    <Button size="sm"><ArrowRight className="size-4" /> New Investor</Button>
                </Link>
            }
        >
            <Head title="Dashboard" />

            <div className="space-y-8">
                {/* Hero greeting */}
                <section>
                    <h1 className="font-display text-3xl font-bold tracking-tight">
                        Assalamu Alaikum, {userName} 👋
                    </h1>
                    <p className="mt-1 text-muted">
                        Here's the {new Date().toLocaleDateString("en-US", { month: "long", year: "numeric" })} profit cycle at a glance.
                    </p>
                </section>

                {/* KPI grid */}
                <section className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {kpis.map((kpi) => (
                        <KpiCard key={kpi.label} kpi={kpi} />
                    ))}
                </section>

                {/* Charts */}
                <section className="grid gap-6 lg:grid-cols-2">
                    {/* Profit trend */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Monthly Profit Trend</CardTitle>
                            <CardDescription>Estimated vs Actual profit (last 6 months)</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {trend.every(t => t.actual === 0 && t.estimated === 0) ? (
                                <div className="h-64 flex items-center justify-center text-muted text-sm">
                                    No profit data yet. Finalize a month to see the trend.
                                </div>
                            ) : (
                                <ResponsiveContainer width="100%" height={250}>
                                    <LineChart data={trend}>
                                        <CartesianGrid strokeDasharray="3 3" stroke="var(--color-border)" />
                                        <XAxis dataKey="label" stroke="var(--color-muted)" fontSize={12} />
                                        <YAxis stroke="var(--color-muted)" fontSize={12} tickFormatter={(v) => v >= 1000000 ? `${(v / 1000000).toFixed(1)}M` : v >= 1000 ? `${(v / 1000).toFixed(0)}K` : v} />
                                        <Tooltip
                                            contentStyle={{ background: "var(--color-surface)", border: "1px solid var(--color-border)", borderRadius: "8px", fontSize: "12px" }}
                                            formatter={(value: number) => formatBDT(value)}
                                        />
                                        <Line type="monotone" dataKey="estimated" stroke="#F59E0B" strokeWidth={2} dot={{ r: 3 }} name="Estimated (Z2)" />
                                        <Line type="monotone" dataKey="actual" stroke="#10B981" strokeWidth={2} dot={{ r: 3 }} name="Actual (X2)" />
                                        <Line type="monotone" dataKey="my_profit" stroke="#06B6D4" strokeWidth={2} dot={{ r: 3 }} name="M/Y Profit (AG184)" strokeDasharray="5 5" />
                                    </LineChart>
                                </ResponsiveContainer>
                            )}
                        </CardContent>
                    </Card>

                    {/* Sector allocation donut */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Sector Allocation</CardTitle>
                            <CardDescription>Capital distribution across active sectors</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {sectorAllocation.every(s => s.value === 0) ? (
                                <div className="h-64 flex items-center justify-center text-muted text-sm">
                                    No sector balances yet. Set opening balances to see the allocation.
                                </div>
                            ) : (
                                <ResponsiveContainer width="100%" height={250}>
                                    <RechartsPie>
                                        <Pie
                                            data={sectorAllocation}
                                            dataKey="value"
                                            nameKey="name"
                                            cx="50%"
                                            cy="50%"
                                            outerRadius={80}
                                            innerRadius={50}
                                            paddingAngle={2}
                                        >
                                            {sectorAllocation.map((_, i) => (
                                                <Cell key={i} fill={PIE_COLORS[i % PIE_COLORS.length]} />
                                            ))}
                                        </Pie>
                                        <Tooltip
                                            contentStyle={{ background: "var(--color-surface)", border: "1px solid var(--color-border)", borderRadius: "8px", fontSize: "12px" }}
                                            formatter={(value: number) => formatBDT(value)}
                                        />
                                        <Legend wrapperStyle={{ fontSize: "11px" }} />
                                    </RechartsPie>
                                </ResponsiveContainer>
                            )}
                        </CardContent>
                    </Card>
                </section>

                {/* Quick actions + Recent activity */}
                <section className="grid gap-6 lg:grid-cols-2">
                    {/* Quick actions */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Quick Actions</CardTitle>
                            <CardDescription>Jump to the most common tasks</CardDescription>
                        </CardHeader>
                        <CardContent className="grid sm:grid-cols-2 gap-3">
                            {quickActions.map((qa) => {
                                const Icon = qa.icon;
                                return (
                                    <Link key={qa.title} href={qa.href}>
                                        <Card className="cursor-pointer hover:border-primary/40 hover:shadow-[var(--shadow-lifted)] transition-all group">
                                            <CardContent className="p-4">
                                                <div className="flex items-start justify-between">
                                                    <div>
                                                        <div className={cn("size-8 rounded-lg flex items-center justify-center bg-${qa.tone}-soft mb-2")}>
                                                            <Icon className={cn("size-4 text-${qa.tone}")} />
                                                        </div>
                                                        <p className="font-medium text-sm">{qa.title}</p>
                                                        <p className="text-xs text-muted mt-0.5">{qa.desc}</p>
                                                    </div>
                                                    <ChevronRight className="size-4 text-muted group-hover:text-primary group-hover:translate-x-1 transition-all" />
                                                </div>
                                            </CardContent>
                                        </Card>
                                    </Link>
                                );
                            })}
                        </CardContent>
                    </Card>

                    {/* Recent activity */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Activity className="size-4 text-primary" />
                                Recent Activity
                            </CardTitle>
                            <CardDescription>Latest system events</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3 max-h-80 overflow-y-auto">
                            {recentActivity.length === 0 ? (
                                <p className="text-sm text-muted text-center py-8">No activity recorded yet.</p>
                            ) : (
                                recentActivity.map((item, i) => (
                                    <div key={i} className="flex items-center gap-3 py-2 border-b border-border last:border-0">
                                        <div className="size-2 rounded-full bg-primary shrink-0" />
                                        <div className="flex-1 min-w-0">
                                            <p className="text-sm font-medium truncate">{item.action}</p>
                                            <p className="text-xs text-muted">{item.entity_type} · by {item.user}</p>
                                        </div>
                                        <span className="text-xs text-muted font-num shrink-0">{item.created_at}</span>
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>
                </section>

                {/* Investor tier distribution */}
                <section>
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <PieChart className="size-4 text-primary" />
                                Investor Tier Distribution
                            </CardTitle>
                            <CardDescription>Active investors by deed ratio</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-3 gap-4">
                                {tierDistribution.map((tier) => (
                                    <div key={tier.name} className="p-4 rounded-lg border border-border text-center">
                                        <div className="size-12 rounded-full mx-auto flex items-center justify-center" style={{ background: tier.color + "20" }}>
                                            <span className="font-bold text-lg" style={{ color: tier.color }}>{tier.value}</span>
                                        </div>
                                        <p className="text-sm font-medium mt-2">{tier.name}</p>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                </section>
            </div>
        </AuthenticatedLayout>
    );
}
