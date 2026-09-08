import { Head, Link, router } from "@inertiajs/react";
import { useState } from "react";
import { route } from "ziggy-js";
import { AuthenticatedLayout } from "@/Components/layout";
import {
    Card, CardContent, CardDescription, CardHeader, CardTitle,
    Button, Badge,
} from "@/Components/ui";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui";
import {
    TrendingUp, ChevronLeft, ChevronRight, Calendar, ReceiptText,
    Crown, AlertCircle, Info, FileSpreadsheet, Users,
} from "lucide-react";
import { PageTransition } from "@/Components/common";
import { formatBDT, formatPercent, cn } from "@/lib/utils";

interface GridItem {
    investor_id: number;
    investor_name: string;
    reference: string | null;
    deed_ratio: string;
    investment: number;           // D
    investment_ratio: number;     // E
    primary_profit_share: number; // Q/F
    actual_profit_at_full: number; // N
    deed_ratio_applied: number;    // AF
    actual_profit_due: number;     // AG
    advance_difference: number;   // AH
    retained_earnings_credit: number; // AJ
    net_settlement: number;        // AK
}

interface Totals {
    total_estimated: number;      // Z2
    total_actual: number;          // X2
    total_variance: number;        // Y2
    total_investment: number;      // D181
    total_profit_due: number;      // AG182
    total_advance_diff: number;    // AH182
    total_retained: number;        // AJ182
    my_profit: number;             // AG184
    my_profit_ratio: number;       // AG186
    active_investor_count: number;
    status: string;
}

interface Retained {
    total_amount: number;          // AI3
    investor_portion: number;      // AJ4
    my_portion: number;            // AK4
}

interface Props {
    month: string;
    monthLabel: string;
    grid: GridItem[];
    totals: Totals | null;
    retained: Retained | null;
    isCalculated: boolean;
    canEdit: boolean;
}

const tierBadge = (ratio: string) => {
    if (ratio === "100") return <Badge variant="success">100%</Badge>;
    if (ratio === "80") return <Badge variant="warning">80%</Badge>;
    return <Badge variant="info">60%</Badge>;
};

export default function InvestorProfitIndex({ month, monthLabel, grid, totals, retained, isCalculated, canEdit }: Props) {
    const [expandedRow, setExpandedRow] = useState<number | null>(null);

    const navigateMonth = (direction: "prev" | "next") => {
        const date = new Date(month);
        date.setMonth(date.getMonth() + (direction === "next" ? 1 : -1));
        const newMonth = date.toISOString().slice(0, 10);
        router.get(route("profit.investor.index"), { month: newMonth }, { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout title="Investor Profit">
            <Head title={`Investor Profit — ${monthLabel}`} />

            <PageTransition><div className="space-y-6">
                {/* Header with month switcher */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <div className="size-10 rounded-xl bg-primary-soft flex items-center justify-center">
                            <ReceiptText className="size-5 text-primary" />
                        </div>
                        <div>
                            <h1 className="font-display text-2xl font-bold tracking-tight">Investor Profit Distribution</h1>
                            <p className="text-sm text-muted">8-phase calculation engine results — the "For Sajid" page</p>
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

                {/* Not calculated yet banner */}
                {!isCalculated && (
                    <Card>
                        <CardContent className="p-8 text-center">
                            <AlertCircle className="size-12 text-muted mx-auto mb-4" />
                            <h3 className="font-display text-lg font-semibold">No profit calculation for {monthLabel}</h3>
                            <p className="text-sm text-muted mt-2 max-w-md mx-auto">
                                The M/Y needs to enter sector profits and finalize the month to trigger the
                                8-phase calculation engine. Visit the sector profit entry page to get started.
                            </p>
                            <Link href={route("profit.sector.index")} className="inline-block mt-4">
                                <Button><TrendingUp className="size-4" /> Go to Sector Profit Entry</Button>
                            </Link>
                        </CardContent>
                    </Card>
                )}

                {/* KPI summary cards */}
                {isCalculated && totals && (
                    <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <KpiCard
                            label="Total Investment"
                            value={formatBDT(totals.total_investment)}
                            hint={`D181 · ${totals.active_investor_count} investors`}
                            tone="primary"
                        />
                        <KpiCard
                            label="Total Actual Profit"
                            value={formatBDT(totals.total_actual)}
                            hint={`X2 · Z2: ${formatBDT(totals.total_estimated, false)}`}
                            tone="success"
                        />
                        <KpiCard
                            label="M/Y Profit"
                            value={formatBDT(totals.my_profit)}
                            hint={`AG184 · ${formatPercent(totals.my_profit_ratio)} (AG186)`}
                            tone="accent"
                        />
                        <KpiCard
                            label="Retained Earnings"
                            value={retained ? formatBDT(retained.total_amount) : "—"}
                            hint={retained ? `AI3 · 71/29 split` : "AI3"}
                            tone="info"
                        />
                    </div>
                )}

                {/* Retained earnings breakdown */}
                {isCalculated && retained && (
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base">Retained Earnings Breakdown</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-3 gap-4">
                                <div className="p-4 rounded-lg border border-border bg-surface-2/50">
                                    <p className="text-xs text-muted">Total (AI3)</p>
                                    <p className="font-num text-lg font-bold mt-1">{formatBDT(retained.total_amount)}</p>
                                </div>
                                <div className="p-4 rounded-lg border border-border bg-primary-soft/30">
                                    <p className="text-xs text-muted">Investors 71% (AJ4)</p>
                                    <p className="font-num text-lg font-bold text-primary mt-1">{formatBDT(retained.investor_portion)}</p>
                                </div>
                                <div className="p-4 rounded-lg border border-border bg-accent-soft/30">
                                    <p className="text-xs text-muted">M/Y 29% (AK4)</p>
                                    <p className="font-num text-lg font-bold text-accent mt-1">{formatBDT(retained.my_portion)}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* The main grid — "For Sajid" */}
                {isCalculated && grid.length > 0 && (
                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <div>
                                    <CardTitle>Investor Profit Grid</CardTitle>
                                    <CardDescription>
                                        {grid.length} investors · click a row to expand the retained earnings breakdown
                                    </CardDescription>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Badge variant={totals?.status === "finalized" ? "success" : "warning"}>
                                        {totals?.status}
                                    </Badge>
                                    {totals && (
                                        <Badge variant="accent">
                                            <Crown className="size-3" /> M/Y: {formatPercent(totals.my_profit_ratio)}
                                        </Badge>
                                    )}
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="p-0">
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="sticky left-0 bg-surface-2 z-10">Investor</TableHead>
                                            <TableHead className="text-right">Investment (D)</TableHead>
                                            <TableHead className="text-right">Ratio (E)</TableHead>
                                            <TableHead className="text-right">Primary (Q)</TableHead>
                                            <TableHead className="text-right">Actual@100% (N)</TableHead>
                                            <TableHead className="text-center">Tier (AF)</TableHead>
                                            <TableHead className="text-right">Profit Due (AG)</TableHead>
                                            <TableHead className="text-right">Advance Diff (AH)</TableHead>
                                            <TableHead className="text-right">Retained (AJ)</TableHead>
                                            <TableHead className="text-right">Net Settle (AK)</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {grid.map((item) => {
                                            const isExpanded = expandedRow === item.investor_id;
                                            const isOverpaid = item.advance_difference > 0;
                                            const isUnderpaid = item.advance_difference < 0;
                                            const netReceivable = item.net_settlement > 0;
                                            const netPayable = item.net_settlement < 0;

                                            return (
                                                <>
                                                    <TableRow
                                                        key={item.investor_id}
                                                        className="cursor-pointer hover:bg-surface-2/50"
                                                        onClick={() => setExpandedRow(isExpanded ? null : item.investor_id)}
                                                    >
                                                        <TableCell className="sticky left-0 bg-surface z-10 font-medium">
                                                            <div className="flex items-center gap-2">
                                                                <ChevronRight className={cn("size-4 text-muted transition-transform", isExpanded && "rotate-90")} />
                                                                <span>{item.investor_name}</span>
                                                                {item.reference && (
                                                                    <Badge variant="outline" className="text-[10px]">{item.reference}</Badge>
                                                                )}
                                                            </div>
                                                        </TableCell>
                                                        <TableCell className="text-right font-num">{formatBDT(item.investment, false)}</TableCell>
                                                        <TableCell className="text-right font-num text-muted">{formatPercent(item.investment_ratio * 100, 4)}</TableCell>
                                                        <TableCell className="text-right font-num">{formatBDT(item.primary_profit_share, false)}</TableCell>
                                                        <TableCell className="text-right font-num text-muted">{formatBDT(item.actual_profit_at_full, false)}</TableCell>
                                                        <TableCell className="text-center">{tierBadge(item.deed_ratio)}</TableCell>
                                                        <TableCell className="text-right font-num text-success">{formatBDT(item.actual_profit_due, false)}</TableCell>
                                                        <TableCell className={cn(
                                                            "text-right font-num font-medium",
                                                            isOverpaid ? "text-danger" : isUnderpaid ? "text-success" : "text-muted",
                                                        )}>
                                                            {item.advance_difference > 0 ? "+" : ""}{formatBDT(item.advance_difference, false)}
                                                        </TableCell>
                                                        <TableCell className="text-right font-num text-accent">{formatBDT(item.retained_earnings_credit, false)}</TableCell>
                                                        <TableCell className={cn(
                                                            "text-right font-num font-bold",
                                                            netReceivable ? "text-danger" : netPayable ? "text-success" : "text-muted",
                                                        )}>
                                                            {item.net_settlement > 0 ? "+" : ""}{formatBDT(item.net_settlement, false)}
                                                        </TableCell>
                                                    </TableRow>
                                                    {isExpanded && (
                                                        <TableRow key={`${item.investor_id}-expanded`} className="bg-surface-2/30">
                                                            <TableCell colSpan={10} className="py-4">
                                                                <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 ml-8">
                                                                    <ExpandedField label="Investment (D)" value={formatBDT(item.investment)} />
                                                                    <ExpandedField label="Ratio (E)" value={formatPercent(item.investment_ratio * 100, 4)} />
                                                                    <ExpandedField label="Primary Share (Q)" value={formatBDT(item.primary_profit_share)} />
                                                                    <ExpandedField label="Actual @ 100% (N)" value={formatBDT(item.actual_profit_at_full)} />
                                                                    <ExpandedField label="Deed Ratio (AF)" value={`${item.deed_ratio}%`} />
                                                                    <ExpandedField label="Profit Due (AG)" value={formatBDT(item.actual_profit_due)} tone="success" />
                                                                    <ExpandedField
                                                                        label="Advance Diff (AH)"
                                                                        value={`${item.advance_difference > 0 ? "+" : ""}${formatBDT(item.advance_difference)}`}
                                                                        tone={isOverpaid ? "danger" : isUnderpaid ? "success" : undefined}
                                                                    />
                                                                    <ExpandedField
                                                                        label="Net Settlement (AK)"
                                                                        value={`${item.net_settlement > 0 ? "+" : ""}${formatBDT(item.net_settlement)}`}
                                                                        tone={netReceivable ? "danger" : netPayable ? "success" : undefined}
                                                                    />
                                                                </div>
                                                                <div className="mt-4 ml-8 flex items-center gap-2 text-xs text-muted">
                                                                    <Info className="size-3" />
                                                                    {netReceivable && `Investor owes M/Y ${formatBDT(item.net_settlement)} (after retained credit)`}
                                                                    {netPayable && `M/Y owes investor ${formatBDT(Math.abs(item.net_settlement))} (retained credit exceeds advance difference)`}
                                                                    {!netReceivable && !netPayable && "Settled — no amount owed either way"}
                                                                </div>
                                                            </TableCell>
                                                        </TableRow>
                                                    )}
                                                </>
                                            );
                                        })}
                                    </TableBody>
                                </Table>
                            </div>

                            {/* Sticky totals row — Excel AG182, AH182, AG184, AG186 */}
                            {totals && (
                                <div className="border-t-2 border-primary/30 bg-primary-soft/20">
                                    <div className="grid grid-cols-[minmax(200px,1fr)_repeat(9,minmax(120px,1fr))] gap-0">
                                        <div className="p-4 font-display font-bold flex items-center gap-2">
                                            <Users className="size-4 text-primary" />
                                            TOTALS ({totals.active_investor_count})
                                        </div>
                                        <div className="p-4 text-right font-num font-bold">
                                            {formatBDT(totals.total_investment, false)}
                                            <div className="text-[10px] text-muted font-normal">D181</div>
                                        </div>
                                        <div className="p-4 text-right font-num font-bold text-muted">
                                            100%
                                            <div className="text-[10px] text-muted font-normal">E</div>
                                        </div>
                                        <div className="p-4 text-right font-num font-bold">
                                            {formatBDT(totals.total_estimated, false)}
                                            <div className="text-[10px] text-muted font-normal">Z2</div>
                                        </div>
                                        <div className="p-4 text-right font-num font-bold text-muted">
                                            {formatBDT(totals.total_actual, false)}
                                            <div className="text-[10px] text-muted font-normal">X2</div>
                                        </div>
                                        <div className="p-4 text-center font-bold text-muted">—</div>
                                        <div className="p-4 text-right font-num font-bold text-success">
                                            {formatBDT(totals.total_profit_due, false)}
                                            <div className="text-[10px] text-muted font-normal">AG182</div>
                                        </div>
                                        <div className={cn(
                                            "p-4 text-right font-num font-bold",
                                            totals.total_advance_diff > 0 ? "text-danger" : "text-success",
                                        )}>
                                            {totals.total_advance_diff > 0 ? "+" : ""}{formatBDT(totals.total_advance_diff, false)}
                                            <div className="text-[10px] text-muted font-normal">AH182</div>
                                        </div>
                                        <div className="p-4 text-right font-num font-bold text-accent">
                                            {formatBDT(totals.total_retained, false)}
                                            <div className="text-[10px] text-muted font-normal">AJ182</div>
                                        </div>
                                        <div className="p-4 text-right font-num font-bold text-primary">
                                            {formatBDT(totals.my_profit, false)}
                                            <div className="text-[10px] text-muted font-normal">AG184</div>
                                        </div>
                                    </div>
                                    <div className="px-4 py-2 border-t border-border/50 flex items-center justify-between text-xs">
                                        <span className="text-muted">
                                            M/Y Profit Ratio (AG186): <span className="font-num font-bold text-accent">{formatPercent(totals.my_profit_ratio)}</span>
                                        </span>
                                        <span className="text-muted">
                                            Sector Variance (Y2 = Z2 − X2): <span className="font-num font-bold">{formatBDT(totals.total_variance, false)}</span>
                                        </span>
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                )}

                {/* Excel cell reference legend */}
                {isCalculated && (
                    <div className="flex flex-wrap items-center gap-4 text-xs text-muted">
                        <span className="flex items-center gap-1">
                            <Badge variant="outline" className="font-num">D</Badge> Investment
                        </span>
                        <span className="flex items-center gap-1">
                            <Badge variant="outline" className="font-num">E</Badge> Ratio
                        </span>
                        <span className="flex items-center gap-1">
                            <Badge variant="outline" className="font-num">Q</Badge> Primary Share
                        </span>
                        <span className="flex items-center gap-1">
                            <Badge variant="outline" className="font-num">N</Badge> Actual @ 100%
                        </span>
                        <span className="flex items-center gap-1">
                            <Badge variant="outline" className="font-num">AF</Badge> Deed Tier
                        </span>
                        <span className="flex items-center gap-1">
                            <Badge variant="outline" className="font-num">AG</Badge> Profit Due
                        </span>
                        <span className="flex items-center gap-1">
                            <Badge variant="outline" className="font-num">AH</Badge> Advance Diff (+/−)
                        </span>
                        <span className="flex items-center gap-1">
                            <Badge variant="outline" className="font-num">AJ</Badge> Retained Credit
                        </span>
                        <span className="flex items-center gap-1">
                            <Badge variant="outline" className="font-num">AK</Badge> Net Settlement
                        </span>
                    </div>
                )}
            </div></PageTransition>
        </AuthenticatedLayout>
    );
}

function KpiCard({ label, value, hint, tone }: { label: string; value: string; hint: string; tone: string }) {
    const icons: Record<string, typeof TrendingUp> = {
        primary: TrendingUp,
        success: ReceiptText,
        accent: Crown,
        info: FileSpreadsheet,
    };
    const Icon = icons[tone] ?? TrendingUp;
    return (
        <Card>
            <CardContent className="p-4">
                <div className={cn("size-9 rounded-lg flex items-center justify-center bg-${tone}-soft")}>
                    <Icon className={cn("size-4 text-${tone}")} />
                </div>
                <p className="text-xs text-muted mt-3">{label}</p>
                <p className="font-num text-xl font-semibold mt-1">{value}</p>
                <p className="text-[10px] text-muted mt-1">{hint}</p>
            </CardContent>
        </Card>
    );
}

function ExpandedField({ label, value, tone }: { label: string; value: string; tone?: string }) {
    return (
        <div>
            <p className="text-[10px] text-muted uppercase tracking-wider">{label}</p>
            <p className={cn("font-num font-medium", tone === "success" && "text-success", tone === "danger" && "text-danger")}>
                {value}
            </p>
        </div>
    );
}
