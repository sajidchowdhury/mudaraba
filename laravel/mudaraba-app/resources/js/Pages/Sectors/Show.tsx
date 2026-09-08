import { Head, Link, useForm } from "@inertiajs/react";
import { useState } from "react";
import { route } from "ziggy-js";
import { AuthenticatedLayout } from "@/Components/layout";
import {
    Card, CardContent, CardDescription, CardHeader, CardTitle,
    Button, Badge, Input, Label, Select, Textarea,
} from "@/Components/ui";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/Components/ui";
import {
    Pencil, ArrowLeft, Wallet, ReceiptText, Phone, MapPin,
    ArrowUpCircle, ArrowDownCircle, TrendingUp, Plus,
} from "lucide-react";
import { formatBDT, cn } from "@/lib/utils";

interface Sector {
    id: number;
    name: string;
    mobile: string | null;
    address: string | null;
    status: string;
    created_at: string;
    updated_at: string;
}

interface Investment {
    id: number;
    amount: number;
    type: string;
    transaction_date: string;
    remarks: string | null;
}

interface ProfitRecord {
    profit_month: string;
    estimated_profit: number;
    actual_profit: number;
    advance_difference: number;
    status: string;
}

interface Props {
    sector: Sector;
    stats: {
        current_balance: number;
        profit_due: number;
        investment_count: number;
        profit_records: number;
    };
    recentInvestments: Investment[];
    recentProfit: ProfitRecord[];
}

export default function SectorShow({ sector, stats, recentInvestments, recentProfit }: Props) {
    const [tab, setTab] = useState("profile");

    // ── Sector Investment form (add money to / withdraw from this sector) ──
    const { data, setData, post, processing, reset } = useForm({
        sector_id: sector.id,
        amount: "",
        type: "add",
        transaction_date: new Date().toISOString().slice(0, 10),
        remarks: "",
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route("sector-investments.store"), {
            preserveScroll: true,
            onSuccess: () => reset("amount", "remarks"),
        });
    };

    return (
        <AuthenticatedLayout
            title={sector.name}
            actions={
                <Link href={route("sectors.edit", sector.id)}>
                    <Button size="sm"><Pencil className="size-4" /> Edit</Button>
                </Link>
            }
        >
            <Head title={sector.name} />

            <div className="space-y-6">
                <Link href={route("sectors.index")} className="inline-flex items-center gap-1.5 text-sm text-muted hover:text-foreground transition-colors">
                    <ArrowLeft className="size-4" /> All Sectors
                </Link>

                {/* Profile header */}
                <Card>
                    <CardContent className="p-6">
                        <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                            <div className="flex items-start gap-4">
                                <div className="size-16 rounded-2xl bg-gradient-to-br from-primary to-emerald-600 flex items-center justify-center text-white font-display text-2xl font-bold shrink-0">
                                    {sector.name.split(" ").map(n => n[0]).slice(0, 2).join("").toUpperCase()}
                                </div>
                                <div>
                                    <h1 className="font-display text-2xl font-bold tracking-tight">{sector.name}</h1>
                                    <div className="flex flex-wrap items-center gap-2 mt-2">
                                        <Badge variant={sector.status === "active" ? "success" : sector.status === "inactive" ? "warning" : "danger"}>
                                            {sector.status}
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                            <div className="flex flex-col sm:items-end gap-1 text-sm">
                                <span className="text-muted">Created {sector.created_at}</span>
                                <span className="text-muted">Last updated {sector.updated_at}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Stats grid */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <StatCard icon={Wallet} label="Current Balance" value={formatBDT(stats.current_balance)} tone="primary" />
                    <StatCard icon={ReceiptText} label="Profit Due" value={formatBDT(stats.profit_due)} tone="accent" />
                    <StatCard icon={ArrowUpCircle} label="Investments" value={String(stats.investment_count)} tone="info" />
                    <StatCard icon={TrendingUp} label="Profit Records" value={String(stats.profit_records)} tone="success" />
                </div>

                {/* Tabs */}
                <Tabs value={tab} onValueChange={setTab}>
                    <TabsList>
                        <TabsTrigger value="profile">Profile</TabsTrigger>
                        <TabsTrigger value="investments">Investments</TabsTrigger>
                        <TabsTrigger value="profit">Profit History</TabsTrigger>
                    </TabsList>

                    {/* Profile tab */}
                    <TabsContent value="profile">
                        <Card>
                            <CardHeader>
                                <CardTitle>Sector Details</CardTitle>
                                <CardDescription>Full profile information for {sector.name}.</CardDescription>
                            </CardHeader>
                            <CardContent className="grid sm:grid-cols-2 gap-4">
                                <DetailRow icon={Phone} label="Mobile" value={sector.mobile ?? "—"} mono />
                                <DetailRow icon={MapPin} label="Address" value={sector.address ?? "—"} />
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Investments tab */}
                    <TabsContent value="investments">
                        {/* Add/Withdraw form */}
                        <Card className="mb-4">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Plus className="size-5 text-primary" />
                                    Allocate / Withdraw Capital
                                </CardTitle>
                                <CardDescription>
                                    Assign investor funds to this sector or withdraw capital back to the M/Y pool.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={handleSubmit} className="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
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
                                        <Label htmlFor="remarks">Remarks (optional)</Label>
                                        <Input
                                            id="remarks"
                                            type="text"
                                            placeholder="e.g. January allocation"
                                            value={data.remarks}
                                            onChange={(e) => setData("remarks", e.target.value)}
                                        />
                                    </div>
                                    <div className="sm:col-span-2 lg:col-span-4 flex justify-end">
                                        <Button type="submit" disabled={processing || !data.amount}>
                                            {data.type === "add" ? (
                                                <><ArrowUpCircle className="size-4" /> Allocate to {sector.name}</>
                                            ) : (
                                                <><ArrowDownCircle className="size-4" /> Withdraw from {sector.name}</>
                                            )}
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>

                        {/* Investment history */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Recent Investments</CardTitle>
                                <CardDescription>Last {recentInvestments.length} capital movements (add/withdraw).</CardDescription>
                            </CardHeader>
                            <CardContent className="p-0">
                                <div className="rounded-lg border border-border overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Date</TableHead>
                                                <TableHead>Type</TableHead>
                                                <TableHead className="text-right">Amount</TableHead>
                                                <TableHead>Remarks</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {recentInvestments.length === 0 ? (
                                                <TableRow>
                                                    <TableCell colSpan={4} className="text-center py-8 text-muted">No investments yet.</TableCell>
                                                </TableRow>
                                            ) : (
                                                recentInvestments.map((inv) => (
                                                    <TableRow key={inv.id}>
                                                        <TableCell className="font-num">{inv.transaction_date}</TableCell>
                                                        <TableCell>
                                                            {inv.type === "add" ? (
                                                                <Badge variant="success"><ArrowUpCircle className="size-3" /> Add</Badge>
                                                            ) : (
                                                                <Badge variant="danger"><ArrowDownCircle className="size-3" /> Withdraw</Badge>
                                                            )}
                                                        </TableCell>
                                                        <TableCell className={cn("text-right font-num font-medium", inv.type === "add" ? "text-success" : "text-danger")}>
                                                            {inv.type === "add" ? "+" : "-"}{formatBDT(inv.amount, false)}
                                                        </TableCell>
                                                        <TableCell className="text-muted">{inv.remarks ?? "—"}</TableCell>
                                                    </TableRow>
                                                ))
                                            )}
                                        </TableBody>
                                    </Table>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Profit history tab */}
                    <TabsContent value="profit">
                        <Card>
                            <CardHeader>
                                <CardTitle>Profit History</CardTitle>
                                <CardDescription>Last {recentProfit.length} monthly profit entries (estimated vs actual).</CardDescription>
                            </CardHeader>
                            <CardContent className="p-0">
                                <div className="rounded-lg border border-border overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Month</TableHead>
                                                <TableHead className="text-right">Estimated</TableHead>
                                                <TableHead className="text-right">Actual</TableHead>
                                                <TableHead className="text-right">Variance</TableHead>
                                                <TableHead>Status</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {recentProfit.length === 0 ? (
                                                <TableRow>
                                                    <TableCell colSpan={5} className="text-center py-8 text-muted">No profit records yet.</TableCell>
                                                </TableRow>
                                            ) : (
                                                recentProfit.map((p, i) => (
                                                    <TableRow key={i}>
                                                        <TableCell className="font-num">{p.profit_month}</TableCell>
                                                        <TableCell className="text-right font-num">{formatBDT(p.estimated_profit, false)}</TableCell>
                                                        <TableCell className="text-right font-num text-success">{formatBDT(p.actual_profit, false)}</TableCell>
                                                        <TableCell className={cn("text-right font-num", p.advance_difference > 0 ? "text-danger" : p.advance_difference < 0 ? "text-success" : "text-muted")}>
                                                            {formatBDT(p.advance_difference, false)}
                                                        </TableCell>
                                                        <TableCell>
                                                            <Badge variant={p.status === "finalized" ? "success" : "warning"}>
                                                                {p.status}
                                                            </Badge>
                                                        </TableCell>
                                                    </TableRow>
                                                ))
                                            )}
                                        </TableBody>
                                    </Table>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </AuthenticatedLayout>
    );
}

function StatCard({ icon: Icon, label, value, tone }: { icon: typeof Wallet; label: string; value: string; tone: string }) {
    return (
        <Card>
            <CardContent className="p-4">
                <div className="flex items-center justify-between">
                    <div className={cn("size-9 rounded-lg flex items-center justify-center bg-${tone}-soft")}>
                        <Icon className={cn("size-4 text-${tone}")} />
                    </div>
                </div>
                <p className="text-xs text-muted mt-3">{label}</p>
                <p className="font-num text-xl font-semibold mt-1">{value}</p>
            </CardContent>
        </Card>
    );
}

function DetailRow({ icon: Icon, label, value, mono }: { icon: typeof Phone; label: string; value: string; mono?: boolean }) {
    return (
        <div className="flex items-start gap-3">
            <div className="size-8 rounded-lg bg-surface-2 flex items-center justify-center shrink-0">
                <Icon className="size-4 text-muted" />
            </div>
            <div className="min-w-0">
                <p className="text-xs text-muted">{label}</p>
                <p className={cn("text-sm font-medium truncate", mono && "font-num")}>{value}</p>
            </div>
        </div>
    );
}
