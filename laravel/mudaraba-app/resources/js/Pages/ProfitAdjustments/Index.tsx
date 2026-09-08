import { Head, router, useForm } from "@inertiajs/react";
import { useMemo, useState } from "react";
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
    Users, ShoppingBag, Info, Banknote,
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

interface InvestorOption {
    id: number;
    name: string;
    reference: string | null;
    deed_ratio: string;
    adjustable_balance: number;
    investment: number;
}
interface SectorOption {
    id: number;
    name: string;
    due_balance: number;
}

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

    // ----- Fund A: deed ratio filter + adv_adjust auto-distribute -----
    const [fundATier, setFundATier] = useState<string>("60");
    const [advAdjust, setAdvAdjust] = useState<string>("");
    const [fundAInvestorAmounts, setFundAInvestorAmounts] = useState<Record<number, string>>({});
    const [fundASectorAmounts, setFundASectorAmounts] = useState<Record<number, string>>({});
    const [batchCommon, setBatchCommon] = useState({
        transaction_date: new Date().toISOString().slice(0, 10),
        profit_month: new Date().toISOString().slice(0, 7) + "-01",
        remarks: "",
    });

    // ----- Fund B: sector-only -----
    const [fundBSectorAmounts, setFundBSectorAmounts] = useState<Record<number, string>>({});

    // ----- Direct: 2 modes -----
    const [directMode, setDirectMode] = useState<"investor_wise" | "as_per_invest">("investor_wise");
    const [directInvestorId, setDirectInvestorId] = useState<string>("");
    const [directSectorId, setDirectSectorId] = useState<string>("");
    const [directTotalAmount, setDirectTotalAmount] = useState<string>("");
    const [directInvestorAmounts, setDirectInvestorAmounts] = useState<Record<number, string>>({});
    const [directCommon, setDirectCommon] = useState({
        transaction_date: new Date().toISOString().slice(0, 10),
        profit_month: new Date().toISOString().slice(0, 7) + "-01",
        remarks: "",
    });

    const directForm = useForm({
        mode: "investor_wise" as "investor_wise" | "as_per_invest",
        sector_id: "",
        investor_id: "",
        total_amount: "",
        transaction_date: new Date().toISOString().slice(0, 10),
        profit_month: new Date().toISOString().slice(0, 7) + "-01",
        remarks: "",
        investor_items: [] as { investor_id: number; amount: string }[],
    });

    // Fund A — filtered investors by tier
    const fundAInvestors = useMemo(
        () => investors.filter((i) => i.deed_ratio === fundATier),
        [investors, fundATier],
    );
    const fundATotalInvestment = fundAInvestors.reduce((s, i) => s + i.investment, 0);
    const fundAAdjustableBalance = fundAInvestors.reduce((s, i) => s + i.adjustable_balance, 0);

    // Auto-distribute advAdjust across filtered investors by investment ratio
    const handleAdvAdjust = (val: string) => {
        setAdvAdjust(val);
        const total = parseFloat(val) || 0;
        const next: Record<number, string> = {};
        if (fundATotalInvestment > 0) {
            for (const inv of fundAInvestors) {
                next[inv.id] = ((inv.investment / fundATotalInvestment) * total).toFixed(2);
            }
        }
        setFundAInvestorAmounts(next);
    };

    const fundAInvestorTotal = Object.values(fundAInvestorAmounts).reduce((s, v) => s + (parseFloat(v) || 0), 0);
    const fundASectorTotal = Object.values(fundASectorAmounts).reduce((s, v) => s + (parseFloat(v) || 0), 0);
    const fundAProjected = fundBalances.fund_a + fundAInvestorTotal - fundASectorTotal;

    const fundBSectorTotal = Object.values(fundBSectorAmounts).reduce((s, v) => s + (parseFloat(v) || 0), 0);
    const fundBProjected = fundBalances.fund_b + fundBSectorTotal; // Fund B grows by sector total

    // Direct as-per-invest: auto-distribute by investment ratio
    const directTotalInvestment = investors.reduce((s, i) => s + i.investment, 0);
    const handleDirectTotalAmount = (val: string) => {
        setDirectTotalAmount(val);
        const total = parseFloat(val) || 0;
        const next: Record<number, string> = {};
        if (directTotalInvestment > 0) {
            for (const inv of investors) {
                next[inv.id] = ((inv.investment / directTotalInvestment) * total).toFixed(2);
            }
        }
        setDirectInvestorAmounts(next);
    };
    const directBulkInvestorTotal = Object.values(directInvestorAmounts).reduce((s, v) => s + (parseFloat(v) || 0), 0);

    // ===== Submit handlers =====
    const handleFundASubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const invItems = Object.entries(fundAInvestorAmounts)
            .filter(([, v]) => parseFloat(v) > 0)
            .map(([id, amount]) => ({ investor_id: parseInt(id), amount }));
        const secItems = Object.entries(fundASectorAmounts)
            .filter(([, v]) => parseFloat(v) > 0)
            .map(([id, amount]) => ({ sector_id: parseInt(id), amount }));
        if (invItems.length === 0 && secItems.length === 0) {
            toast.error("Enter at least one adjustment amount");
            return;
        }
        const payload = {
            type: "fund_a",
            transaction_date: batchCommon.transaction_date,
            profit_month: batchCommon.profit_month,
            remarks: batchCommon.remarks,
            investor_items: invItems,
            sector_items: secItems,
        };
        router.post(route("adjustments.store-batch"), payload, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Fund A batch saved");
                setFundAInvestorAmounts({});
                setFundASectorAmounts({});
                setAdvAdjust("");
            },
            onError: (e) => toast.error(Object.values(e)[0] as string || "Failed to save Fund A"),
        });
    };

    const handleFundBSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const secItems = Object.entries(fundBSectorAmounts)
            .filter(([, v]) => parseFloat(v) > 0)
            .map(([id, amount]) => ({ sector_id: parseInt(id), amount }));
        if (secItems.length === 0) {
            toast.error("Enter at least one sector amount");
            return;
        }
        const payload = {
            type: "fund_b",
            transaction_date: batchCommon.transaction_date,
            profit_month: batchCommon.profit_month,
            remarks: batchCommon.remarks,
            investor_items: [], // Fund B is sector-only
            sector_items: secItems,
        };
        router.post(route("adjustments.store-batch"), payload, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Fund B batch saved");
                setFundBSectorAmounts({});
            },
            onError: (e) => toast.error(Object.values(e)[0] as string || "Failed to save Fund B"),
        });
    };

    const handleDirectSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!directSectorId) {
            toast.error("Select a sector");
            return;
        }
        if (directMode === "investor_wise") {
            if (!directInvestorId) { toast.error("Select an investor"); return; }
            if (!directTotalAmount || parseFloat(directTotalAmount) <= 0) { toast.error("Enter total amount"); return; }
            directForm.setData({
                mode: "investor_wise",
                sector_id: directSectorId,
                investor_id: directInvestorId,
                total_amount: directTotalAmount,
                transaction_date: directCommon.transaction_date,
                profit_month: directCommon.profit_month,
                remarks: directCommon.remarks,
                investor_items: [],
            });
        } else {
            const invItems = Object.entries(directInvestorAmounts)
                .filter(([, v]) => parseFloat(v) > 0)
                .map(([id, amount]) => ({ investor_id: parseInt(id), amount }));
            if (invItems.length === 0) { toast.error("Distribute to at least one investor"); return; }
            if (!directTotalAmount || parseFloat(directTotalAmount) <= 0) { toast.error("Enter total amount"); return; }
            directForm.setData({
                mode: "as_per_invest",
                sector_id: directSectorId,
                investor_id: "",
                total_amount: directTotalAmount,
                transaction_date: directCommon.transaction_date,
                profit_month: directCommon.profit_month,
                remarks: directCommon.remarks,
                investor_items: invItems,
            });
        }
        // submit on next tick so setData is flushed
        setTimeout(() => {
            directForm.post(route("adjustments.store-direct"), {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success(`Direct funding (${directMode === "investor_wise" ? "Investor-Wise" : "As-Per-Invest"}) saved`);
                    setDirectTotalAmount("");
                    setDirectInvestorAmounts({});
                    setDirectInvestorId("");
                    setDirectSectorId("");
                },
                onError: (e) => toast.error(Object.values(e)[0] as string || "Failed to save direct funding"),
            });
        }, 0);
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
                        <p className="text-sm text-muted">Fund A, Fund B, and Direct investor adjustments — matching the original PHP system</p>
                    </div>
                </div>

                {/* Fund balance explanation */}
                <Card>
                    <CardContent className="p-4">
                        <div className="flex items-start gap-3">
                            <Info className="size-5 text-info shrink-0 mt-0.5" />
                            <div className="text-sm space-y-1">
                                <p><strong>Fund A:</strong> investors ADD to the pool, sectors DEDUCT — balance = Σ(investor) − Σ(sector)</p>
                                <p><strong>Fund B:</strong> sector surplus INCREASES the reserve — balance = +Σ(sector), no investor side</p>
                                <p><strong>Direct:</strong> sector ↔ investor transfer, no fund tracking — 2 modes (investor-wise + as-per-invest)</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Tabs value={tab} onValueChange={setTab}>
                    <TabsList>
                        <TabsTrigger value="fund_a">Fund A</TabsTrigger>
                        <TabsTrigger value="fund_b">Fund B</TabsTrigger>
                        <TabsTrigger value="direct">Direct Funding</TabsTrigger>
                    </TabsList>

                    {/* === FUND A — investors + sectors, deed ratio filter, auto-distribute === */}
                    <TabsContent value="fund_a">
                        <form onSubmit={handleFundASubmit} className="space-y-4">
                            <div className="grid gap-4 md:grid-cols-3">
                                <div>
                                    <Label>Deed Ratio Group</Label>
                                    <Select value={fundATier} onValueChange={setFundATier}>
                                        <SelectTrigger><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="100">Tier 100% (1.0)</SelectItem>
                                            <SelectItem value="80">Tier 80% (0.8)</SelectItem>
                                            <SelectItem value="60">Tier 60% (0.6)</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <Label>Transaction Date</Label>
                                    <Input type="date" value={batchCommon.transaction_date}
                                        onChange={(e) => setBatchCommon(p => ({ ...p, transaction_date: e.target.value }))}
                                        disabled={!canEdit} className="font-num" />
                                </div>
                                <div>
                                    <Label>Profit Month</Label>
                                    <Input type="date" value={batchCommon.profit_month}
                                        onChange={(e) => setBatchCommon(p => ({ ...p, profit_month: e.target.value }))}
                                        disabled={!canEdit} className="font-num" />
                                </div>
                            </div>

                            <div className="grid gap-3 md:grid-cols-3">
                                <div className="rounded-lg border border-emerald-200 dark:border-emerald-900 bg-emerald-50/50 dark:bg-emerald-950/20 p-3">
                                    <p className="text-xs text-muted">Adjustable Balance (group)</p>
                                    <p className="font-num text-lg font-bold text-emerald-700 dark:text-emerald-400">{formatBDT(fundAAdjustableBalance, false)}</p>
                                </div>
                                <div className="rounded-lg border p-3">
                                    <p className="text-xs text-muted">Group Investment</p>
                                    <p className="font-num text-lg font-bold">{formatBDT(fundATotalInvestment, false)}</p>
                                </div>
                                <div className="rounded-lg border p-3">
                                    <Label>Adv Adjust (auto-distributes)</Label>
                                    <Input type="number" step="0.01" min="0" value={advAdjust}
                                        onChange={(e) => handleAdvAdjust(e.target.value)}
                                        placeholder="0.00" disabled={!canEdit} className="font-num text-lg" />
                                </div>
                            </div>

                            <div className="grid gap-4 lg:grid-cols-2">
                                {/* Investor side */}
                                <Card>
                                    <CardHeader className="pb-3">
                                        <CardTitle className="text-base flex items-center gap-2">
                                            <Users className="size-4 text-primary" />
                                            Investor Adjustments ({fundAInvestors.length})
                                        </CardTitle>
                                        <CardDescription>Auto-distributed by investment ratio — editable</CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-2 max-h-72 overflow-y-auto">
                                        {fundAInvestors.map((inv) => (
                                            <div key={inv.id} className="flex items-center gap-3 py-1">
                                                <div className="flex-1 min-w-0">
                                                    <p className="text-sm font-medium truncate">{inv.name}</p>
                                                    <p className="text-xs text-muted">
                                                        Inv: {formatBDT(inv.investment, false)} · Adj Bal: {formatBDT(inv.adjustable_balance, false)}
                                                    </p>
                                                </div>
                                                <Input type="number" step="0.01" min="0" placeholder="0"
                                                    value={fundAInvestorAmounts[inv.id] ?? ""}
                                                    onChange={(e) => setFundAInvestorAmounts(p => ({ ...p, [inv.id]: e.target.value }))}
                                                    disabled={!canEdit} className="w-32 font-num text-right" />
                                            </div>
                                        ))}
                                        {fundAInvestors.length === 0 && (
                                            <p className="text-sm text-muted text-center py-4">No investors in this tier.</p>
                                        )}
                                    </CardContent>
                                </Card>

                                {/* Sector side */}
                                <Card>
                                    <CardHeader className="pb-3">
                                        <CardTitle className="text-base flex items-center gap-2">
                                            <ShoppingBag className="size-4 text-accent" />
                                            Sector Allocations ({sectors.length})
                                        </CardTitle>
                                        <CardDescription>Enter amount per sector (deducts from Fund A)</CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-2 max-h-72 overflow-y-auto">
                                        {sectors.map((sec) => (
                                            <div key={sec.id} className="flex items-center gap-3 py-1">
                                                <div className="flex-1 min-w-0">
                                                    <p className="text-sm font-medium truncate">{sec.name}</p>
                                                    <p className="text-xs text-muted">Due: {formatBDT(sec.due_balance, false)}</p>
                                                </div>
                                                <Input type="number" step="0.01" min="0" placeholder="0"
                                                    value={fundASectorAmounts[sec.id] ?? ""}
                                                    onChange={(e) => setFundASectorAmounts(p => ({ ...p, [sec.id]: e.target.value }))}
                                                    disabled={!canEdit} className="w-32 font-num text-right" />
                                            </div>
                                        ))}
                                    </CardContent>
                                </Card>
                            </div>

                            <Card>
                                <CardContent className="p-4">
                                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                        <div>
                                            <Label>Investor Total</Label>
                                            <p className="font-num text-lg font-bold text-primary mt-2">{formatBDT(fundAInvestorTotal, false)}</p>
                                        </div>
                                        <div>
                                            <Label>Sector Total</Label>
                                            <p className="font-num text-lg font-bold text-accent mt-2">{formatBDT(fundASectorTotal, false)}</p>
                                        </div>
                                        <div>
                                            <Label>Net Fund Change</Label>
                                            <p className="font-num text-lg font-bold mt-2">{formatBDT(fundAInvestorTotal - fundASectorTotal, false)}</p>
                                        </div>
                                        <div>
                                            <Label>Projected Fund A</Label>
                                            <p className="font-num text-lg font-bold text-emerald-700 dark:text-emerald-400 mt-2">{formatBDT(fundAProjected, false)}</p>
                                        </div>
                                    </div>
                                    <div className="mt-4">
                                        <Label>Remarks</Label>
                                        <Input value={batchCommon.remarks}
                                            onChange={(e) => setBatchCommon(p => ({ ...p, remarks: e.target.value }))}
                                            placeholder="Optional notes…" disabled={!canEdit} />
                                    </div>
                                    <div className="mt-4 flex items-center justify-between">
                                        <div className="flex items-center gap-2 text-sm text-muted">
                                            <Wallet className="size-4" />
                                            Current Fund A: <span className="font-num font-bold">{formatBDT(fundBalances.fund_a, false)}</span>
                                        </div>
                                        <Button type="submit" disabled={!canEdit}>
                                            <Save className="size-4" /> Save Fund A Batch
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        </form>
                    </TabsContent>

                    {/* === FUND B — sector-only surplus → reserve === */}
                    <TabsContent value="fund_b">
                        <form onSubmit={handleFundBSubmit} className="space-y-4">
                            <div className="grid gap-4 md:grid-cols-3">
                                <div>
                                    <Label>Transaction Date</Label>
                                    <Input type="date" value={batchCommon.transaction_date}
                                        onChange={(e) => setBatchCommon(p => ({ ...p, transaction_date: e.target.value }))}
                                        disabled={!canEdit} className="font-num" />
                                </div>
                                <div>
                                    <Label>Profit Month</Label>
                                    <Input type="date" value={batchCommon.profit_month}
                                        onChange={(e) => setBatchCommon(p => ({ ...p, profit_month: e.target.value }))}
                                        disabled={!canEdit} className="font-num" />
                                </div>
                                <div>
                                    <Label>Projected Fund B</Label>
                                    <div className="h-9 flex items-center px-3 rounded-md border bg-amber-50 dark:bg-amber-950/30">
                                        <span className="font-num font-bold text-amber-700 dark:text-amber-400">{formatBDT(fundBProjected, false)}</span>
                                    </div>
                                </div>
                            </div>

                            <Card>
                                <CardHeader className="pb-3">
                                    <CardTitle className="text-base flex items-center gap-2">
                                        <Banknote className="size-4 text-amber-600" />
                                        Sector Surplus ({sectors.length})
                                    </CardTitle>
                                    <CardDescription>
                                        Fund B is sector-only — investor side is not applicable. Each sector amount INCREASES the Fund B reserve.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-2 max-h-96 overflow-y-auto">
                                    {sectors.map((sec) => (
                                        <div key={sec.id} className="flex items-center gap-3 py-1">
                                            <div className="flex-1 min-w-0">
                                                <p className="text-sm font-medium truncate">{sec.name}</p>
                                                <p className="text-xs text-muted">Due: {formatBDT(sec.due_balance, false)}</p>
                                            </div>
                                            <Input type="number" step="0.01" min="0" placeholder="0"
                                                value={fundBSectorAmounts[sec.id] ?? ""}
                                                onChange={(e) => setFundBSectorAmounts(p => ({ ...p, [sec.id]: e.target.value }))}
                                                disabled={!canEdit} className="w-32 font-num text-right" />
                                        </div>
                                    ))}
                                </CardContent>
                            </Card>

                            <Card>
                                <CardContent className="p-4">
                                    <div className="grid grid-cols-2 sm:grid-cols-3 gap-4">
                                        <div>
                                            <Label>Total Sector Adjust</Label>
                                            <p className="font-num text-lg font-bold text-amber-600 mt-2">{formatBDT(fundBSectorTotal, false)}</p>
                                        </div>
                                        <div>
                                            <Label>Current Fund B</Label>
                                            <p className="font-num text-lg font-bold mt-2">{formatBDT(fundBalances.fund_b, false)}</p>
                                        </div>
                                        <div>
                                            <Label>Projected Fund B</Label>
                                            <p className="font-num text-lg font-bold text-emerald-700 dark:text-emerald-400 mt-2">{formatBDT(fundBProjected, false)}</p>
                                        </div>
                                    </div>
                                    <div className="mt-4">
                                        <Label>Remarks</Label>
                                        <Input value={batchCommon.remarks}
                                            onChange={(e) => setBatchCommon(p => ({ ...p, remarks: e.target.value }))}
                                            placeholder="Optional notes…" disabled={!canEdit} />
                                    </div>
                                    <div className="mt-4 flex justify-end">
                                        <Button type="submit" disabled={!canEdit}>
                                            <Save className="size-4" /> Save Fund B Batch
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        </form>
                    </TabsContent>

                    {/* === DIRECT FUNDING — sector ↔ investor, 2 modes === */}
                    <TabsContent value="direct">
                        <form onSubmit={handleDirectSubmit} className="space-y-4">
                            <Card>
                                <CardHeader className="pb-3">
                                    <CardTitle className="text-base flex items-center gap-2">
                                        <ArrowRightLeft className="size-4 text-accent" />
                                        Direct Funding — Sector ↔ Investor
                                    </CardTitle>
                                    <CardDescription>
                                        Direct transfer between a sector and investor(s). No fund ledger is touched.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div>
                                            <Label>Mode</Label>
                                            <Select value={directMode} onValueChange={(v) => setDirectMode(v as "investor_wise" | "as_per_invest")}>
                                                <SelectTrigger><SelectValue /></SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="investor_wise">Investor-Wise (single)</SelectItem>
                                                    <SelectItem value="as_per_invest">As-Per-Invest (bulk by ratio)</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div>
                                            <Label>Sector *</Label>
                                            <Select value={directSectorId} onValueChange={setDirectSectorId}>
                                                <SelectTrigger><SelectValue placeholder="Select sector…" /></SelectTrigger>
                                                <SelectContent>
                                                    {sectors.map((sec) => (
                                                        <SelectItem key={sec.id} value={String(sec.id)}>
                                                            {sec.name} (Due: {formatBDT(sec.due_balance, false)})
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </div>

                                    {directMode === "investor_wise" ? (
                                        <div className="grid gap-4 md:grid-cols-2">
                                            <div>
                                                <Label>Investor *</Label>
                                                <Select value={directInvestorId} onValueChange={setDirectInvestorId}>
                                                    <SelectTrigger><SelectValue placeholder="Select investor…" /></SelectTrigger>
                                                    <SelectContent>
                                                        {investors.map((inv) => (
                                                            <SelectItem key={inv.id} value={String(inv.id)}>
                                                                {inv.name} (Adj Bal: {formatBDT(inv.adjustable_balance, false)})
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                            <div>
                                                <Label>Total Amount (BDT) *</Label>
                                                <Input type="number" step="0.01" min="0" value={directTotalAmount}
                                                    onChange={(e) => setDirectTotalAmount(e.target.value)}
                                                    placeholder="0.00" disabled={!canEdit} className="font-num text-lg" />
                                            </div>
                                        </div>
                                    ) : (
                                        <div>
                                            <Label>Total Amount (auto-distributes by ratio) *</Label>
                                            <Input type="number" step="0.01" min="0" value={directTotalAmount}
                                                onChange={(e) => handleDirectTotalAmount(e.target.value)}
                                                placeholder="0.00" disabled={!canEdit} className="font-num text-lg" />
                                            <p className="text-xs text-muted mt-1">
                                                Distributed total: <span className="font-semibold">{formatBDT(directBulkInvestorTotal, false)}</span>
                                            </p>
                                        </div>
                                    )}

                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div>
                                            <Label>Transaction Date</Label>
                                            <Input type="date" value={directCommon.transaction_date}
                                                onChange={(e) => setDirectCommon(p => ({ ...p, transaction_date: e.target.value }))}
                                                disabled={!canEdit} className="font-num" />
                                        </div>
                                        <div>
                                            <Label>Profit Month</Label>
                                            <Input type="date" value={directCommon.profit_month}
                                                onChange={(e) => setDirectCommon(p => ({ ...p, profit_month: e.target.value }))}
                                                disabled={!canEdit} className="font-num" />
                                        </div>
                                    </div>

                                    <div>
                                        <Label>Remarks</Label>
                                        <Input value={directCommon.remarks}
                                            onChange={(e) => setDirectCommon(p => ({ ...p, remarks: e.target.value }))}
                                            placeholder="Optional notes…" disabled={!canEdit} />
                                    </div>

                                    {directMode === "as_per_invest" && (
                                        <Card>
                                            <CardHeader className="pb-3">
                                                <CardTitle className="text-sm">Investor Distribution ({investors.length})</CardTitle>
                                            </CardHeader>
                                            <CardContent className="p-0">
                                                <div className="max-h-72 overflow-y-auto">
                                                    <Table>
                                                        <TableHeader>
                                                            <TableRow>
                                                                <TableHead>Investor</TableHead>
                                                                <TableHead className="text-right">Investment</TableHead>
                                                                <TableHead className="text-right">Ratio</TableHead>
                                                                <TableHead className="text-right">Amount</TableHead>
                                                            </TableRow>
                                                        </TableHeader>
                                                        <TableBody>
                                                            {investors.map((inv) => (
                                                                <TableRow key={inv.id}>
                                                                    <TableCell className="font-medium">{inv.name}</TableCell>
                                                                    <TableCell className="text-right font-num">{formatBDT(inv.investment, false)}</TableCell>
                                                                    <TableCell className="text-right text-xs text-muted">
                                                                        {directTotalInvestment > 0 ? ((inv.investment / directTotalInvestment) * 100).toFixed(3) : "0.000"}%
                                                                    </TableCell>
                                                                    <TableCell className="text-right">
                                                                        <Input type="number" step="0.01" min="0" placeholder="0"
                                                                            value={directInvestorAmounts[inv.id] ?? ""}
                                                                            onChange={(e) => setDirectInvestorAmounts(p => ({ ...p, [inv.id]: e.target.value }))}
                                                                            disabled={!canEdit} className="w-28 font-num text-right h-8" />
                                                                    </TableCell>
                                                                </TableRow>
                                                            ))}
                                                        </TableBody>
                                                    </Table>
                                                </div>
                                            </CardContent>
                                        </Card>
                                    )}

                                    <div className="flex justify-end">
                                        <Button type="submit" disabled={!canEdit || directForm.processing}>
                                            <Save className="size-4" />
                                            {directForm.processing ? "Saving…" : `Save Direct (${directMode === "investor_wise" ? "Investor-Wise" : "As-Per-Invest"})`}
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        </form>
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
