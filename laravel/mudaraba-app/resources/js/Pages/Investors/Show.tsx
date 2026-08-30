import { Head, Link } from "@inertiajs/react";
import { route } from "ziggy-js";
import { useState } from "react";
import { AuthenticatedLayout } from "@/Components/layout";
import {
    Card, CardContent, CardDescription, CardHeader, CardTitle,
    Button, Badge,
} from "@/Components/ui";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/Components/ui";
import {
    Pencil, ArrowLeft, Wallet, TrendingUp, ReceiptText, Phone, MapPin, Calendar,
    ArrowUpCircle, ArrowDownCircle,
} from "lucide-react";
import { formatBDT, cn } from "@/lib/utils";

interface Investor {
    id: number;
    name: string;
    reference: string | null;
    mobile: string | null;
    address: string | null;
    deed_ratio: string;
    status: string;
    start_profit_month: string | null;
    end_profit_month: string | null;
    created_at: string;
    updated_at: string;
}

interface Transaction {
    id: number;
    amount: number;
    type: string;
    transaction_date: string;
    remarks: string | null;
}

interface ProfitRecord {
    profit_month: string;
    investment: number;
    actual_profit_due: number;
    advance_difference: number;
    net_settlement: number;
}

interface Props {
    investor: Investor;
    stats: {
        current_balance: number;
        profit_due: number;
        transaction_count: number;
        profit_records: number;
    };
    recentTransactions: Transaction[];
    recentProfit: ProfitRecord[];
}

const tierBadge = (ratio: string) => {
    if (ratio === "100") return <Badge variant="success">Tier 100% · Full</Badge>;
    if (ratio === "80") return <Badge variant="warning">Tier 80% · Reduced</Badge>;
    return <Badge variant="info">Tier 60% · Lowest</Badge>;
};

export default function InvestorShow({ investor, stats, recentTransactions, recentProfit }: Props) {
    const [tab, setTab] = useState("profile");

    return (
        <AuthenticatedLayout
            title={investor.name}
            actions={
                <Link href={route("investors.edit", investor.id)}>
                    <Button size="sm"><Pencil className="size-4" /> Edit</Button>
                </Link>
            }
        >
            <Head title={investor.name} />

            <div className="space-y-6">
                {/* Breadcrumb back-link */}
                <Link href={route("investors.index")} className="inline-flex items-center gap-1.5 text-sm text-muted hover:text-foreground transition-colors">
                    <ArrowLeft className="size-4" /> All Investors
                </Link>

                {/* Profile header */}
                <Card>
                    <CardContent className="p-6">
                        <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                            <div className="flex items-start gap-4">
                                <div className="size-16 rounded-2xl bg-gradient-to-br from-primary to-emerald-600 flex items-center justify-center text-white font-display text-2xl font-bold shrink-0">
                                    {investor.name.split(" ").map(n => n[0]).slice(0, 2).join("").toUpperCase()}
                                </div>
                                <div>
                                    <h1 className="font-display text-2xl font-bold tracking-tight">{investor.name}</h1>
                                    <div className="flex flex-wrap items-center gap-2 mt-2">
                                        {tierBadge(investor.deed_ratio)}
                                        <Badge variant={investor.status === "active" ? "success" : investor.status === "inactive" ? "warning" : "danger"}>
                                            {investor.status}
                                        </Badge>
                                        {investor.reference && (
                                            <Badge variant="outline">Ref: {investor.reference}</Badge>
                                        )}
                                    </div>
                                </div>
                            </div>
                            <div className="flex flex-col sm:items-end gap-1 text-sm">
                                <span className="text-muted">Created {investor.created_at}</span>
                                <span className="text-muted">Last updated {investor.updated_at}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Stats grid */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <StatCard icon={Wallet} label="Current Balance" value={formatBDT(stats.current_balance)} tone="primary" />
                    <StatCard icon={ReceiptText} label="Profit Due" value={formatBDT(stats.profit_due)} tone="accent" />
                    <StatCard icon={ArrowUpCircle} label="Transactions" value={String(stats.transaction_count)} tone="info" />
                    <StatCard icon={TrendingUp} label="Profit Records" value={String(stats.profit_records)} tone="success" />
                </div>

                {/* Tabs */}
                <Tabs value={tab} onValueChange={setTab}>
                    <TabsList>
                        <TabsTrigger value="profile">Profile</TabsTrigger>
                        <TabsTrigger value="transactions">Transactions</TabsTrigger>
                        <TabsTrigger value="profit">Profit History</TabsTrigger>
                    </TabsList>

                    {/* Profile tab */}
                    <TabsContent value="profile">
                        <Card>
                            <CardHeader>
                                <CardTitle>Investor Details</CardTitle>
                                <CardDescription>Full profile information for {investor.name}.</CardDescription>
                            </CardHeader>
                            <CardContent className="grid sm:grid-cols-2 gap-4">
                                <DetailRow icon={Phone} label="Mobile" value={investor.mobile ?? "—"} mono />
                                <DetailRow icon={MapPin} label="Address" value={investor.address ?? "—"} />
                                <DetailRow icon={Calendar} label="Start Profit Month" value={investor.start_profit_month ?? "—"} mono />
                                <DetailRow icon={Calendar} label="End Profit Month" value={investor.end_profit_month ?? "—"} mono />
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Transactions tab */}
                    <TabsContent value="transactions">
                        <Card>
                            <CardHeader>
                                <CardTitle>Recent Transactions</CardTitle>
                                <CardDescription>Last {recentTransactions.length} capital movements (add/withdraw).</CardDescription>
                            </CardHeader>
                            <CardContent className="p-0">
                                <div className="rounded-lg border border-border overflow-hidden">
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
                                            {recentTransactions.length === 0 ? (
                                                <TableRow>
                                                    <TableCell colSpan={4} className="text-center py-8 text-muted">No transactions yet.</TableCell>
                                                </TableRow>
                                            ) : (
                                                recentTransactions.map((t) => (
                                                    <TableRow key={t.id}>
                                                        <TableCell className="font-num">{t.transaction_date}</TableCell>
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
                                <CardDescription>Last {recentProfit.length} monthly profit distributions.</CardDescription>
                            </CardHeader>
                            <CardContent className="p-0">
                                <div className="rounded-lg border border-border overflow-hidden">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Month</TableHead>
                                                <TableHead className="text-right">Investment</TableHead>
                                                <TableHead className="text-right">Profit Due</TableHead>
                                                <TableHead className="text-right">Advance Diff</TableHead>
                                                <TableHead className="text-right">Net Settlement</TableHead>
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
                                                        <TableCell className="text-right font-num">{formatBDT(p.investment, false)}</TableCell>
                                                        <TableCell className="text-right font-num text-success">{formatBDT(p.actual_profit_due, false)}</TableCell>
                                                        <TableCell className={cn("text-right font-num", p.advance_difference > 0 ? "text-danger" : p.advance_difference < 0 ? "text-success" : "text-muted")}>
                                                            {formatBDT(p.advance_difference, false)}
                                                        </TableCell>
                                                        <TableCell className={cn("text-right font-num font-medium", p.net_settlement > 0 ? "text-danger" : p.net_settlement < 0 ? "text-success" : "text-muted")}>
                                                            {formatBDT(p.net_settlement, false)}
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
