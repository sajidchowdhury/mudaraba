import { Head, Link, router, useForm } from "@inertiajs/react";
import { useState, useEffect } from "react";
import { route } from "ziggy-js";
import { AuthenticatedLayout } from "@/Components/layout";
import {
    Card, CardContent, CardDescription, CardHeader, CardTitle,
    Button, Badge, Input, Label, Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from "@/Components/ui";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui";
import {
    Wallet, ArrowUpCircle, ArrowDownCircle, Trash2, Search, Calendar,
    AlertCircle, Info,
} from "lucide-react";
import { formatBDT, cn } from "@/lib/utils";
import { toast } from "sonner";

interface Transaction {
    id: number;
    investor_name: string;
    investor_id: number;
    amount: number;
    type: string;
    transaction_month: string;
    transaction_date: string;
    remarks: string | null;
    created_by: string;
    created_at: string;
}

interface PaginatedTransactions {
    data: Transaction[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
}

interface InvestorOption {
    id: number;
    name: string;
    reference: string | null;
}

interface Props {
    transactions: PaginatedTransactions;
    investors: InvestorOption[];
    filters: { investor_id?: string; type?: string };
    canBackdate: boolean;
}

export default function InvestmentsIndex({ transactions, investors, filters, canBackdate }: Props) {
    const [filterInvestor, setFilterInvestor] = useState(filters.investor_id ?? "all");
    const [filterType, setFilterType] = useState(filters.type ?? "all");

    // Form state
    const { data, setData, post, processing, errors, reset } = useForm({
        investor_id: "",
        amount: "",
        type: "add",
        transaction_month: new Date().toISOString().slice(0, 7) + "-01",
        transaction_date: new Date().toISOString().slice(0, 10),
        remarks: "",
    });

    // Live balance preview for selected investor
    const [previewBalance, setPreviewBalance] = useState<number | null>(null);
    const [loadingBalance, setLoadingBalance] = useState(false);

    useEffect(() => {
        if (!data.investor_id) {
            setPreviewBalance(null);
            return;
        }
        setLoadingBalance(true);
        fetch(route("investments.balance", data.investor_id))
            .then((r) => r.json())
            .then((d) => setPreviewBalance(d.balance))
            .catch(() => setPreviewBalance(null))
            .finally(() => setLoadingBalance(false));
    }, [data.investor_id]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route("investments.store"), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Transaction recorded successfully");
                reset("amount", "remarks");
            },
            onError: () => toast.error("Failed to record transaction"),
        });
    };

    const handleDelete = (tx: Transaction) => {
        if (!confirm(`Delete this transaction? This will rollback the investor's ledger.`)) return;
        router.delete(route("investments.destroy", tx.id), {
            onSuccess: () => toast.success("Transaction deleted, ledger rolled back"),
            onError: () => toast.error("Failed to delete transaction"),
        });
    };

    const applyFilters = () => {
        router.get(route("investments.index"), {
            investor_id: filterInvestor !== "all" ? filterInvestor : undefined,
            type: filterType !== "all" ? filterType : undefined,
        }, { preserveScroll: true, preserveState: true });
    };

    // Preview the resulting balance after this transaction
    const amount = parseFloat(data.amount) || 0;
    const resultingBalance = previewBalance !== null
        ? data.type === "add" ? previewBalance + amount : previewBalance - amount
        : null;

    return (
        <AuthenticatedLayout title="Investment Transactions">
            <Head title="Investment Transactions" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center gap-3">
                    <div className="size-10 rounded-xl bg-primary-soft flex items-center justify-center">
                        <Wallet className="size-5 text-primary" />
                    </div>
                    <div>
                        <h1 className="font-display text-2xl font-bold tracking-tight">Investment Transactions</h1>
                        <p className="text-sm text-muted">Record capital additions and withdrawals</p>
                    </div>
                </div>

                <div className="grid lg:grid-cols-3 gap-6">
                    {/* === LEFT: Transaction form === */}
                    <div className="lg:col-span-1">
                        <Card>
                            <CardHeader>
                                <CardTitle>New Transaction</CardTitle>
                                <CardDescription>Record an add or withdraw</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={handleSubmit} className="space-y-4">
                                    {/* Investor selector */}
                                    <div className="space-y-2">
                                        <Label htmlFor="investor_id">Investor *</Label>
                                        <Select value={data.investor_id} onValueChange={(v) => setData("investor_id", v)}>
                                            <SelectTrigger><SelectValue placeholder="Select investor…" /></SelectTrigger>
                                            <SelectContent>
                                                {investors.map((inv) => (
                                                    <SelectItem key={inv.id} value={String(inv.id)}>
                                                        {inv.name}{inv.reference ? ` (${inv.reference})` : ""}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.investor_id && <p className="text-xs text-danger">{errors.investor_id}</p>}
                                    </div>

                                    {/* Balance preview */}
                                    {data.investor_id && (
                                        <div className="p-3 rounded-lg border border-border bg-surface-2/50 space-y-1.5">
                                            <div className="flex items-center justify-between text-sm">
                                                <span className="text-muted flex items-center gap-1.5">
                                                    <Wallet className="size-3.5" /> Current Balance
                                                </span>
                                                <span className="font-num font-medium">
                                                    {loadingBalance ? "…" : formatBDT(previewBalance ?? 0)}
                                                </span>
                                            </div>
                                            {resultingBalance !== null && amount > 0 && (
                                                <div className="flex items-center justify-between text-sm pt-1.5 border-t border-border">
                                                    <span className="text-muted">After Transaction</span>
                                                    <span className={cn(
                                                        "font-num font-bold",
                                                        resultingBalance >= 0 ? "text-success" : "text-danger",
                                                    )}>
                                                        {formatBDT(resultingBalance)}
                                                    </span>
                                                </div>
                                            )}
                                        </div>
                                    )}

                                    {/* Type selector (segmented) */}
                                    <div className="space-y-2">
                                        <Label>Type *</Label>
                                        <div className="grid grid-cols-2 gap-2">
                                            <button
                                                type="button"
                                                onClick={() => setData("type", "add")}
                                                className={cn(
                                                    "flex items-center justify-center gap-2 p-3 rounded-lg border-2 text-sm font-medium transition-all",
                                                    data.type === "add"
                                                        ? "border-success bg-success-soft text-success"
                                                        : "border-border hover:border-success/40",
                                                )}
                                            >
                                                <ArrowUpCircle className="size-4" /> Add
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => setData("type", "withdraw")}
                                                className={cn(
                                                    "flex items-center justify-center gap-2 p-3 rounded-lg border-2 text-sm font-medium transition-all",
                                                    data.type === "withdraw"
                                                        ? "border-danger bg-danger-soft text-danger"
                                                        : "border-border hover:border-danger/40",
                                                )}
                                            >
                                                <ArrowDownCircle className="size-4" /> Withdraw
                                            </button>
                                        </div>
                                        {errors.type && <p className="text-xs text-danger">{errors.type}</p>}
                                    </div>

                                    {/* Amount */}
                                    <div className="space-y-2">
                                        <Label htmlFor="amount">Amount (BDT) *</Label>
                                        <Input
                                            id="amount"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={data.amount}
                                            onChange={(e) => setData("amount", e.target.value)}
                                            placeholder="0.00"
                                            className="font-num text-lg"
                                            aria-invalid={!!errors.amount}
                                        />
                                        {errors.amount && <p className="text-xs text-danger">{errors.amount}</p>}
                                    </div>

                                    {/* Transaction month + date */}
                                    <div className="grid grid-cols-2 gap-3">
                                        <div className="space-y-2">
                                            <Label htmlFor="transaction_month">Month *</Label>
                                            <Input
                                                id="transaction_month"
                                                type="date"
                                                value={data.transaction_month}
                                                onChange={(e) => setData("transaction_month", e.target.value)}
                                                className="font-num"
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="transaction_date">Date *</Label>
                                            <Input
                                                id="transaction_date"
                                                type="date"
                                                value={data.transaction_date}
                                                onChange={(e) => setData("transaction_date", e.target.value)}
                                                className="font-num"
                                            />
                                        </div>
                                    </div>
                                    {errors.transaction_date && <p className="text-xs text-danger">{errors.transaction_date}</p>}

                                    {/* Backdate warning */}
                                    {!canBackdate && (
                                        <div className="flex items-start gap-2 p-2 rounded-lg bg-warning-soft/50 border border-warning/20">
                                            <AlertCircle className="size-3.5 text-warning shrink-0 mt-0.5" />
                                            <p className="text-xs text-warning">
                                                Backdating beyond 7 days requires permission.
                                            </p>
                                        </div>
                                    )}

                                    {/* Remarks */}
                                    <div className="space-y-2">
                                        <Label htmlFor="remarks">Remarks</Label>
                                        <Input
                                            id="remarks"
                                            value={data.remarks}
                                            onChange={(e) => setData("remarks", e.target.value)}
                                            placeholder="Optional notes…"
                                        />
                                    </div>

                                    {/* Submit */}
                                    <Button type="submit" className="w-full" disabled={processing || !data.investor_id || !data.amount}>
                                        {processing ? (
                                            <>
                                                <span className="size-4 rounded-full border-2 border-white/30 border-t-white animate-spin" />
                                                Recording…
                                            </>
                                        ) : (
                                            <>
                                                {data.type === "add" ? <ArrowUpCircle className="size-4" /> : <ArrowDownCircle className="size-4" />}
                                                Record {data.type === "add" ? "Add" : "Withdraw"}
                                            </>
                                        )}
                                    </Button>
                                </form>
                            </CardContent>
                        </Card>
                    </div>

                    {/* === RIGHT: Transaction history === */}
                    <div className="lg:col-span-2 space-y-4">
                        {/* Filters */}
                        <Card>
                            <CardContent className="p-4">
                                <div className="flex flex-col sm:flex-row gap-3">
                                    <div className="flex-1 space-y-1.5">
                                        <Label>Filter by Investor</Label>
                                        <Select value={filterInvestor} onValueChange={setFilterInvestor}>
                                            <SelectTrigger><SelectValue placeholder="All investors" /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">All investors</SelectItem>
                                                {investors.map((inv) => (
                                                    <SelectItem key={inv.id} value={String(inv.id)}>{inv.name}</SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="w-full sm:w-48 space-y-1.5">
                                        <Label>Type</Label>
                                        <Select value={filterType} onValueChange={setFilterType}>
                                            <SelectTrigger><SelectValue placeholder="All types" /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">All types</SelectItem>
                                                <SelectItem value="add">Add only</SelectItem>
                                                <SelectItem value="withdraw">Withdraw only</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="flex items-end">
                                        <Button onClick={applyFilters} className="w-full sm:w-auto">
                                            <Search className="size-4" /> Filter
                                        </Button>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Table */}
                        <Card>
                            <CardContent className="p-0">
                                <div className="rounded-lg border border-border overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Date</TableHead>
                                                <TableHead>Investor</TableHead>
                                                <TableHead>Type</TableHead>
                                                <TableHead className="text-right">Amount</TableHead>
                                                <TableHead>Remarks</TableHead>
                                                <TableHead className="text-right">Action</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {transactions.data.length === 0 ? (
                                                <TableRow>
                                                    <TableCell colSpan={6} className="text-center py-12 text-muted">
                                                        No transactions found. Record your first transaction using the form.
                                                    </TableCell>
                                                </TableRow>
                                            ) : (
                                                transactions.data.map((tx) => (
                                                    <TableRow key={tx.id}>
                                                        <TableCell className="font-num">
                                                            <div>{tx.transaction_date}</div>
                                                            <div className="text-xs text-muted">{tx.transaction_month}</div>
                                                        </TableCell>
                                                        <TableCell>
                                                            <Link
                                                                href={route("investors.show", tx.investor_id)}
                                                                className="font-medium hover:text-primary transition-colors"
                                                            >
                                                                {tx.investor_name}
                                                            </Link>
                                                        </TableCell>
                                                        <TableCell>
                                                            {tx.type === "add" ? (
                                                                <Badge variant="success"><ArrowUpCircle className="size-3" /> Add</Badge>
                                                            ) : (
                                                                <Badge variant="danger"><ArrowDownCircle className="size-3" /> Withdraw</Badge>
                                                            )}
                                                        </TableCell>
                                                        <TableCell className={cn(
                                                            "text-right font-num font-medium",
                                                            tx.type === "add" ? "text-success" : "text-danger",
                                                        )}>
                                                            {tx.type === "add" ? "+" : "-"}{formatBDT(tx.amount, false)}
                                                        </TableCell>
                                                        <TableCell className="text-muted text-sm max-w-[200px] truncate">
                                                            {tx.remarks ?? "—"}
                                                        </TableCell>
                                                        <TableCell className="text-right">
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                aria-label="Delete"
                                                                onClick={() => handleDelete(tx)}
                                                            >
                                                                <Trash2 className="size-4 text-danger" />
                                                            </Button>
                                                        </TableCell>
                                                    </TableRow>
                                                ))
                                            )}
                                        </TableBody>
                                    </Table>
                                </div>

                                {/* Pagination */}
                                {transactions.last_page > 1 && (
                                    <div className="flex items-center justify-between px-4 py-3 border-t border-border">
                                        <p className="text-xs text-muted">
                                            Showing {transactions.from ?? 0}–{transactions.to ?? 0} of {transactions.total}
                                        </p>
                                        <div className="flex items-center gap-1">
                                            {transactions.links.map((link, i) => (
                                                <button
                                                    key={i}
                                                    onClick={() => link.url && router.get(link.url, {}, { preserveScroll: true, preserveState: true })}
                                                    disabled={!link.url}
                                                    className={cn(
                                                        "px-3 py-1.5 text-sm rounded-md border transition-colors",
                                                        link.active
                                                            ? "bg-primary text-primary-foreground border-primary"
                                                            : "border-border hover:bg-surface-2 disabled:opacity-40",
                                                    )}
                                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                                />
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
