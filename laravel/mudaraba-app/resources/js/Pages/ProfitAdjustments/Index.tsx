import { Head, Link, router, useForm } from "@inertiajs/react";
import { useState } from "react";
import { route } from "ziggy-js";
import { AuthenticatedLayout } from "@/Components/layout";
import {
    Card, CardContent, CardDescription, CardHeader, CardTitle,
    Button, Badge, Input, Label, Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from "@/Components/ui";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/Components/ui";
import {
    Settings2, Save, Trash2, ArrowRightLeft, Wallet,
    Users, ShoppingBag, Info,
} from "lucide-react";
import { formatBDT, cn } from "@/lib/utils";
import { toast } from "sonner";
import { PageTransition } from "@/Components/common";

interface Adjustment {
    id: number;
    type: string;
    type_label: string;
    target_type: string;
    target_name: string;
    amount: number;
    transaction_date: string;
    profit_month: string;
    remarks: string | null;
    created_by: string;
    created_at: string;
}

interface PaginatedAdjustments {
    data: Adjustment[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
}

interface InvestorOption { id: number; name: string; reference: string | null; deed_ratio: string; }
interface SectorOption { id: number; name: string; }

interface Props {
    adjustments: PaginatedAdjustments;
    investors: InvestorOption[];
    sectors: SectorOption[];
    fundBalances: { fund_a: number; fund_b: number };
    filters: { type?: string };
    canEdit: boolean;
}

export default function ProfitAdjustmentsIndex({ adjustments, investors, sectors, fundBalances, filters, canEdit }: Props) {
    const [tab, setTab] = useState(filters.type === "direct" ? "direct" : filters.type === "fund_b" ? "fund_b" : "fund_a");
    const [filterType, setFilterType] = useState(filters.type ?? "all");

    // Batch form state
    const batchForm = useForm({
        type: "fund_a",
        transaction_date: new Date().toISOString().slice(0, 10),
        profit_month: new Date().toISOString().slice(0, 7) + "-01",
        remarks: "",
        investor_items: [] as { investor_id: number; amount: string }[],
        sector_items: [] as { sector_id: number; amount: string }[],
    });

    // Direct form state
    const directForm = useForm({
        investor_id: "",
        amount: "",
        transaction_date: new Date().toISOString().slice(0, 10),
        profit_month: new Date().toISOString().slice(0, 7) + "-01",
        remarks: "",
    });

    // Batch investor/sector amounts
    const [investorAmounts, setInvestorAmounts] = useState<Record<number, string>>({});
    const [sectorAmounts, setSectorAmounts] = useState<Record<number, string>>({});

    const handleBatchSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const invItems = Object.entries(investorAmounts)
            .filter(([, v]) => parseFloat(v) > 0)
            .map(([id, amount]) => ({ investor_id: parseInt(id), amount }));
        const secItems = Object.entries(sectorAmounts)
            .filter(([, v]) => parseFloat(v) > 0)
            .map(([id, amount]) => ({ sector_id: parseInt(id), amount }));

        if (invItems.length === 0 && secItems.length === 0) {
            toast.error("Enter at least one adjustment amount");
            return;
        }

        batchForm.setData("investor_items", invItems);
        batchForm.setData("sector_items", secItems);
        batchForm.post(route("adjustments.store-batch"), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Batch adjustment saved");
                setInvestorAmounts({});
                setSectorAmounts({});
            },
            onError: () => toast.error("Failed to save batch adjustment"),
        });
    };

    const handleDirectSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        directForm.post(route("adjustments.store-direct"), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Direct adjustment saved");
                directForm.reset("amount", "remarks");
            },
            onError: () => toast.error("Failed to save direct adjustment"),
        });
    };

    const handleDelete = (adj: Adjustment) => {
        if (!confirm(`Delete this ${adj.type_label} adjustment of ৳${adj.amount} for ${adj.target_name}? Ledger will be rolled back.`)) return;
        router.delete(route("adjustments.destroy", adj.id), {
            onSuccess: () => toast.success("Adjustment deleted, ledger rolled back"),
            onError: () => toast.error("Failed to delete adjustment"),
        });
    };

    const applyFilter = () => {
        router.get(route("adjustments.index"), {
            type: filterType !== "all" ? filterType : undefined,
        }, { preserveScroll: true, preserveState: true });
    };

    const batchTotal = Object.values(investorAmounts).reduce((s, v) => s + (parseFloat(v) || 0), 0);
    const sectorTotal = Object.values(sectorAmounts).reduce((s, v) => s + (parseFloat(v) || 0), 0);

    return (
        <AuthenticatedLayout
            title="Profit Adjustments"
            actions={
                <div className="flex items-center gap-3">
                    <Badge variant="info">Fund A: {formatBDT(fundBalances.fund_a, false)}</Badge>
                    <Badge variant="warning">Fund B: {formatBDT(fundBalances.fund_b, false)}</Badge>
                </div>
            }
        >
            <Head title="Profit Adjustments" />

            <PageTransition><div className="space-y-6">
                {/* Header */}
                <div className="flex items-center gap-3">
                    <div className="size-10 rounded-xl bg-primary-soft flex items-center justify-center">
                        <Settings2 className="size-5 text-primary" />
                    </div>
                    <div>
                        <h1 className="font-display text-2xl font-bold tracking-tight">Profit Adjustments</h1>
                        <p className="text-sm text-muted">Fund A, Fund B, and Direct investor adjustments — unified</p>
                    </div>
                </div>

                {/* Fund balance explanation */}
                <Card>
                    <CardContent className="p-4">
                        <div className="flex items-start gap-3">
                            <Info className="size-5 text-info shrink-0 mt-0.5" />
                            <div className="text-sm">
                                <p className="font-medium">Fund Balance = Σ(Investor adjustments) − Σ(Sector adjustments)</p>
                                <p className="text-muted mt-1">
                                    Fund balances are computed on-the-fly from actual adjustment records — they can never drift.
                                    Each investor adjustment decreases their profit due; each sector adjustment decreases the sector's profit due.
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Tabs value={tab} onValueChange={setTab}>
                    <TabsList>
                        <TabsTrigger value="fund_a">Fund A</TabsTrigger>
                        <TabsTrigger value="fund_b">Fund B</TabsTrigger>
                        <TabsTrigger value="direct">Direct</TabsTrigger>
                    </TabsList>

                    {/* === FUND A / FUND B (batch mode) === */}
                    {(["fund_a", "fund_b"] as const).map((fundType) => (
                        <TabsContent key={fundType} value={fundType}>
                            <div className="grid lg:grid-cols-2 gap-6">
                                {/* Investor side */}
                                <Card>
                                    <CardHeader className="pb-3">
                                        <CardTitle className="text-base flex items-center gap-2">
                                            <Users className="size-4 text-primary" />
                                            Investor Adjustments
                                        </CardTitle>
                                        <CardDescription>Enter adjustment amount per investor (decreases profit due)</CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-2 max-h-64 sm:max-h-96 overflow-y-auto">
                                        {investors.map((inv) => (
                                            <div key={inv.id} className="flex items-center gap-3">
                                                <div className="flex-1 min-w-0">
                                                    <p className="text-sm font-medium truncate">{inv.name}</p>
                                                    <p className="text-xs text-muted">Tier {inv.deed_ratio}%{inv.reference ? ` · ${inv.reference}` : ""}</p>
                                                </div>
                                                <Input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    placeholder="0"
                                                    value={investorAmounts[inv.id] ?? ""}
                                                    onChange={(e) => setInvestorAmounts(prev => ({ ...prev, [inv.id]: e.target.value }))}
                                                    disabled={!canEdit}
                                                    className="w-32 font-num text-right"
                                                />
                                            </div>
                                        ))}
                                    </CardContent>
                                </Card>

                                {/* Sector side */}
                                <Card>
                                    <CardHeader className="pb-3">
                                        <CardTitle className="text-base flex items-center gap-2">
                                            <ShoppingBag className="size-4 text-accent" />
                                            Sector Adjustments
                                        </CardTitle>
                                        <CardDescription>Enter adjustment amount per sector (decreases sector profit due)</CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-2 max-h-64 sm:max-h-96 overflow-y-auto">
                                        {sectors.map((sec) => (
                                            <div key={sec.id} className="flex items-center gap-3">
                                                <div className="flex-1 min-w-0">
                                                    <p className="text-sm font-medium truncate">{sec.name}</p>
                                                </div>
                                                <Input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    placeholder="0"
                                                    value={sectorAmounts[sec.id] ?? ""}
                                                    onChange={(e) => setSectorAmounts(prev => ({ ...prev, [sec.id]: e.target.value }))}
                                                    disabled={!canEdit}
                                                    className="w-32 font-num text-right"
                                                />
                                            </div>
                                        ))}
                                    </CardContent>
                                </Card>
                            </div>

                            {/* Batch totals + save */}
                            <Card className="mt-4">
                                <CardContent className="p-4">
                                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                        <div>
                                            <Label>Date</Label>
                                            <Input
                                                type="date"
                                                value={batchForm.data.transaction_date}
                                                onChange={(e) => batchForm.setData("transaction_date", e.target.value)}
                                                disabled={!canEdit}
                                                className="font-num"
                                            />
                                        </div>
                                        <div>
                                            <Label>Profit Month</Label>
                                            <Input
                                                type="date"
                                                value={batchForm.data.profit_month}
                                                onChange={(e) => batchForm.setData("profit_month", e.target.value)}
                                                disabled={!canEdit}
                                                className="font-num"
                                            />
                                        </div>
                                        <div>
                                            <Label>Investor Total</Label>
                                            <p className="font-num text-lg font-bold text-primary mt-2">{formatBDT(batchTotal, false)}</p>
                                        </div>
                                        <div>
                                            <Label>Sector Total</Label>
                                            <p className="font-num text-lg font-bold text-accent mt-2">{formatBDT(sectorTotal, false)}</p>
                                        </div>
                                    </div>
                                    <div className="mt-4">
                                        <Label>Remarks</Label>
                                        <Input
                                            value={batchForm.data.remarks}
                                            onChange={(e) => batchForm.setData("remarks", e.target.value)}
                                            placeholder="Optional notes…"
                                            disabled={!canEdit}
                                        />
                                    </div>
                                    <div className="mt-4 flex items-center justify-between">
                                        <div className="flex items-center gap-2 text-sm text-muted">
                                            <Wallet className="size-4" />
                                            Current {fundType === "fund_a" ? "Fund A" : "Fund B"} Balance:
                                            <span className="font-num font-bold">
                                                {formatBDT(fundType === "fund_a" ? fundBalances.fund_a : fundBalances.fund_b, false)}
                                            </span>
                                            {batchTotal > 0 || sectorTotal > 0 ? (
                                                <span className="text-muted">
                                                    → After: <span className="font-num font-bold text-info">
                                                        {formatBDT(
                                                            (fundType === "fund_a" ? fundBalances.fund_a : fundBalances.fund_b) + batchTotal - sectorTotal,
                                                            false,
                                                        )}
                                                    </span>
                                                </span>
                                            ) : null}
                                        </div>
                                        <Button onClick={handleBatchSubmit} disabled={!canEdit || batchForm.processing}>
                                            <Save className="size-4" />
                                            {batchForm.processing ? "Saving…" : `Save ${fundType === "fund_a" ? "Fund A" : "Fund B"} Batch`}
                                        </Button>
                                    </div>
                                    {/* Hidden type field */}
                                    <input type="hidden" value={fundType} onChange={() => batchForm.setData("type", fundType)} />
                                </CardContent>
                            </Card>
                        </TabsContent>
                    ))}

                    {/* === DIRECT (single investor) === */}
                    <TabsContent value="direct">
                        <Card className="max-w-xl mx-auto">
                            <CardHeader>
                                <CardTitle>Direct Investor Adjustment</CardTitle>
                                <CardDescription>
                                    Adjust a single investor's profit due directly. No sector side, no fund tracking.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={handleDirectSubmit} className="space-y-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="direct_investor">Investor *</Label>
                                        <Select value={directForm.data.investor_id} onValueChange={(v) => directForm.setData("investor_id", v)}>
                                            <SelectTrigger><SelectValue placeholder="Select investor…" /></SelectTrigger>
                                            <SelectContent>
                                                {investors.map((inv) => (
                                                    <SelectItem key={inv.id} value={String(inv.id)}>
                                                        {inv.name} (Tier {inv.deed_ratio}%)
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {directForm.errors.investor_id && <p className="text-xs text-danger">{directForm.errors.investor_id}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="direct_amount">Amount (BDT) *</Label>
                                        <Input
                                            id="direct_amount"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={directForm.data.amount}
                                            onChange={(e) => directForm.setData("amount", e.target.value)}
                                            placeholder="0.00"
                                            className="font-num text-lg"
                                            disabled={!canEdit}
                                        />
                                        {directForm.errors.amount && <p className="text-xs text-danger">{directForm.errors.amount}</p>}
                                    </div>
                                    <div className="grid grid-cols-2 gap-3">
                                        <div className="space-y-2">
                                            <Label htmlFor="direct_date">Transaction Date *</Label>
                                            <Input
                                                id="direct_date"
                                                type="date"
                                                value={directForm.data.transaction_date}
                                                onChange={(e) => directForm.setData("transaction_date", e.target.value)}
                                                className="font-num"
                                                disabled={!canEdit}
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="direct_month">Profit Month *</Label>
                                            <Input
                                                id="direct_month"
                                                type="date"
                                                value={directForm.data.profit_month}
                                                onChange={(e) => directForm.setData("profit_month", e.target.value)}
                                                className="font-num"
                                                disabled={!canEdit}
                                            />
                                        </div>
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="direct_remarks">Remarks</Label>
                                        <Input
                                            id="direct_remarks"
                                            value={directForm.data.remarks}
                                            onChange={(e) => directForm.setData("remarks", e.target.value)}
                                            placeholder="Optional notes…"
                                            disabled={!canEdit}
                                        />
                                    </div>
                                    <Button type="submit" className="w-full" disabled={!canEdit || directForm.processing}>
                                        <ArrowRightLeft className="size-4" />
                                        {directForm.processing ? "Saving…" : "Save Direct Adjustment"}
                                    </Button>
                                </form>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>

                {/* History table */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div>
                                <CardTitle>Adjustment History</CardTitle>
                                <CardDescription>{adjustments.total} total adjustments</CardDescription>
                            </div>
                            <div className="flex items-center gap-2">
                                <Select value={filterType} onValueChange={setFilterType}>
                                    <SelectTrigger className="w-40"><SelectValue placeholder="All types" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All types</SelectItem>
                                        <SelectItem value="fund_a">Fund A</SelectItem>
                                        <SelectItem value="fund_b">Fund B</SelectItem>
                                        <SelectItem value="direct">Direct</SelectItem>
                                    </SelectContent>
                                </Select>
                                <Button variant="outline" size="sm" onClick={applyFilter}>Filter</Button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="p-0">
                        <div className="rounded-lg border border-border overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Date</TableHead>
                                        <TableHead>Type</TableHead>
                                        <TableHead>Target</TableHead>
                                        <TableHead className="text-right">Amount</TableHead>
                                        <TableHead>Remarks</TableHead>
                                        <TableHead>By</TableHead>
                                        <TableHead className="text-right">Action</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {adjustments.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={7} className="text-center py-12 text-muted">
                                                No adjustments found. Use the tabs above to create one.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        adjustments.data.map((adj) => (
                                            <TableRow key={adj.id}>
                                                <TableCell className="font-num">{adj.transaction_date}</TableCell>
                                                <TableCell>
                                                    <Badge variant={
                                                        adj.type === "fund_a" ? "info" :
                                                        adj.type === "fund_b" ? "warning" : "accent"
                                                    }>{adj.type_label}</Badge>
                                                </TableCell>
                                                <TableCell>
                                                    <span className="flex items-center gap-1.5">
                                                        {adj.target_type === "investor" ? (
                                                            <Users className="size-3 text-muted" />
                                                        ) : (
                                                            <ShoppingBag className="size-3 text-muted" />
                                                        )}
                                                        {adj.target_name}
                                                    </span>
                                                </TableCell>
                                                <TableCell className="text-right font-num font-medium text-danger">
                                                    −{formatBDT(adj.amount, false)}
                                                </TableCell>
                                                <TableCell className="text-muted text-sm max-w-[200px] truncate">{adj.remarks ?? "—"}</TableCell>
                                                <TableCell className="text-muted text-sm">{adj.created_by}</TableCell>
                                                <TableCell className="text-right">
                                                    <Button variant="ghost" size="icon" aria-label="Delete" onClick={() => handleDelete(adj)} disabled={!canEdit}>
                                                        <Trash2 className="size-4 text-danger" />
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {adjustments.last_page > 1 && (
                            <div className="flex items-center justify-between px-4 py-3 border-t border-border">
                                <p className="text-xs text-muted">
                                    Showing {adjustments.from ?? 0}–{adjustments.to ?? 0} of {adjustments.total}
                                </p>
                                <div className="flex items-center gap-1">
                                    {adjustments.links.map((link, i) => (
                                        <button
                                            key={i}
                                            onClick={() => link.url && router.get(link.url, {}, { preserveScroll: true, preserveState: true })}
                                            disabled={!link.url}
                                            className={cn(
                                                "px-3 py-1.5 text-sm rounded-md border transition-colors",
                                                link.active ? "bg-primary text-primary-foreground border-primary" : "border-border hover:bg-surface-2 disabled:opacity-40",
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
