import { Head, Link } from "@inertiajs/react";
import { useState } from "react";
import { route } from "ziggy-js";
import { AuthenticatedLayout } from "@/Components/layout";
import {
    Card, CardContent, CardDescription, CardHeader, CardTitle,
    Button, Badge,
} from "@/Components/ui";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/Components/ui";
import {
    Pencil, ArrowLeft, Wallet, ReceiptText, Phone, MapPin,
    ArrowUpCircle, ArrowDownCircle, Crown,
} from "lucide-react";
import { formatBDT, cn } from "@/lib/utils";

interface Director {
    id: number;
    name: string;
    mobile: string | null;
    address: string | null;
    is_my: boolean;
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

interface Props {
    director: Director;
    stats: {
        current_balance: number;
        transaction_count: number;
    };
    recentTransactions: Transaction[];
}

export default function DirectorShow({ director, stats, recentTransactions }: Props) {
    const [tab, setTab] = useState("profile");

    return (
        <AuthenticatedLayout
            title={director.name}
            actions={
                <Link href={route("directors.edit", director.id)}>
                    <Button size="sm"><Pencil className="size-4" /> Edit</Button>
                </Link>
            }
        >
            <Head title={director.name} />

            <div className="space-y-6">
                <Link href={route("directors.index")} className="inline-flex items-center gap-1.5 text-sm text-muted hover:text-foreground transition-colors">
                    <ArrowLeft className="size-4" /> All Directors
                </Link>

                {/* Profile header */}
                <Card>
                    <CardContent className="p-6">
                        <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                            <div className="flex items-start gap-4">
                                <div className="size-16 rounded-2xl bg-gradient-to-br from-primary to-emerald-600 flex items-center justify-center text-white font-display text-2xl font-bold shrink-0">
                                    {director.name.split(" ").map(n => n[0]).slice(0, 2).join("").toUpperCase()}
                                </div>
                                <div>
                                    <h1 className="font-display text-2xl font-bold tracking-tight flex items-center gap-2">
                                        {director.name}
                                        {director.is_my && <Crown className="size-5 text-accent" />}
                                    </h1>
                                    <div className="flex flex-wrap items-center gap-2 mt-2">
                                        {director.is_my ? (
                                            <Badge variant="accent"><Crown className="size-3" /> Primary M/Y</Badge>
                                        ) : (
                                            <Badge variant="outline">Director</Badge>
                                        )}
                                    </div>
                                </div>
                            </div>
                            <div className="flex flex-col sm:items-end gap-1 text-sm">
                                <span className="text-muted">Created {director.created_at}</span>
                                <span className="text-muted">Last updated {director.updated_at}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Stats grid */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <StatCard icon={Wallet} label="Current Balance" value={formatBDT(stats.current_balance)} tone="primary" />
                    <StatCard icon={ReceiptText} label="Transactions" value={String(stats.transaction_count)} tone="info" />
                </div>

                {/* Tabs */}
                <Tabs value={tab} onValueChange={setTab}>
                    <TabsList>
                        <TabsTrigger value="profile">Profile</TabsTrigger>
                        <TabsTrigger value="transactions">Transactions</TabsTrigger>
                    </TabsList>

                    {/* Profile tab */}
                    <TabsContent value="profile">
                        <Card>
                            <CardHeader>
                                <CardTitle>Director Details</CardTitle>
                                <CardDescription>Full profile information for {director.name}.</CardDescription>
                            </CardHeader>
                            <CardContent className="grid sm:grid-cols-2 gap-4">
                                <DetailRow icon={Phone} label="Mobile" value={director.mobile ?? "—"} mono />
                                <DetailRow icon={MapPin} label="Address" value={director.address ?? "—"} />
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Transactions tab */}
                    <TabsContent value="transactions">
                        <Card>
                            <CardHeader>
                                <CardTitle>Recent Transactions</CardTitle>
                                <CardDescription>
                                    Last {recentTransactions.length} capital movements (withdraw/return).
                                </CardDescription>
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
                                                            {t.type === "withdraw" ? (
                                                                <Badge variant="warning"><ArrowDownCircle className="size-3" /> Withdraw</Badge>
                                                            ) : (
                                                                <Badge variant="success"><ArrowUpCircle className="size-3" /> Return</Badge>
                                                            )}
                                                        </TableCell>
                                                        <TableCell className={cn("text-right font-num font-medium", t.type === "withdraw" ? "text-danger" : "text-success")}>
                                                            {t.type === "withdraw" ? "-" : "+"}{formatBDT(t.amount, false)}
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
