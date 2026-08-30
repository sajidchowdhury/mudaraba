import { Head, Link, router } from "@inertiajs/react";
import { route } from "ziggy-js";
import { useState, useMemo } from "react";
import { AuthenticatedLayout } from "@/Components/layout";
import {
    Card, CardContent, CardDescription, CardHeader, CardTitle,
    Button, Badge, Input, Label, Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from "@/Components/ui";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui";
import { Users, Plus, Search, ChevronLeft, ChevronRight, Eye, Pencil, Trash2 } from "lucide-react";
import { formatBDT, cn } from "@/lib/utils";
import { toast } from "sonner";

interface Investor {
    id: number;
    name: string;
    reference: string | null;
    mobile: string | null;
    deed_ratio: string;
    status: string;
    start_profit_month: string | null;
    end_profit_month: string | null;
    current_balance: number;
    created_at: string;
}

interface PaginatedInvestors {
    data: Investor[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Props {
    investors: PaginatedInvestors;
    filters: { search?: string; status?: string; deed_ratio?: string };
}

const tierBadge = (ratio: string) => {
    if (ratio === "100") return <Badge variant="success">Tier 100%</Badge>;
    if (ratio === "80") return <Badge variant="warning">Tier 80%</Badge>;
    return <Badge variant="info">Tier 60%</Badge>;
};

const statusBadge = (status: string) => {
    if (status === "active") return <Badge variant="success">Active</Badge>;
    if (status === "inactive") return <Badge variant="warning">Inactive</Badge>;
    return <Badge variant="danger">Closed</Badge>;
};

export default function InvestorsIndex({ investors, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? "");
    const [status, setStatus] = useState(filters.status ?? "all");
    const [deedRatio, setDeedRatio] = useState(filters.deed_ratio ?? "all");

    const applyFilters = () => {
        router.get(route("investors.index"), {
            search: search || undefined,
            status: status !== "all" ? status : undefined,
            deed_ratio: deedRatio !== "all" ? deedRatio : undefined,
        }, { preserveScroll: true, preserveState: true });
    };

    const handleDelete = (investor: Investor) => {
        if (!confirm(`Deactivate investor "${investor.name}"? This is a soft delete — financial records are preserved.`)) return;
        router.delete(route("investors.destroy", investor.id), {
            onSuccess: () => toast.success(`Investor ${investor.name} deactivated`),
            onError: () => toast.error("Failed to deactivate investor"),
        });
    };

    return (
        <AuthenticatedLayout
            title="Investors"
            actions={
                <Link href={route("investors.new")}>
                    <Button size="sm"><Plus className="size-4" /> New Investor</Button>
                </Link>
            }
        >
            <Head title="Investors" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center gap-3">
                    <div className="size-10 rounded-xl bg-primary-soft flex items-center justify-center">
                        <Users className="size-5 text-primary" />
                    </div>
                    <div>
                        <h1 className="font-display text-2xl font-bold tracking-tight">Investors</h1>
                        <p className="text-sm text-muted">{investors.total} total investors across 3 deed tiers</p>
                    </div>
                </div>

                {/* Filters */}
                <Card>
                    <CardContent className="p-4">
                        <div className="flex flex-col sm:flex-row gap-3">
                            <div className="flex-1 space-y-1.5">
                                <Label htmlFor="search">Search</Label>
                                <div className="relative">
                                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground/50" />
                                    <Input
                                        id="search"
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        onKeyDown={(e) => e.key === "Enter" && applyFilters()}
                                        placeholder="Name, reference, or mobile…"
                                        className="pl-10"
                                    />
                                </div>
                            </div>
                            <div className="w-full sm:w-48 space-y-1.5">
                                <Label>Status</Label>
                                <Select value={status} onValueChange={setStatus}>
                                    <SelectTrigger><SelectValue placeholder="All statuses" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All statuses</SelectItem>
                                        <SelectItem value="active">Active</SelectItem>
                                        <SelectItem value="inactive">Inactive</SelectItem>
                                        <SelectItem value="closed">Closed</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="w-full sm:w-48 space-y-1.5">
                                <Label>Deed Tier</Label>
                                <Select value={deedRatio} onValueChange={setDeedRatio}>
                                    <SelectTrigger><SelectValue placeholder="All tiers" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All tiers</SelectItem>
                                        <SelectItem value="100">100% — Full share</SelectItem>
                                        <SelectItem value="80">80% — Reduced</SelectItem>
                                        <SelectItem value="60">60% — Lowest</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="flex items-end">
                                <Button onClick={applyFilters} className="w-full sm:w-auto">Apply</Button>
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
                                        <TableHead>Name</TableHead>
                                        <TableHead>Reference</TableHead>
                                        <TableHead>Mobile</TableHead>
                                        <TableHead>Tier</TableHead>
                                        <TableHead className="text-right">Balance</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {investors.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={7} className="text-center py-12 text-muted">
                                                No investors found. Try adjusting filters or create a new investor.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        investors.data.map((inv) => (
                                            <TableRow key={inv.id}>
                                                <TableCell className="font-medium">
                                                    <Link href={route("investors.show", inv.id)} className="hover:text-primary transition-colors">
                                                        {inv.name}
                                                    </Link>
                                                </TableCell>
                                                <TableCell className="text-muted">{inv.reference ?? "—"}</TableCell>
                                                <TableCell className="text-muted font-num">{inv.mobile ?? "—"}</TableCell>
                                                <TableCell>{tierBadge(inv.deed_ratio)}</TableCell>
                                                <TableCell className={cn("text-right font-num font-medium", inv.current_balance > 0 ? "text-success" : "text-danger")}>
                                                    {formatBDT(inv.current_balance)}
                                                </TableCell>
                                                <TableCell>{statusBadge(inv.status)}</TableCell>
                                                <TableCell className="text-right">
                                                    <div className="flex items-center justify-end gap-1">
                                                        <Link href={route("investors.show", inv.id)}>
                                                            <Button variant="ghost" size="icon" aria-label="View"><Eye className="size-4" /></Button>
                                                        </Link>
                                                        <Link href={route("investors.edit", inv.id)}>
                                                            <Button variant="ghost" size="icon" aria-label="Edit"><Pencil className="size-4" /></Button>
                                                        </Link>
                                                        <Button variant="ghost" size="icon" aria-label="Deactivate" onClick={() => handleDelete(inv)}>
                                                            <Trash2 className="size-4 text-danger" />
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {/* Pagination */}
                        {investors.last_page > 1 && (
                            <div className="flex items-center justify-between px-4 py-3 border-t border-border">
                                <p className="text-xs text-muted">
                                    Showing {investors.from ?? 0}–{investors.to ?? 0} of {investors.total}
                                </p>
                                <div className="flex items-center gap-1">
                                    {investors.links.map((link, i) => (
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
        </AuthenticatedLayout>
    );
}
