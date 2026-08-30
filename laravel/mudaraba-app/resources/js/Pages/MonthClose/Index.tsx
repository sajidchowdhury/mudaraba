import { Head, Link, router, useForm } from "@inertiajs/react";
import { route } from "ziggy-js";
import { AuthenticatedLayout } from "@/Components/layout";
import {
    Card, CardContent, CardDescription, CardHeader, CardTitle,
    Button, Badge,
} from "@/Components/ui";
import {
    Lock, Unlock, CheckCircle2, XCircle, Calendar, ChevronLeft, ChevronRight,
    ShieldCheck, AlertTriangle, FileCheck, TrendingUp, ReceiptText,
} from "lucide-react";
import { cn, formatBDT, formatPercent } from "@/lib/utils";
import { toast } from "sonner";

interface ChecklistItem {
    id: string;
    label: string;
    done: boolean;
    detail: string;
    required: boolean;
}

interface Summary {
    total_estimated: number;
    total_actual: number;
    my_profit: number;
    my_profit_ratio: number;
    active_investors: number;
}

interface Props {
    month: string;
    monthLabel: string;
    status: string;
    checklist: ChecklistItem[];
    allDone: boolean;
    summary: Summary | null;
    canLock: boolean;
    lockedAt: string | null;
    lockedBy: string | null;
}

export default function MonthCloseIndex({ month, monthLabel, status, checklist, allDone, summary, canLock, lockedAt, lockedBy }: Props) {
    const { post, processing } = useForm({ month });

    const handleLock = () => {
        if (!confirm(`Lock ${monthLabel}? No further edits will be permitted without admin override.`)) return;
        post(route("month-close.lock"), {
            preserveScroll: true,
            onSuccess: () => toast.success(`${monthLabel} locked successfully`),
            onError: () => toast.error("Failed to lock month"),
        });
    };

    const handleUnlock = () => {
        if (!confirm(`Unlock ${monthLabel}? This will allow edits to sector profits and recalculation.`)) return;
        post(route("month-close.unlock"), {
            preserveScroll: true,
            onSuccess: () => toast.success(`${monthLabel} unlocked — edits now permitted`),
            onError: () => toast.error("Failed to unlock month"),
        });
    };

    const navigateMonth = (direction: "prev" | "next") => {
        const date = new Date(month);
        date.setMonth(date.getMonth() + (direction === "next" ? 1 : -1));
        const newMonth = date.toISOString().slice(0, 10);
        router.get(route("month-close.index"), { month: newMonth }, { preserveScroll: true });
    };

    const statusBadge = () => {
        if (status === "locked") return <Badge variant="danger"><Lock className="size-3" /> Locked</Badge>;
        if (status === "finalized") return <Badge variant="success"><CheckCircle2 className="size-3" /> Finalized</Badge>;
        return <Badge variant="warning"><AlertTriangle className="size-3" /> Open</Badge>;
    };

    return (
        <AuthenticatedLayout title="Month Closing">
            <Head title={`Month Close — ${monthLabel}`} />

            <div className="space-y-6 max-w-4xl mx-auto">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <div className="size-10 rounded-xl bg-primary-soft flex items-center justify-center">
                            <FileCheck className="size-5 text-primary" />
                        </div>
                        <div>
                            <h1 className="font-display text-2xl font-bold tracking-tight">Month Closing</h1>
                            <p className="text-sm text-muted">Verify checklist + lock the month</p>
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

                {/* Status banner */}
                <Card>
                    <CardContent className="p-6">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-4">
                                <div className={cn(
                                    "size-12 rounded-xl flex items-center justify-center",
                                    status === "locked" ? "bg-danger-soft" : status === "finalized" ? "bg-success-soft" : "bg-warning-soft",
                                )}>
                                    {status === "locked" ? <Lock className="size-6 text-danger" /> :
                                     status === "finalized" ? <CheckCircle2 className="size-6 text-success" /> :
                                     <AlertTriangle className="size-6 text-warning" />}
                                </div>
                                <div>
                                    <p className="font-display text-lg font-semibold">Month Status: {statusBadge()}</p>
                                    {status === "locked" && lockedAt && (
                                        <p className="text-sm text-muted mt-1">
                                            Locked on {lockedAt} by {lockedBy ?? "—"}
                                        </p>
                                    )}
                                    {status === "finalized" && (
                                        <p className="text-sm text-muted mt-1">
                                            Calculation complete — ready to lock if checklist passes
                                        </p>
                                    )}
                                    {status === "open" && (
                                        <p className="text-sm text-muted mt-1">
                                            No calculation has been run for this month yet
                                        </p>
                                    )}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Summary (if calculated) */}
                {summary && (
                    <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <Card>
                            <CardContent className="p-4">
                                <div className="size-9 rounded-lg bg-primary-soft flex items-center justify-center">
                                    <TrendingUp className="size-4 text-primary" />
                                </div>
                                <p className="text-xs text-muted mt-3">Estimated (Z2)</p>
                                <p className="font-num text-lg font-semibold mt-1">{formatBDT(summary.total_estimated)}</p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent className="p-4">
                                <div className="size-9 rounded-lg bg-success-soft flex items-center justify-center">
                                    <CheckCircle2 className="size-4 text-success" />
                                </div>
                                <p className="text-xs text-muted mt-3">Actual (X2)</p>
                                <p className="font-num text-lg font-semibold mt-1">{formatBDT(summary.total_actual)}</p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent className="p-4">
                                <div className="size-9 rounded-lg bg-accent-soft flex items-center justify-center">
                                    <ReceiptText className="size-4 text-accent" />
                                </div>
                                <p className="text-xs text-muted mt-3">M/Y Profit (AG184)</p>
                                <p className="font-num text-lg font-semibold mt-1">{formatBDT(summary.my_profit)}</p>
                                <p className="text-[10px] text-muted mt-0.5">{formatPercent(summary.my_profit_ratio)} ratio</p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent className="p-4">
                                <div className="size-9 rounded-lg bg-info-soft flex items-center justify-center">
                                    <ShieldCheck className="size-4 text-info" />
                                </div>
                                <p className="text-xs text-muted mt-3">Investors</p>
                                <p className="font-num text-lg font-semibold mt-1">{summary.active_investors}</p>
                            </CardContent>
                        </Card>
                    </div>
                )}

                {/* Checklist */}
                <Card>
                    <CardHeader>
                        <CardTitle>Month-End Checklist</CardTitle>
                        <CardDescription>
                            All required items must be complete before locking the month.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {checklist.map((item) => (
                            <div
                                key={item.id}
                                className={cn(
                                    "flex items-center gap-3 p-4 rounded-lg border transition-colors",
                                    item.done
                                        ? "border-success/30 bg-success-soft/20"
                                        : "border-warning/30 bg-warning-soft/20",
                                )}
                            >
                                <div className="shrink-0">
                                    {item.done ? (
                                        <CheckCircle2 className="size-5 text-success" />
                                    ) : (
                                        <XCircle className="size-5 text-warning" />
                                    )}
                                </div>
                                <div className="flex-1 min-w-0">
                                    <p className="text-sm font-medium">{item.label}</p>
                                    <p className="text-xs text-muted mt-0.5">{item.detail}</p>
                                </div>
                                {item.required && (
                                    <Badge variant={item.done ? "success" : "warning"} className="text-[10px]">
                                        {item.done ? "Done" : "Required"}
                                    </Badge>
                                )}
                            </div>
                        ))}

                        {/* All done indicator */}
                        <div className={cn(
                            "flex items-center gap-3 p-4 rounded-lg border-2 mt-4",
                            allDone ? "border-success bg-success-soft/30" : "border-muted border-dashed bg-surface-2/30",
                        )}>
                            {allDone ? (
                                <>
                                    <CheckCircle2 className="size-6 text-success" />
                                    <p className="font-medium text-success">All checklist items are complete — ready to lock</p>
                                </>
                            ) : (
                                <>
                                    <AlertTriangle className="size-6 text-muted" />
                                    <p className="font-medium text-muted">Complete all required items before locking</p>
                                </>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Lock/Unlock actions */}
                <Card>
                    <CardContent className="p-6">
                        {status !== "locked" ? (
                            <div className="flex flex-col items-center gap-4">
                                <Lock className="size-12 text-muted" />
                                <div className="text-center">
                                    <h3 className="font-display text-lg font-semibold">Lock {monthLabel}</h3>
                                    <p className="text-sm text-muted mt-1 max-w-md">
                                        Locking prevents all edits to sector profits, investor details, and
                                        due ledgers for this month. Only a superadmin can unlock.
                                    </p>
                                </div>
                                <Button
                                    onClick={handleLock}
                                    disabled={!allDone || !canLock || processing || status === "open"}
                                    size="lg"
                                    variant="danger"
                                >
                                    <Lock className="size-4" />
                                    {processing ? "Locking…" : `Lock ${monthLabel}`}
                                </Button>
                                {!canLock && (
                                    <p className="text-xs text-muted">Only superadmins can lock months.</p>
                                )}
                                {canLock && !allDone && status !== "open" && (
                                    <p className="text-xs text-warning">Complete all checklist items first.</p>
                                )}
                                {status === "open" && (
                                    <p className="text-xs text-muted">Run the calculation first by finalizing sector profits.</p>
                                )}
                            </div>
                        ) : (
                            <div className="flex flex-col items-center gap-4">
                                <ShieldCheck className="size-12 text-danger" />
                                <div className="text-center">
                                    <h3 className="font-display text-lg font-semibold text-danger">Month is Locked</h3>
                                    <p className="text-sm text-muted mt-1 max-w-md">
                                        {monthLabel} is locked. All financial data is frozen.
                                        {canLock && " You can unlock it if needed (with audit trail)."}
                                    </p>
                                </div>
                                {canLock && (
                                    <Button
                                        onClick={handleUnlock}
                                        disabled={processing}
                                        size="lg"
                                        variant="outline"
                                    >
                                        <Unlock className="size-4" />
                                        {processing ? "Unlocking…" : `Unlock ${monthLabel}`}
                                    </Button>
                                )}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Quick links */}
                <div className="flex flex-wrap gap-3 justify-center pb-6">
                    <Link href={route("profit.sector.index", { month })}>
                        <Button variant="ghost" size="sm"><TrendingUp className="size-4" /> Sector Profit Entry</Button>
                    </Link>
                    <Link href={route("profit.investor.index", { month })}>
                        <Button variant="ghost" size="sm"><ReceiptText className="size-4" /> Investor Profit View</Button>
                    </Link>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
