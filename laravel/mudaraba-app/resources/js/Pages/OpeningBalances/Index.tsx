import { Head, router, useForm } from "@inertiajs/react";
import { useState } from "react";
import { route } from "ziggy-js";
import { AuthenticatedLayout } from "@/Components/layout";
import {
    Card, CardContent, CardDescription, CardHeader, CardTitle,
    Button, Badge, Input, Label,
} from "@/Components/ui";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/Components/ui";
import {
    CalendarClock, Save, Crown, Users, ShoppingBag, Wallet,
} from "lucide-react";
import { formatBDT, cn } from "@/lib/utils";
import { toast } from "sonner";

interface Director {
    id: number; name: string; is_my: boolean; due: number; has_ledger: boolean;
}
interface Investor {
    id: number; name: string; reference: string | null; deed_ratio: string;
    capital_due: number; profit_due: number; has_capital_ledger: boolean; has_profit_ledger: boolean;
}
interface Sector {
    id: number; name: string;
    capital_due: number; profit_due: number; has_capital_ledger: boolean; has_profit_ledger: boolean;
}
interface Props {
    directors: Director[];
    investors: Investor[];
    sectors: Sector[];
    totals: {
        investor_capital: number; investor_profit: number;
        sector_capital: number; sector_profit: number; director_due: number;
    };
    canEdit: boolean;
}

export default function OpeningBalancesIndex({ directors, investors, sectors, totals, canEdit }: Props) {
    const [tab, setTab] = useState("my");

    // Director form
    const [directorDue, setDirectorDue] = useState<Record<number, string>>({});
    const directorForm = useForm({});

    // Investor forms
    const [investorCapital, setInvestorCapital] = useState<Record<number, string>>({});
    const [investorProfit, setInvestorProfit] = useState<Record<number, string>>({});

    // Sector forms
    const [sectorCapital, setSectorCapital] = useState<Record<number, string>>({});
    const [sectorProfit, setSectorProfit] = useState<Record<number, string>>({});

    // Initialize state from props
    useState(() => {
        directors.forEach(d => { directorDue[d.id] = String(d.due); });
        investors.forEach(i => { investorCapital[i.id] = String(i.capital_due); investorProfit[i.id] = String(i.profit_due); });
        sectors.forEach(s => { sectorCapital[s.id] = String(s.capital_due); sectorProfit[s.id] = String(s.profit_due); });
    });

    const handleDirectorSave = (director: Director) => {
        const due = parseFloat(directorDue[director.id] ?? "0") || 0;
        router.put(route("opening.director.update", director.id), { due }, {
            preserveScroll: true,
            onSuccess: () => toast.success(`${director.name} opening balance updated`),
            onError: () => toast.error("Failed to update director balance"),
        });
    };

    const handleInvestorsSave = () => {
        const items = investors.map(i => ({
            id: i.id,
            capital_due: parseFloat(investorCapital[i.id] ?? "0") || 0,
            profit_due: parseFloat(investorProfit[i.id] ?? "0") || 0,
        }));
        router.put(route("opening.investors.update"), { items }, {
            preserveScroll: true,
            onSuccess: () => toast.success(`${items.length} investor opening balances saved`),
            onError: () => toast.error("Failed to save investor balances"),
        });
    };

    const handleSectorsSave = () => {
        const items = sectors.map(s => ({
            id: s.id,
            capital_due: parseFloat(sectorCapital[s.id] ?? "0") || 0,
            profit_due: parseFloat(sectorProfit[s.id] ?? "0") || 0,
        }));
        router.put(route("opening.sectors.update"), { items }, {
            preserveScroll: true,
            onSuccess: () => toast.success(`${items.length} sector opening balances saved`),
            onError: () => toast.error("Failed to save sector balances"),
        });
    };

    return (
        <AuthenticatedLayout title="Opening Balances">
            <Head title="Opening Balances" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center gap-3">
                    <div className="size-10 rounded-xl bg-primary-soft flex items-center justify-center">
                        <CalendarClock className="size-5 text-primary" />
                    </div>
                    <div>
                        <h1 className="font-display text-2xl font-bold tracking-tight">Opening Balances</h1>
                        <p className="text-sm text-muted">Initialize due ledgers for M/Y, investors, and sectors</p>
                    </div>
                </div>

                {/* Totals summary */}
                <div className="grid grid-cols-2 lg:grid-cols-5 gap-4">
                    <TotalCard icon={Users} label="Investor Capital" value={formatBDT(totals.investor_capital)} tone="primary" />
                    <TotalCard icon={Wallet} label="Investor Profit Due" value={formatBDT(totals.investor_profit)} tone="accent" />
                    <TotalCard icon={ShoppingBag} label="Sector Capital" value={formatBDT(totals.sector_capital)} tone="info" />
                    <TotalCard icon={Wallet} label="Sector Profit Due" value={formatBDT(totals.sector_profit)} tone="warning" />
                    <TotalCard icon={Crown} label="M/Y Due" value={formatBDT(totals.director_due)} tone="success" />
                </div>

                <Tabs value={tab} onValueChange={setTab}>
                    <TabsList>
                        <TabsTrigger value="my"><Crown className="size-4 mr-1.5" /> M/Y</TabsTrigger>
                        <TabsTrigger value="investors"><Users className="size-4 mr-1.5" /> Investors</TabsTrigger>
                        <TabsTrigger value="sectors"><ShoppingBag className="size-4 mr-1.5" /> Sectors</TabsTrigger>
                    </TabsList>

                    {/* === M/Y (Director) tab === */}
                    <TabsContent value="my">
                        <Card>
                            <CardHeader>
                                <CardTitle>M/Y Opening Balances</CardTitle>
                                <CardDescription>Set the opening due for each managing partner (director).</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {directors.map(d => (
                                    <div key={d.id} className="flex items-center gap-4 p-4 rounded-lg border border-border">
                                        <div className="size-10 rounded-lg bg-surface-2 flex items-center justify-center">
                                            <Crown className={cn("size-5", d.is_my ? "text-accent" : "text-muted")} />
                                        </div>
                                        <div className="flex-1 min-w-0">
                                            <p className="font-medium flex items-center gap-2">
                                                {d.name}
                                                {d.is_my && <Badge variant="accent">Primary M/Y</Badge>}
                                            </p>
                                            <p className="text-xs text-muted">
                                                Current: {formatBDT(d.due)}
                                                {!d.has_ledger && " · No ledger yet (will be created)"}
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Label className="text-xs text-muted">Due</Label>
                                            <Input
                                                type="number"
                                                step="0.01"
                                                value={directorDue[d.id] ?? ""}
                                                onChange={(e) => setDirectorDue(prev => ({ ...prev, [d.id]: e.target.value }))}
                                                disabled={!canEdit}
                                                className="w-40 font-num text-right"
                                            />
                                            <Button size="sm" variant="outline" onClick={() => handleDirectorSave(d)} disabled={!canEdit}>
                                                <Save className="size-3.5" />
                                            </Button>
                                        </div>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* === Investors tab === */}
                    <TabsContent value="investors">
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <div>
                                        <CardTitle>Investor Opening Balances</CardTitle>
                                        <CardDescription>Set capital + profit due for each investor. Save all at once.</CardDescription>
                                    </div>
                                    <Button onClick={handleInvestorsSave} disabled={!canEdit}>
                                        <Save className="size-4" /> Save All
                                    </Button>
                                </div>
                            </CardHeader>
                            <CardContent className="p-0">
                                <div className="rounded-lg border border-border overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Investor</TableHead>
                                                <TableHead>Tier</TableHead>
                                                <TableHead className="text-right">Capital Due</TableHead>
                                                <TableHead className="text-right">Profit Due</TableHead>
                                                <TableHead className="text-center">Ledger</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {investors.map(inv => (
                                                <TableRow key={inv.id}>
                                                    <TableCell className="font-medium">
                                                        {inv.name}
                                                        {inv.reference && <span className="text-xs text-muted ml-2">({inv.reference})</span>}
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge variant={inv.deed_ratio === "100" ? "success" : inv.deed_ratio === "80" ? "warning" : "info"}>
                                                            {inv.deed_ratio}%
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell className="p-2">
                                                        <Input
                                                            type="number"
                                                            step="0.01"
                                                            value={investorCapital[inv.id] ?? ""}
                                                            onChange={(e) => setInvestorCapital(prev => ({ ...prev, [inv.id]: e.target.value }))}
                                                            disabled={!canEdit}
                                                            className="text-right font-num w-32"
                                                        />
                                                    </TableCell>
                                                    <TableCell className="p-2">
                                                        <Input
                                                            type="number"
                                                            step="0.01"
                                                            value={investorProfit[inv.id] ?? ""}
                                                            onChange={(e) => setInvestorProfit(prev => ({ ...prev, [inv.id]: e.target.value }))}
                                                            disabled={!canEdit}
                                                            className="text-right font-num w-32"
                                                        />
                                                    </TableCell>
                                                    <TableCell className="text-center">
                                                        {inv.has_capital_ledger ? (
                                                            <Badge variant="success">Set</Badge>
                                                        ) : (
                                                            <Badge variant="outline">New</Badge>
                                                        )}
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* === Sectors tab === */}
                    <TabsContent value="sectors">
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <div>
                                        <CardTitle>Sector Opening Balances</CardTitle>
                                        <CardDescription>Set capital + profit due for each sector. Save all at once.</CardDescription>
                                    </div>
                                    <Button onClick={handleSectorsSave} disabled={!canEdit}>
                                        <Save className="size-4" /> Save All
                                    </Button>
                                </div>
                            </CardHeader>
                            <CardContent className="p-0">
                                <div className="rounded-lg border border-border overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Sector</TableHead>
                                                <TableHead className="text-right">Capital Due</TableHead>
                                                <TableHead className="text-right">Profit Due</TableHead>
                                                <TableHead className="text-center">Ledger</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {sectors.map(sec => (
                                                <TableRow key={sec.id}>
                                                    <TableCell className="font-medium">{sec.name}</TableCell>
                                                    <TableCell className="p-2">
                                                        <Input
                                                            type="number"
                                                            step="0.01"
                                                            value={sectorCapital[sec.id] ?? ""}
                                                            onChange={(e) => setSectorCapital(prev => ({ ...prev, [sec.id]: e.target.value }))}
                                                            disabled={!canEdit}
                                                            className="text-right font-num w-32"
                                                        />
                                                    </TableCell>
                                                    <TableCell className="p-2">
                                                        <Input
                                                            type="number"
                                                            step="0.01"
                                                            value={sectorProfit[sec.id] ?? ""}
                                                            onChange={(e) => setSectorProfit(prev => ({ ...prev, [sec.id]: e.target.value }))}
                                                            disabled={!canEdit}
                                                            className="text-right font-num w-32"
                                                        />
                                                    </TableCell>
                                                    <TableCell className="text-center">
                                                        {sec.has_capital_ledger ? (
                                                            <Badge variant="success">Set</Badge>
                                                        ) : (
                                                            <Badge variant="outline">New</Badge>
                                                        )}
                                                    </TableCell>
                                                </TableRow>
                                            ))}
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

function TotalCard({ icon: Icon, label, value, tone }: { icon: typeof Users; label: string; value: string; tone: string }) {
    return (
        <Card>
            <CardContent className="p-4">
                <div className={cn("size-9 rounded-lg flex items-center justify-center bg-${tone}-soft")}>
                    <Icon className={cn("size-4 text-${tone}")} />
                </div>
                <p className="text-xs text-muted mt-3">{label}</p>
                <p className="font-num text-lg font-semibold mt-1">{value}</p>
            </CardContent>
        </Card>
    );
}
