import { Head, router } from "@inertiajs/react";
import { useState } from "react";
import { route } from "ziggy-js";
import { AuthenticatedLayout } from "@/Components/layout";
import {
    Card, CardContent, CardDescription, CardHeader, CardTitle,
    Button, Badge, Input, Label, Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from "@/Components/ui";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui";
import {
    ScrollText, Search, Download, TrendingUp, TrendingDown,
    ArrowUpCircle, ArrowDownCircle, ShoppingBag, Wallet, FileBarChart,
} from "lucide-react";
import { formatBDT, cn } from "@/lib/utils";
import { toast } from "sonner";

interface LedgerEntry {
    date: string;
    type: string;
    subtype: string;
    description: string;
    amount: number;
    amount_display: number;
    is_positive: boolean;
    remarks: string | null;
    created_by: string;
    running_balance: number;
}

interface SectorOption { id: number; name: string; }

interface SectorInfo {
    id: number; name: string;
    opening_balance: number; opening_profit_due: number;
}

interface Summary {
    total_entries: number; total_inflow: number; total_outflow: number; net_balance: number;
}

interface Props {
    sectors: SectorOption[];
    selectedId: number | null;
    sector: SectorInfo | null;
    ledger: LedgerEntry[];
    filters: { date_from: string | null; date_to: string | null };
    summary: Summary;
}

const typeBadge = (type: string, subtype: string) => {
    if (type === "capital") return subtype === "add"
        ? <Badge variant="success"><ArrowUpCircle className="size-3" /> Add</Badge>
        : <Badge variant="danger"><ArrowDownCircle className="size-3" /> Withdraw</Badge>;
    if (type === "profit") return <Badge variant="primary"><FileBarChart className="size-3" /> {subtype === "estimated" ? "Est (Z)" : "Actual (X)"}</Badge>;
    if (type === "variance") return <Badge variant="warning"><TrendingDown className="size-3" /> Variance (Y)</Badge>;
    if (type === "adjustment") return <Badge variant="accent"><ScrollText className="size-3" /> {subtype}</Badge>;
    return <Badge variant="outline">{type}</Badge>;
};

export default function SectorLedger({ sectors, selectedId, sector, ledger, filters, summary }: Props) {
    const [selectedSector, setSelectedSector] = useState(selectedId ? String(selectedId) : "");
    const [dateFrom, setDateFrom] = useState(filters.date_from ?? "");
    const [dateTo, setDateTo] = useState(filters.date_to ?? "");

    const applyFilters = () => {
        const params: Record<string, string> = {};
        if (selectedSector) params.sector_id = selectedSector;
        if (dateFrom) params.date_from = dateFrom;
        if (dateTo) params.date_to = dateTo;
        router.get(route("reports.sector-ledger"), params, { preserveScroll: true, preserveState: true });
    };

    return (
        <AuthenticatedLayout title="Sector Ledger">
            <Head title="Sector Ledger Report" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center gap-3">
                    <div className="size-10 rounded-xl bg-primary-soft flex items-center justify-center">
                        <ShoppingBag className="size-5 text-primary" />
                    </div>
                    <div>
                        <h1 className="font-display text-2xl font-bold tracking-tight">Sector Ledger Report</h1>
                        <p className="text-sm text-muted">Investments, monthly profits, and adjustments with running balance</p>
                    </div>
                </div>

                {/* Filters */}
                <Card>
                    <CardContent className="p-4">
                        <div className="grid sm:grid-cols-4 gap-3">
                            <div className="space-y-1.5">
                                <Label>Sector</Label>
                                <Select value={selectedSector} onValueChange={setSelectedSector}>
                                    <SelectTrigger><SelectValue placeholder="Select sector…" /></SelectTrigger>
                                    <SelectContent>
                                        {sectors.map((sec) => (
                                            <SelectItem key={sec.id} value={String(sec.id)}>{sec.name}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-1.5">
                                <Label>From Date</Label>
                                <Input type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} className="font-num" />
                            </div>
                            <div className="space-y-1.5">
                                <Label>To Date</Label>
                                <Input type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} className="font-num" />
                            </div>
                            <div className="flex items-end">
                                <Button onClick={applyFilters} className="w-full">
                                    <Search className="size-4" /> Generate
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Sector info + summary (when selected) */}
                {sector && (
                    <>
                        <Card>
                            <CardContent className="p-6">
                                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div className="flex items-center gap-4">
                                        <div className="size-12 rounded-xl bg-gradient-to-br from-primary to-emerald-600 flex items-center justify-center text-white font-bold text-lg">
                                            {sector.name.split(" ").map(n => n[0]).slice(0, 2).join("")}
                                        </div>
                                        <div>
                                            <h2 className="font-display text-xl font-bold">{sector.name}</h2>
                                        </div>
                                    </div>
                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="text-right">
                                            <p className="text-xs text-muted">Opening Capital Balance</p>
                                            <p className="font-num text-lg font-bold">{formatBDT(sector.opening_balance)}</p>
                                        </div>
                                        <div className="text-right">
                                            <p className="text-xs text-muted">Opening Profit Due</p>
                                            <p className="font-num text-lg font-bold text-accent">{formatBDT(sector.opening_profit_due)}</p>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Summary stats */}
                        <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                            <Card>
                                <CardContent className="p-4">
                                    <div className="size-9 rounded-lg bg-primary-soft flex items-center justify-center">
                                        <ScrollText className="size-4 text-primary" />
                                    </div>
                                    <p className="text-xs text-muted mt-3">Total Entries</p>
                                    <p className="font-num text-lg font-semibold mt-1">{summary.total_entries}</p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent className="p-4">
                                    <div className="size-9 rounded-lg bg-success-soft flex items-center justify-center">
                                        <TrendingUp className="size-4 text-success" />
                                    </div>
                                    <p className="text-xs text-muted mt-3">Total Inflow</p>
                                    <p className="font-num text-lg font-semibold mt-1 text-success">{formatBDT(summary.total_inflow, false)}</p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent className="p-4">
                                    <div className="size-9 rounded-lg bg-danger-soft flex items-center justify-center">
                                        <TrendingDown className="size-4 text-danger" />
                                    </div>
                                    <p className="text-xs text-muted mt-3">Total Outflow</p>
                                    <p className="font-num text-lg font-semibold mt-1 text-danger">{formatBDT(summary.total_outflow, false)}</p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent className="p-4">
                                    <div className="size-9 rounded-lg bg-accent-soft flex items-center justify-center">
                                        <Wallet className="size-4 text-accent" />
                                    </div>
                                    <p className="text-xs text-muted mt-3">Net Movement</p>
                                    <p className={cn("font-num text-lg font-semibold mt-1", summary.net_balance >= 0 ? "text-success" : "text-danger")}>
                                        {summary.net_balance >= 0 ? "+" : ""}{formatBDT(summary.net_balance, false)}
                                    </p>
                                </CardContent>
                            </Card>
                        </div>
                    </>
                )}

                {/* Ledger table */}
                {sector ? (
                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <div>
                                    <CardTitle>Transaction Timeline</CardTitle>
                                    <CardDescription>Investments, monthly profits, and adjustments</CardDescription>
                                </div>
                                <Button variant="outline" size="sm" onClick={() => window.open(route("exports.sector-ledger") + "?sector_id=" + selectedSector + (dateFrom ? "&date_from=" + dateFrom : "") + (dateTo ? "&date_to=" + dateTo : ""))}>
                                    <Download className="size-4" /> Export
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent className="p-0">
                            <div className="rounded-lg border border-border overflow-hidden">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Date</TableHead>
                                            <TableHead>Type</TableHead>
                                            <TableHead>Description</TableHead>
                                            <TableHead className="text-right">Inflow</TableHead>
                                            <TableHead className="text-right">Outflow</TableHead>
                                            <TableHead className="text-right">Running Balance</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {ledger.length === 0 ? (
                                            <TableRow>
                                                <TableCell colSpan={6} className="text-center py-12 text-muted">
                                                    No transactions found for the selected period.
                                                </TableCell>
                                            </TableRow>
                                        ) : (
                                            ledger.map((entry, i) => (
                                                <TableRow key={i}>
                                                    <TableCell className="font-num whitespace-nowrap">{entry.date}</TableCell>
                                                    <TableCell>{typeBadge(entry.type, entry.subtype)}</TableCell>
                                                    <TableCell>
                                                        <div>
                                                            <p className="text-sm font-medium">{entry.description}</p>
                                                            {entry.remarks && (
                                                                <p className="text-xs text-muted truncate max-w-xs">{entry.remarks}</p>
                                                            )}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell className="text-right font-num text-success">
                                                        {entry.is_positive ? formatBDT(entry.amount_display, false) : "—"}
                                                    </TableCell>
                                                    <TableCell className="text-right font-num text-danger">
                                                        {!entry.is_positive ? formatBDT(entry.amount_display, false) : "—"}
                                                    </TableCell>
                                                    <TableCell className={cn(
                                                        "text-right font-num font-bold",
                                                        entry.running_balance >= 0 ? "text-foreground" : "text-danger",
                                                    )}>
                                                        {formatBDT(entry.running_balance, false)}
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        )}
                                    </TableBody>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardContent className="p-12 text-center">
                            <ShoppingBag className="size-12 text-muted mx-auto mb-4" />
                            <h3 className="font-display text-lg font-semibold">Select a Sector</h3>
                            <p className="text-sm text-muted mt-2">Choose a sector above to generate its ledger report.</p>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
