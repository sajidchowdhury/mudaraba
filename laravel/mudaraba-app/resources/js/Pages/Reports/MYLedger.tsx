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
    BookOpen, Search, Download, TrendingUp, TrendingDown,
    Crown, Wallet, ArrowDownCircle, ArrowUpCircle,
} from "lucide-react";
import { formatBDT, cn } from "@/lib/utils";
import { toast } from "sonner";

interface LedgerEntry {
    date: string; type: string; subtype: string; description: string;
    amount: number; amount_display: number; is_positive: boolean;
    remarks: string | null; created_by: string; running_balance: number;
}
interface DirectorOption { id: number; name: string; is_my: boolean; }
interface DirectorInfo { id: number; name: string; is_my: boolean; opening_due: number; }
interface Summary { total_entries: number; total_inflow: number; total_outflow: number; net_balance: number; }

interface Props {
    directors: DirectorOption[];
    selectedId: number | null;
    director: DirectorInfo | null;
    ledger: LedgerEntry[];
    filters: { date_from: string | null; date_to: string | null };
    summary: Summary;
}

const typeBadge = (type: string, subtype: string) => {
    if (type === "transaction") return subtype === "withdraw"
        ? <Badge variant="warning"><ArrowDownCircle className="size-3" /> Withdraw</Badge>
        : <Badge variant="success"><ArrowUpCircle className="size-3" /> Return</Badge>;
    if (type === "profit") return <Badge variant="primary"><TrendingUp className="size-3" /> M/Y Profit</Badge>;
    if (type === "retained") return <Badge variant="accent"><Wallet className="size-3" /> Retained 29%</Badge>;
    return <Badge variant="outline">{type}</Badge>;
};

export default function MYLedger({ directors, selectedId, director, ledger, filters, summary }: Props) {
    const [selectedDirector, setSelectedDirector] = useState(selectedId ? String(selectedId) : "");
    const [dateFrom, setDateFrom] = useState(filters.date_from ?? "");
    const [dateTo, setDateTo] = useState(filters.date_to ?? "");

    const applyFilters = () => {
        const params: Record<string, string> = {};
        if (selectedDirector) params.director_id = selectedDirector;
        if (dateFrom) params.date_from = dateFrom;
        if (dateTo) params.date_to = dateTo;
        router.get(route("reports.my-ledger"), params, { preserveScroll: true, preserveState: true });
    };

    return (
        <AuthenticatedLayout title="M/Y Ledger">
            <Head title="M/Y Ledger Report" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center gap-3">
                    <div className="size-10 rounded-xl bg-primary-soft flex items-center justify-center">
                        <BookOpen className="size-5 text-primary" />
                    </div>
                    <div>
                        <h1 className="font-display text-2xl font-bold tracking-tight">M / Y Ledger Report</h1>
                        <p className="text-sm text-muted">Director transactions, M/Y profit, and retained earnings</p>
                    </div>
                </div>

                {/* Filters */}
                <Card>
                    <CardContent className="p-4">
                        <div className="grid sm:grid-cols-4 gap-3">
                            <div className="space-y-1.5">
                                <Label>Director</Label>
                                <Select value={selectedDirector} onValueChange={setSelectedDirector}>
                                    <SelectTrigger><SelectValue placeholder="Select director…" /></SelectTrigger>
                                    <SelectContent>
                                        {directors.map((dir) => (
                                            <SelectItem key={dir.id} value={String(dir.id)}>
                                                {dir.name} {dir.is_my && "👑"}
                                            </SelectItem>
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
                                <Button onClick={applyFilters} className="w-full"><Search className="size-4" /> Generate</Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Director info + summary */}
                {director && (
                    <>
                        <Card>
                            <CardContent className="p-6">
                                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div className="flex items-center gap-4">
                                        <div className="size-12 rounded-xl bg-gradient-to-br from-primary to-emerald-600 flex items-center justify-center text-white font-bold text-lg">
                                            {director.is_my ? <Crown className="size-6" /> : director.name[0]}
                                        </div>
                                        <div>
                                            <h2 className="font-display text-xl font-bold flex items-center gap-2">
                                                {director.name}
                                                {director.is_my && <Badge variant="accent"><Crown className="size-3" /> Primary M/Y</Badge>}
                                            </h2>
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        <p className="text-xs text-muted">Opening Due Balance</p>
                                        <p className="font-num text-lg font-bold">{formatBDT(director.opening_due)}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Summary stats */}
                        <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                            <Card><CardContent className="p-4">
                                <div className="size-9 rounded-lg bg-primary-soft flex items-center justify-center"><BookOpen className="size-4 text-primary" /></div>
                                <p className="text-xs text-muted mt-3">Total Entries</p>
                                <p className="font-num text-lg font-semibold mt-1">{summary.total_entries}</p>
                            </CardContent></Card>
                            <Card><CardContent className="p-4">
                                <div className="size-9 rounded-lg bg-success-soft flex items-center justify-center"><TrendingUp className="size-4 text-success" /></div>
                                <p className="text-xs text-muted mt-3">Total Inflow</p>
                                <p className="font-num text-lg font-semibold mt-1 text-success">{formatBDT(summary.total_inflow, false)}</p>
                            </CardContent></Card>
                            <Card><CardContent className="p-4">
                                <div className="size-9 rounded-lg bg-danger-soft flex items-center justify-center"><TrendingDown className="size-4 text-danger" /></div>
                                <p className="text-xs text-muted mt-3">Total Outflow</p>
                                <p className="font-num text-lg font-semibold mt-1 text-danger">{formatBDT(summary.total_outflow, false)}</p>
                            </CardContent></Card>
                            <Card><CardContent className="p-4">
                                <div className="size-9 rounded-lg bg-accent-soft flex items-center justify-center"><Wallet className="size-4 text-accent" /></div>
                                <p className="text-xs text-muted mt-3">Net Movement</p>
                                <p className={cn("font-num text-lg font-semibold mt-1", summary.net_balance >= 0 ? "text-success" : "text-danger")}>
                                    {summary.net_balance >= 0 ? "+" : ""}{formatBDT(summary.net_balance, false)}
                                </p>
                            </CardContent></Card>
                        </div>
                    </>
                )}

                {/* Ledger table */}
                {director ? (
                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <div>
                                    <CardTitle>Transaction Timeline</CardTitle>
                                    <CardDescription>Withdrawals, M/Y profit, and retained earnings</CardDescription>
                                </div>
                                <Button variant="outline" size="sm" onClick={() => window.open(route("exports.my-ledger") + "?director_id=" + selectedDirector + (dateFrom ? "&date_from=" + dateFrom : "") + (dateTo ? "&date_to=" + dateTo : ""))}>
                                    <Download className="size-4" /> Export
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent className="p-0">
                            <div className="rounded-lg border border-border overflow-x-auto">
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
                                                            {entry.remarks && <p className="text-xs text-muted truncate max-w-xs">{entry.remarks}</p>}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell className="text-right font-num text-success">
                                                        {entry.is_positive ? formatBDT(entry.amount_display, false) : "—"}
                                                    </TableCell>
                                                    <TableCell className="text-right font-num text-danger">
                                                        {!entry.is_positive ? formatBDT(entry.amount_display, false) : "—"}
                                                    </TableCell>
                                                    <TableCell className={cn("text-right font-num font-bold", entry.running_balance >= 0 ? "text-foreground" : "text-danger")}>
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
                            <BookOpen className="size-12 text-muted mx-auto mb-4" />
                            <h3 className="font-display text-lg font-semibold">Select a Director</h3>
                            <p className="text-sm text-muted mt-2">Choose a director above to generate their ledger report.</p>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
