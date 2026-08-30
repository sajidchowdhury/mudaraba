import { Head, router, useForm } from "@inertiajs/react";
import { useState, useMemo, useEffect } from "react";
import { route } from "ziggy-js";
import { AuthenticatedLayout } from "@/Components/layout";
import {
    Card, CardContent, CardDescription, CardHeader, CardTitle,
    Button, Badge, Input,
} from "@/Components/ui";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui";
import {
    TrendingUp, Save, Lock, Calendar, ChevronLeft, ChevronRight,
    AlertCircle, CheckCircle2, FileEdit,
} from "lucide-react";
import { formatBDT, cn } from "@/lib/utils";
import { toast } from "sonner";

interface GridItem {
    sector_id: number;
    sector_name: string;
    estimated_profit: number;
    actual_profit: number;
    status: string;
    exists: boolean;
}

interface Props {
    month: string;
    monthLabel: string;
    grid: GridItem[];
    totals: { estimated: number; actual: number; variance: number };
    isFinalized: boolean;
    isLocked: boolean;
    canEdit: boolean;
}

export default function SectorProfitIndex({ month, monthLabel, grid, totals, isFinalized, isLocked, canEdit }: Props) {
    // Local state for the grid (allows live editing without round-trips)
    const [items, setItems] = useState<GridItem[]>(grid);
    const [currentMonth, setCurrentMonth] = useState(month);

    // Sync when server data changes (e.g. after navigation to a different month)
    useEffect(() => {
        setItems(grid);
    }, [grid]);

    // Live totals (computed from local state, not server)
    const liveTotals = useMemo(() => {
        const estimated = items.reduce((sum, i) => sum + (Number(i.estimated_profit) || 0), 0);
        const actual = items.reduce((sum, i) => sum + (Number(i.actual_profit) || 0), 0);
        return {
            estimated,
            actual,
            variance: estimated - actual, // Y2 = Z2 - X2
        };
    }, [items]);

    // Form for saving
    const { post, processing, setData } = useForm({
        profit_month: currentMonth,
        items: items.map(i => ({
            sector_id: i.sector_id,
            estimated_profit: i.estimated_profit,
            actual_profit: i.actual_profit,
        })),
        finalize: false,
    });

    const updateCell = (sectorId: number, field: "estimated_profit" | "actual_profit", value: string) => {
        const numValue = value === "" ? 0 : parseFloat(value) || 0;
        setItems(prev => prev.map(i =>
            i.sector_id === sectorId ? { ...i, [field]: numValue } : i,
        ));
    };

    const handleSave = (finalize: boolean) => {
        // Update form data before posting
        setData("profit_month", currentMonth);
        setData("items", items.map(i => ({
            sector_id: i.sector_id,
            estimated_profit: i.estimated_profit,
            actual_profit: i.actual_profit,
        })));
        setData("finalize", finalize);
        post(route("profit.sector.store"), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(
                    finalize
                        ? `Sector profits for ${monthLabel} finalized successfully`
                        : `Sector profits for ${monthLabel} saved as draft`,
                );
            },
            onError: () => toast.error("Failed to save sector profits"),
        });
    };

    const navigateMonth = (direction: "prev" | "next") => {
        const date = new Date(currentMonth);
        date.setMonth(date.getMonth() + (direction === "next" ? 1 : -1));
        const newMonth = date.toISOString().slice(0, 10);
        setCurrentMonth(newMonth);
        router.get(route("profit.sector.index"), { month: newMonth }, { preserveScroll: true });
    };

    const isReadOnly = isFinalized || isLocked || !canEdit;

    return (
        <AuthenticatedLayout title="Sector Profit Entry">
            <Head title={`Sector Profit — ${monthLabel}`} />

            <div className="space-y-6">
                {/* Header with month switcher */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <div className="size-10 rounded-xl bg-primary-soft flex items-center justify-center">
                            <TrendingUp className="size-5 text-primary" />
                        </div>
                        <div>
                            <h1 className="font-display text-2xl font-bold tracking-tight">Sector Profit Entry</h1>
                            <p className="text-sm text-muted">Enter estimated and actual profit per sector</p>
                        </div>
                    </div>

                    {/* Month navigator */}
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

                {/* Status banner */}
                {isLocked && (
                    <div className="flex items-center gap-3 p-4 rounded-lg border border-danger/30 bg-danger-soft/30">
                        <Lock className="size-5 text-danger shrink-0" />
                        <div>
                            <p className="font-medium text-danger">This month is locked</p>
                            <p className="text-sm text-muted">
                                All financial data is frozen. A superadmin must unlock it before any edits can be made.
                            </p>
                        </div>
                    </div>
                )}
                {isFinalized && !isLocked && (
                    <div className="flex items-center gap-3 p-4 rounded-lg border border-success/30 bg-success-soft/30">
                        <CheckCircle2 className="size-5 text-success shrink-0" />
                        <div>
                            <p className="font-medium text-success">This month is finalized</p>
                            <p className="text-sm text-muted">
                                All sector profits are locked. Use the investor profit page to run the reconciliation.
                            </p>
                        </div>
                    </div>
                )}
                {!canEdit && !isFinalized && (
                    <div className="flex items-center gap-3 p-4 rounded-lg border border-warning/30 bg-warning-soft/30">
                        <AlertCircle className="size-5 text-warning shrink-0" />
                        <div>
                            <p className="font-medium text-warning">Read-only mode</p>
                            <p className="text-sm text-muted">You need edit permission to modify sector profits.</p>
                        </div>
                    </div>
                )}

                {/* Excel-like grid */}
                <Card>
                    <CardHeader>
                        <CardTitle>Sector Profit Grid</CardTitle>
                        <CardDescription>
                            Enter estimated profit (beginning of month) and actual profit (end of month).
                            Variance = Estimated − Actual. Positive variance means investors were over-paid.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="p-0">
                        <div className="rounded-lg border border-border overflow-hidden">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-[40%]">Sector</TableHead>
                                        <TableHead className="text-right">Estimated (Z)</TableHead>
                                        <TableHead className="text-right">Actual (X)</TableHead>
                                        <TableHead className="text-right">Variance (Y)</TableHead>
                                        <TableHead className="text-center">Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {items.map((item) => {
                                        const variance = (Number(item.estimated_profit) || 0) - (Number(item.actual_profit) || 0);
                                        return (
                                            <TableRow key={item.sector_id}>
                                                <TableCell className="font-medium">{item.sector_name}</TableCell>
                                                <TableCell className="p-2">
                                                    <Input
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        value={item.estimated_profit || ""}
                                                        onChange={(e) => updateCell(item.sector_id, "estimated_profit", e.target.value)}
                                                        disabled={isReadOnly}
                                                        placeholder="0"
                                                        className="text-right font-num border-0 focus-visible:ring-2 focus-visible:ring-primary h-9"
                                                    />
                                                </TableCell>
                                                <TableCell className="p-2">
                                                    <Input
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        value={item.actual_profit || ""}
                                                        onChange={(e) => updateCell(item.sector_id, "actual_profit", e.target.value)}
                                                        disabled={isReadOnly}
                                                        placeholder="0"
                                                        className="text-right font-num border-0 focus-visible:ring-2 focus-visible:ring-primary h-9"
                                                    />
                                                </TableCell>
                                                <TableCell className={cn(
                                                    "text-right font-num font-medium",
                                                    variance > 0 ? "text-danger" : variance < 0 ? "text-success" : "text-muted",
                                                )}>
                                                    {variance > 0 ? "+" : ""}{formatBDT(variance, false)}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {item.exists ? (
                                                        <Badge variant={item.status === "finalized" ? "success" : "warning"}>
                                                            {item.status}
                                                        </Badge>
                                                    ) : (
                                                        <Badge variant="outline">new</Badge>
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })}
                                </TableBody>
                            </Table>
                        </div>

                        {/* Sticky totals row */}
                        <div className="grid grid-cols-5 gap-0 border-t-2 border-primary/30 bg-primary-soft/20">
                            <div className="p-4 font-display font-bold">TOTALS</div>
                            <div className="p-4 text-right font-num font-bold text-lg text-primary">
                                {formatBDT(liveTotals.estimated)}
                            </div>
                            <div className="p-4 text-right font-num font-bold text-lg text-success">
                                {formatBDT(liveTotals.actual)}
                            </div>
                            <div className={cn(
                                "p-4 text-right font-num font-bold text-lg",
                                liveTotals.variance > 0 ? "text-danger" : liveTotals.variance < 0 ? "text-success" : "text-muted",
                            )}>
                                {liveTotals.variance > 0 ? "+" : ""}{formatBDT(liveTotals.variance, false)}
                            </div>
                            <div className="p-4 text-center">
                                <span className="text-xs text-muted">
                                    Z2 / X2 / Y2
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Excel cell reference legend */}
                <div className="flex items-center gap-4 text-xs text-muted">
                    <span className="flex items-center gap-1">
                        <Badge variant="outline" className="font-num">Z</Badge> Estimated Profit
                    </span>
                    <span className="flex items-center gap-1">
                        <Badge variant="outline" className="font-num">X</Badge> Actual Profit
                    </span>
                    <span className="flex items-center gap-1">
                        <Badge variant="outline" className="font-num">Y</Badge> Variance (Z−X)
                    </span>
                    <span className="flex items-center gap-1">
                        <Badge variant="outline" className="font-num">Z2/X2/Y2</Badge> Monthly Totals
                    </span>
                </div>

                {/* Action buttons */}
                {!isReadOnly && (
                    <div className="flex items-center justify-end gap-3 pb-6">
                        <Button
                            variant="outline"
                            onClick={() => handleSave(false)}
                            disabled={processing}
                        >
                            <FileEdit className="size-4" />
                            Save as Draft
                        </Button>
                        <Button
                            onClick={() => handleSave(true)}
                            disabled={processing}
                        >
                            {processing ? (
                                <>
                                    <span className="size-4 rounded-full border-2 border-white/30 border-t-white animate-spin" />
                                    Saving…
                                </>
                            ) : (
                                <>
                                    <Lock className="size-4" />
                                    Finalize Month
                                </>
                            )}
                        </Button>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
