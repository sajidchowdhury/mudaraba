import { Head, router } from "@inertiajs/react";
import { useState } from "react";
import { route } from "ziggy-js";
import { AuthenticatedLayout } from "@/Components/layout";
import {
    Card, CardContent, CardDescription, CardHeader, CardTitle,
    Button, Badge, Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from "@/Components/ui";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui";
import {
    DollarSign, ChevronLeft, ChevronRight, Calendar, Download,
    TrendingUp, Users, ReceiptText, Crown,
} from "lucide-react";
import { formatBDT, formatPercent, cn } from "@/lib/utils";
import { toast } from "sonner";

interface GridItem {
    investor_id: number;
    name: string;
    reference: string | null;
    deed_ratio: string;
    investment: number;
    investment_ratio: number;
    primary_profit_share: number;
    actual_profit_due: number;
    advance_difference: number;
    retained_credit: number;
    net_settlement: number;
    profit_ratio: number;
    has_detail: boolean;
}

interface Totals {
    investment: number;
    profit_due: number;
    advance_diff: number;
    retained: number;
    investor_count: number;
    my_profit: number;
    my_profit_ratio: number;
    total_actual: number;
}

interface Props {
    month: string;
    monthLabel: string;
    grid: GridItem[];
    totals: Totals;
    tierFilter: string;
    hasData: boolean;
}

const tierBadge = (ratio: string) => {
    if (ratio === "100") return <Badge variant="success">100%</Badge>;
    if (ratio === "80") return <Badge variant="warning">80%</Badge>;
    return <Badge variant="info">60%</Badge>;
};

export default function InvestmentProfit({ month, monthLabel, grid, totals, tierFilter, hasData }: Props) {
    const [tier, setTier] = useState(tierFilter ?? "all");

    const navigateMonth = (direction: "prev" | "next") => {
        const date = new Date(month);
        date.setMonth(date.getMonth() + (direction === "next" ? 1 : -1));
        const newMonth = date.toISOString().slice(0, 10);
        router.get(route("reports.investment-profit"), { month: newMonth, tier }, { preserveScroll: true });
    };

    const applyTier = () => {
        router.get(route("reports.investment-profit"), { month, tier }, { preserveScroll: true, preserveState: true });
    };

    return (
        <AuthenticatedLayout title="Investment Profit Report">
            <Head title={`Investment Profit — ${monthLabel}`} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <div className="size-10 rounded-xl bg-primary-soft flex items-center justify-center">
                            <DollarSign className="size-5 text-primary" />
                        </div>
                        <div>
                            <h1 className="font-display text-2xl font-bold tracking-tight">Investment Profit Report</h1>
                            <p className="text-sm text-muted">Cross-investor comparative view</p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="icon" onClick={() => navigateMonth("prev")} aria-label="Previous month">
                            <ChevronLeft className="size-4" />
                        </Button>
                        <div className="flex items-center gap-2 px-4 py-2 rounded-lg border border-border bg-surface">
                            <Calendar className="size-4 text-primary" />
                            <span className="font-medium">{monthLabel}</span>
                        </div>
                        <Button variant="outline" size="icon" onClick={() => navigateMonth("next")} aria-label="Next month">
                            <ChevronRight className="size-4" />
                        </Button>
                    </div>
                </div>

                {/* Summary cards */}
                {hasData && (
                    <div className="grid grid-cols-2 lg:grid-cols-5 gap-4">
                        <Card><CardContent className="p-4">
                            <div className="size-9 rounded-lg bg-primary-soft flex items-center justify-center"><Users className="size-4 text-primary" /></div>
                            <p className="text-xs text-muted mt-3">Total Investment</p>
                            <p className="font-num text-lg font-semibold mt-1">{formatBDT(totals.investment)}</p>
                            <p className="text-[10px] text-muted">{totals.investor_count} investors</p>
                        </CardContent></Card>
                        <Card><CardContent className="p-4">
                            <div className="size-9 rounded-lg bg-success-soft flex items-center justify-center"><TrendingUp className="size-4 text-success" /></div>
                            <p className="text-xs text-muted mt-3">Total Actual (X2)</p>
                            <p className="font-num text-lg font-semibold mt-1">{formatBDT(totals.total_actual)}</p>
                        </CardContent></Card>
                        <Card><CardContent className="p-4">
                            <div className="size-9 rounded-lg bg-accent-soft flex items-center justify-center"><ReceiptText className="size-4 text-accent" /></div>
                            <p className="text-xs text-muted mt-3">Investor Profit Due</p>
                            <p className="font-num text-lg font-semibold mt-1 text-success">{formatBDT(totals.profit_due, false)}</p>
                        </CardContent></Card>
                        <Card><CardContent className="p-4">
                            <div className="size-9 rounded-lg bg-info-soft flex items-center justify-center"><Crown className="size-4 text-info" /></div>
                            <p className="text-xs text-muted mt-3">M/Y Profit (AG184)</p>
                            <p className="font-num text-lg font-semibold mt-1 text-accent">{formatBDT(totals.my_profit, false)}</p>
                            <p className="text-[10px] text-muted">{formatPercent(totals.my_profit_ratio)} ratio</p>
                        </CardContent></Card>
                        <Card><CardContent className="p-4">
                            <div className="size-9 rounded-lg bg-warning-soft flex items-center justify-center"><DollarSign className="size-4 text-warning" /></div>
                            <p className="text-xs text-muted mt-3">Retained Credit</p>
                            <p className="font-num text-lg font-semibold mt-1 text-warning">{formatBDT(totals.retained, false)}</p>
                        </CardContent></Card>
                    </div>
                )}

                {/* Tier filter + export */}
                <Card>
                    <CardContent className="p-4">
                        <div className="flex items-center justify-between gap-4">
                            <div className="flex items-center gap-3">
                                <span className="text-sm font-medium">Filter by Tier:</span>
                                <Select value={tier} onValueChange={setTier}>
                                    <SelectTrigger className="w-40"><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All tiers</SelectItem>
                                        <SelectItem value="100">Tier 100%</SelectItem>
                                        <SelectItem value="80">Tier 80%</SelectItem>
                                        <SelectItem value="60">Tier 60%</SelectItem>
                                    </SelectContent>
                                </Select>
                                <Button variant="outline" size="sm" onClick={applyTier}>Apply</Button>
                            </div>
                            <Button variant="outline" size="sm" onClick={() => toast.info("Excel export coming in Phase 7.6")}>
                                <Download className="size-4" /> Export
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Comparative table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Investor Comparison — {monthLabel}</CardTitle>
                        <CardDescription>
                            {hasData ? `${grid.length} investors sorted by investment (largest first)` : "No profit calculation for this month"}
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="sticky left-0 bg-surface-2 z-10">Investor</TableHead>
                                        <TableHead className="text-center">Tier</TableHead>
                                        <TableHead className="text-right">Investment (D)</TableHead>
                                        <TableHead className="text-right">Ratio (E)</TableHead>
                                        <TableHead className="text-right">Primary (Q)</TableHead>
                                        <TableHead className="text-right">Profit Due (AG)</TableHead>
                                        <TableHead className="text-right">Profit Ratio</TableHead>
                                        <TableHead className="text-right">Advance Diff (AH)</TableHead>
                                        <TableHead className="text-right">Retained (AJ)</TableHead>
                                        <TableHead className="text-right">Net (AK)</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {!hasData || grid.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={10} className="text-center py-12 text-muted">
                                                {hasData ? "No investors match the selected tier filter." : `No profit calculation for ${monthLabel}. Finalize sector profits first.`}
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        grid.map((item) => (
                                            <TableRow key={item.investor_id}>
                                                <TableCell className="sticky left-0 bg-surface z-10 font-medium">
                                                    <div className="flex items-center gap-2">
                                                        <span>{item.name}</span>
                                                        {item.reference && <Badge variant="outline" className="text-[10px]">{item.reference}</Badge>}
                                                    </div>
                                                </TableCell>
                                                <TableCell className="text-center">{tierBadge(item.deed_ratio)}</TableCell>
                                                <TableCell className="text-right font-num">{formatBDT(item.investment, false)}</TableCell>
                                                <TableCell className="text-right font-num text-muted">{formatPercent(item.investment_ratio * 100, 4)}</TableCell>
                                                <TableCell className="text-right font-num">{formatBDT(item.primary_profit_share, false)}</TableCell>
                                                <TableCell className="text-right font-num text-success">{formatBDT(item.actual_profit_due, false)}</TableCell>
                                                <TableCell className="text-right font-num">
                                                    {item.profit_ratio > 0 ? <Badge variant={item.deed_ratio === "100" ? "success" : item.deed_ratio === "80" ? "warning" : "info"}>{formatPercent(item.profit_ratio)}</Badge> : "—"}
                                                </TableCell>
                                                <TableCell className={cn("text-right font-num font-medium", item.advance_difference > 0 ? "text-danger" : item.advance_difference < 0 ? "text-success" : "text-muted")}>
                                                    {item.advance_difference > 0 ? "+" : ""}{formatBDT(item.advance_difference, false)}
                                                </TableCell>
                                                <TableCell className="text-right font-num text-accent">{formatBDT(item.retained_credit, false)}</TableCell>
                                                <TableCell className={cn("text-right font-num font-bold", item.net_settlement > 0 ? "text-danger" : item.net_settlement < 0 ? "text-success" : "text-muted")}>
                                                    {item.net_settlement > 0 ? "+" : ""}{formatBDT(item.net_settlement, false)}
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {/* Totals row */}
                        {hasData && grid.length > 0 && (
                            <div className="border-t-2 border-primary/30 bg-primary-soft/20">
                                <div className="grid grid-cols-[minmax(180px,1fr)_60px_repeat(8,minmax(120px,1fr))] gap-0">
                                    <div className="p-4 font-display font-bold">TOTALS ({totals.investor_count})</div>
                                    <div className="p-4"></div>
                                    <div className="p-4 text-right font-num font-bold">{formatBDT(totals.investment, false)}</div>
                                    <div className="p-4 text-right font-num font-bold text-muted">100%</div>
                                    <div className="p-4 text-right font-num font-bold text-muted">—</div>
                                    <div className="p-4 text-right font-num font-bold text-success">{formatBDT(totals.profit_due, false)}</div>
                                    <div className="p-4 text-right font-num font-bold text-muted">AG182</div>
                                    <div className="p-4 text-right font-num font-bold text-muted">—</div>
                                    <div className="p-4 text-right font-num font-bold text-accent">{formatBDT(totals.retained, false)}</div>
                                    <div className="p-4 text-right font-num font-bold text-primary">{formatBDT(totals.my_profit, false)}</div>
                                </div>
                                <div className="px-4 py-2 border-t border-border/50 text-xs text-muted">
                                    M/Y Profit Ratio (AG186): <span className="font-num font-bold text-accent">{formatPercent(totals.my_profit_ratio)}</span>
                                    {" · "}
                                    Total Actual (X2): <span className="font-num font-bold">{formatBDT(totals.total_actual, false)}</span>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
