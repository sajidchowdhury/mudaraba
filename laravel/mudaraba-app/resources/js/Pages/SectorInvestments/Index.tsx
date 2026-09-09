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
    Layers, ArrowUpCircle, ArrowDownCircle, Trash2, Search,
    AlertCircle,
} from "lucide-react";
import { formatBDT, cn } from "@/lib/utils";
import { toast } from "sonner";
import { PageTransition } from "@/Components/common";

interface Transaction {
    id: number;
    sector_name: string;
    sector_id: number;
    amount: number;
    type: string;
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

interface SectorOption {
    id: number;
    name: string;
}

interface Props {
    transactions: PaginatedTransactions;
    sectors: SectorOption[];
    filters: { sector_id?: string; type?: string };
}

export default function SectorInvestmentsIndex({ transactions, sectors, filters }: Props) {
    const [sectorFilter, setSectorFilter] = useState(filters.sector_id || "all");
    const [typeFilter, setTypeFilter] = useState(filters.type || "all");

    // ── Add/Withdraw form ──────────────────────────────────────────────
    const { data, setData, post, processing, reset } = useForm({
        sector_id: "",
        amount: "",
        type: "add",
        transaction_date: new Date().toISOString().slice(0, 10),
        remarks: "",
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route("sector-investments.store"), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Sector investment recorded successfully");
                reset("amount", "remarks");
            },
            onError: () => toast.error("Failed to record sector investment"),
        });
    };

    const handleDelete = (id: number) => {
        if (!confirm("Delete this sector investment? The ledger will be rolled back.")) return;
        router.delete(route("sector-investments.destroy", id), {
            preserveScroll: true,
            onSuccess: () => toast.success("Investment deleted and ledger rolled back"),
            onError: () => toast.error("Failed to delete investment"),
        });
    };

    // ── Filter ─────────────────────────────────────────────────────────
    const applyFilter = () => {
        const params: Record<string, string> = {};
        if (sectorFilter !== "all") params.sector_id = sectorFilter;
        if (typeFilter !== "all") params.type = typeFilter;
        router.get(route("sector-investments.index"), params, { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout title="Sector Investments">
            <Head title="Sector Investments" />

            <PageTransition><div className="space-y-6">
                {/* Header */}
                <div className="flex items-center gap-3">
                    <div className="size-10 rounded-xl bg-primary-soft flex items-center justify-center">
                        <Layers className="size-5 text-primary" />
                    </div>
                    <div>
                        <h1 className="font-display text-2xl font-bold tracking-tight">Sector Investments</h1>
                        <p className="text-sm text-muted">Allocate investor funds to sectors or withdraw capital back to the pool</p>
                    </div>
                </div>

                {/* Add/Withdraw form */}
                <Card>
                    <CardHeader>
                        <CardTitle>New Allocation / Withdrawal</CardTitle>
                        <CardDescription>Assign funds from the M/Y pool to a sector, or withdraw capital back.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="grid sm:grid-cols-2 lg:grid-cols-5 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="sector_id">Sector</Label>
                                <Select
                                    value={data.sector_id}
                                    onValueChange={(v) => setData("sector_id", v)}
                                >
                                    <SelectTrigger id="sector_id">
                                        <SelectValue placeholder="Select sector" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {sectors.map((s) => (
                                            <SelectItem key={s.id} value={String(s.id)}>{s.name}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="type">Action</Label>
                                <Select
                                    value={data.type}
                                    onValueChange={(v) => setData("type", v)}
                                >
                                    <SelectTrigger id="type">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="add">Add (Allocate)</SelectItem>
                                        <SelectItem value="withdraw">Withdraw</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="amount">Amount (BDT)</Label>
                                <Input
                                    id="amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="0.00"
                                    value={data.amount}
                                    onChange={(e) => setData("amount", e.target.value)}
                                    className="font-num text-right"
                                    required
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="transaction_date">Date</Label>
                                <Input
                                    id="transaction_date"
                                    type="date"
                                    value={data.transaction_date}
                                    onChange={(e) => setData("transaction_date", e.target.value)}
                                    required
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="remarks">Remarks</Label>
                                <Input
                                    id="remarks"
                                    type="text"
                                    placeholder="e.g. January allocation"
                                    value={data.remarks}
                                    onChange={(e) => setData("remarks", e.target.value)}
                                />
                            </div>
                            <div className="sm:col-span-2 lg:col-span-5 flex justify-end">
                                <Button type="submit" disabled={processing || !data.sector_id || !data.amount}>
                                    {data.type === "add" ? (
                                        <><ArrowUpCircle className="size-4" /> Allocate to Sector</>
                                    ) : (
                                        <><ArrowDownCircle className="size-4" /> Withdraw from Sector</>
                                    )}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* Filters */}
                <Card>
                    <CardContent className="p-4">
                        <div className="flex flex-wrap items-center gap-4">
                            <div className="flex items-center gap-3">
                                <Search className="size-4 text-muted" />
                                <span className="text-sm font-medium">Filter:</span>
                            </div>
                            <Select value={sectorFilter} onValueChange={setSectorFilter}>
                                <SelectTrigger className="w-48"><SelectValue placeholder="All sectors" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All sectors</SelectItem>
                                    {sectors.map((s) => (
                                        <SelectItem key={s.id} value={String(s.id)}>{s.name}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select value={typeFilter} onValueChange={setTypeFilter}>
                                <SelectTrigger className="w-36"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All types</SelectItem>
                                    <SelectItem value="add">Add only</SelectItem>
                                    <SelectItem value="withdraw">Withdraw only</SelectItem>
                                </SelectContent>
                            </Select>
                            <Button variant="outline" size="sm" onClick={applyFilter}>Apply</Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Transactions table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Transaction History</CardTitle>
                        <CardDescription>
                            {transactions.total} total transactions
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Date</TableHead>
                                        <TableHead>Sector</TableHead>
                                        <TableHead>Type</TableHead>
                                        <TableHead className="text-right">Amount</TableHead>
                                        <TableHead>Remarks</TableHead>
                                        <TableHead>By</TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {transactions.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={7} className="text-center py-8 text-muted">
                                                <AlertCircle className="size-8 mx-auto mb-2" />
                                                No sector investments yet. Use the form above to allocate funds.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        transactions.data.map((t) => (
                                            <TableRow key={t.id}>
                                                <TableCell className="font-num">{t.transaction_date}</TableCell>
                                                <TableCell className="font-medium">{t.sector_name}</TableCell>
                                                <TableCell>
                                                    {t.type === "add" ? (
                                                        <Badge variant="success"><ArrowUpCircle className="size-3" /> Add</Badge>
                                                    ) : (
                                                        <Badge variant="danger"><ArrowDownCircle className="size-3" /> Withdraw</Badge>
                                                    )}
                                                </TableCell>
                                                <TableCell className={cn("text-right font-num font-medium", t.type === "add" ? "text-success" : "text-danger")}>
                                                    {t.type === "add" ? "+" : "-"}{formatBDT(t.amount, false)}
                                                </TableCell>
                                                <TableCell className="text-muted">{t.remarks ?? "—"}</TableCell>
                                                <TableCell className="text-muted">{t.created_by}</TableCell>
                                                <TableCell className="text-right">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => handleDelete(t.id)}
                                                        className="text-danger hover:text-danger"
                                                    >
                                                        <Trash2 className="size-4" />
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
                                <p className="text-sm text-muted">
                                    Showing {transactions.from}–{transactions.to} of {transactions.total}
                                </p>
                                <div className="flex items-center gap-1">
                                    {transactions.links.map((link, i) => (
                                        <Link
                                            key={i}
                                            href={link.url || "#"}
                                            className={cn(
                                                "px-3 py-1.5 text-sm rounded-md border transition-colors",
                                                link.active
                                                    ? "bg-primary text-primary-foreground border-primary"
                                                    : "border-border hover:bg-surface-2",
                                                !link.url && "opacity-50 pointer-events-none"
                                            )}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div></PageTransition>
        </AuthenticatedLayout>
    );
}
