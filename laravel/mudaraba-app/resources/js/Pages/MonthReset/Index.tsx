import { Head, router } from "@inertiajs/react";
import { useState } from "react";
import { route } from "ziggy-js";
import { AuthenticatedLayout } from "@/Components/layout";
import {
    Card, CardContent, CardDescription, CardHeader, CardTitle,
    Button, Badge, Input,
} from "@/Components/ui";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui";
import {
    Trash2, AlertCircle, Lock, CheckCircle2, TrendingUp, FileSpreadsheet,
} from "lucide-react";
import { formatBDT, formatPercent, cn } from "@/lib/utils";
import { toast } from "sonner";
import { PageTransition } from "@/Components/common";

interface MonthData {
    profit_month: string;
    month_label: string;
    status: string;
    total_estimated: number;
    total_actual: number;
    my_profit: number;
    my_profit_ratio: number;
    investor_count: number;
    sector_count: number;
    has_retained_earnings: boolean;
    is_locked: boolean;
    can_delete: boolean;
}

interface Props {
    months: MonthData[];
    flash?: { success?: string; error?: string };
}

export default function MonthResetIndex({ months, flash }: Props) {
    const [confirmTexts, setConfirmTexts] = useState<Record<string, string>>({});
    const [deleting, setDeleting] = useState<string | null>(null);

    const handleDelete = (month: MonthData) => {
        const confirmText = confirmTexts[month.profit_month] || "";
        const expected = `DELETE ${month.month_label}`;

        if (confirmText !== expected) {
            toast.error(`Type "${expected}" to confirm deletion`);
            return;
        }

        if (month.is_locked) {
            toast.error("Month is locked — unlock it first via the Month Close page");
            return;
        }

        setDeleting(month.profit_month);

        router.delete(route("month-reset.destroy"), {
            data: {
                profit_month: month.profit_month,
                confirm_text: confirmText,
            },
            preserveScroll: true,
            onSuccess: () => {
                setDeleting(null);
                setConfirmTexts(prev => ({ ...prev, [month.profit_month]: "" }));
                toast.success(`All calculation data for ${month.month_label} deleted successfully`);
            },
            onError: (errors) => {
                setDeleting(null);
                const msg = errors.profit_month || errors.confirm_text || "Delete failed";
                toast.error(msg);
            },
        });
    };

    return (
        <AuthenticatedLayout title="Reset Month">
            <Head title="Reset Month" />

            <PageTransition><div className="space-y-6">
                {/* Header */}
                <div className="flex items-center gap-3">
                    <div className="size-10 rounded-xl bg-danger-soft flex items-center justify-center">
                        <Trash2 className="size-5 text-danger" />
                    </div>
                    <div>
                        <h1 className="font-display text-2xl font-bold tracking-tight">Reset Month Calculation</h1>
                        <p className="text-sm text-muted">
                            Delete a month's entire calculation (profit details, summary, retained earnings) and roll back all ledger entries
                        </p>
                    </div>
                </div>

                {/* Warning banner */}
                <Card className="border-2 border-danger/30">
                    <CardContent className="p-4">
                        <div className="flex items-start gap-3">
                            <AlertCircle className="size-5 text-danger shrink-0 mt-0.5" />
                            <div className="space-y-1">
                                <p className="text-sm font-medium text-danger">Destructive operation — read carefully</p>
                                <p className="text-xs text-muted">
                                    Deleting a month's calculation will: (1) reverse ALL ledger changes for that month,
                                    (2) delete investor profit details, (3) delete the monthly summary, (4) delete retained earnings,
                                    (5) reset sector profits back to "draft" status. The investor investments and sector allocations
                                    are <strong>NOT</strong> deleted — only the calculation results are removed.
                                    You can re-run the calculation by finalizing the month again.
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Flash messages */}
                {flash?.success && (
                    <Card className="border-2 border-success/30">
                        <CardContent className="p-4">
                            <div className="flex items-center gap-2 text-success">
                                <CheckCircle2 className="size-5" />
                                <span className="text-sm font-medium">{flash.success}</span>
                            </div>
                        </CardContent>
                    </Card>
                )}
                {flash?.error && (
                    <Card className="border-2 border-danger/30">
                        <CardContent className="p-4">
                            <div className="flex items-center gap-2 text-danger">
                                <AlertCircle className="size-5" />
                                <span className="text-sm font-medium">{flash.error}</span>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Months table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Months with Calculation Data</CardTitle>
                        <CardDescription>
                            {months.length} month(s) have calculation data. Locked months must be unlocked first.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="p-0">
                        {months.length === 0 ? (
                            <div className="text-center py-12">
                                <TrendingUp className="size-12 text-muted mx-auto mb-4" />
                                <p className="text-muted">No calculation data found. No months to reset.</p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Month</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead className="text-right">Z2 (Estimated)</TableHead>
                                            <TableHead className="text-right">X2 (Actual)</TableHead>
                                            <TableHead className="text-right">M/Y Profit</TableHead>
                                            <TableHead className="text-center">Investors</TableHead>
                                            <TableHead className="text-center">Sectors</TableHead>
                                            <TableHead className="text-center">Retained Earnings</TableHead>
                                            <TableHead className="text-center">Action</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {months.map((m) => (
                                            <TableRow key={m.profit_month}>
                                                <TableCell className="font-medium">{m.month_label}</TableCell>
                                                <TableCell>
                                                    {m.is_locked ? (
                                                        <Badge variant="danger"><Lock className="size-3" /> Locked</Badge>
                                                    ) : m.status === "finalized" ? (
                                                        <Badge variant="success">Finalized</Badge>
                                                    ) : (
                                                        <Badge variant="warning">{m.status}</Badge>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right font-num">{formatBDT(m.total_estimated, false)}</TableCell>
                                                <TableCell className="text-right font-num">{formatBDT(m.total_actual, false)}</TableCell>
                                                <TableCell className="text-right font-num font-medium">
                                                    {formatBDT(m.my_profit, false)}
                                                    <div className="text-[10px] text-muted">{formatPercent(m.my_profit_ratio)}</div>
                                                </TableCell>
                                                <TableCell className="text-center">{m.investor_count}</TableCell>
                                                <TableCell className="text-center">{m.sector_count}</TableCell>
                                                <TableCell className="text-center">
                                                    {m.has_retained_earnings ? <CheckCircle2 className="size-4 text-success inline" /> : "—"}
                                                </TableCell>
                                                <TableCell>
                                                    {m.can_delete ? (
                                                        <div className="space-y-2">
                                                            <Input
                                                                type="text"
                                                                placeholder={`Type: DELETE ${m.month_label}`}
                                                                value={confirmTexts[m.profit_month] || ""}
                                                                onChange={(e) => setConfirmTexts(prev => ({ ...prev, [m.profit_month]: e.target.value }))}
                                                                className="text-xs w-48"
                                                            />
                                                            <Button
                                                                variant="danger"
                                                                size="sm"
                                                                disabled={deleting === m.profit_month || (confirmTexts[m.profit_month] || "") !== `DELETE ${m.month_label}`}
                                                                onClick={() => handleDelete(m)}
                                                            >
                                                                {deleting === m.profit_month ? (
                                                                    <span className="size-3 rounded-full border-2 border-white/30 border-t-white animate-spin" />
                                                                ) : (
                                                                    <Trash2 className="size-3.5" />
                                                                )}
                                                                Delete
                                                            </Button>
                                                        </div>
                                                    ) : (
                                                        <Badge variant="danger">
                                                            <Lock className="size-3" /> Unlock first
                                                        </Badge>
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* What gets deleted / what stays */}
                <div className="grid sm:grid-cols-2 gap-4">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-danger">
                                <Trash2 className="size-4" />
                                What gets DELETED
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ul className="space-y-2 text-sm">
                                <li>• Investor monthly profit details (all 150+ rows)</li>
                                <li>• Monthly profit summary (Z2, X2, AG184, etc.)</li>
                                <li>• Retained earnings + distributions for the month</li>
                                <li>• Sector profit due ledger entries (Y2 variance)</li>
                                <li>• Investor profit due ledger entries (AH)</li>
                                <li>• M/Y due ledger entry (AG184)</li>
                                <li>• Sector profits reset to "draft" status</li>
                            </ul>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-success">
                                <CheckCircle2 className="size-4" />
                                What STAYS (NOT deleted)
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ul className="space-y-2 text-sm">
                                <li>• Investor investments (add/withdraw transactions)</li>
                                <li>• Sector investments (allocations)</li>
                                <li>• Investor balances (due ledgers for capital)</li>
                                <li>• Sector balances (due ledgers for capital)</li>
                                <li>• Director transactions (M/Y withdrawals)</li>
                                <li>• Profit adjustments (Fund A/B/Direct)</li>
                                <li>• Audit logs (the deletion itself is logged)</li>
                                <li>• Sector profit estimated/actual values (kept as draft)</li>
                            </ul>
                        </CardContent>
                    </Card>
                </div>
            </div></PageTransition>
        </AuthenticatedLayout>
    );
}
