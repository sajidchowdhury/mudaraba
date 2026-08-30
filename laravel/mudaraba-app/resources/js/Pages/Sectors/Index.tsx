import { Head, Link, router } from "@inertiajs/react";
import { useState } from "react";
import { route } from "ziggy-js";
import { AuthenticatedLayout } from "@/Components/layout";
import {
    Card, CardContent,
    Button, Badge, Input, Label, Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from "@/Components/ui";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui";
import { ShoppingBag, Plus, Search, Eye, Pencil, Trash2 } from "lucide-react";
import { formatBDT, cn } from "@/lib/utils";
import { toast } from "sonner";

interface Sector {
    id: number;
    name: string;
    mobile: string | null;
    status: string;
    current_balance: number;
    created_at: string;
}

interface PaginatedSectors {
    data: Sector[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Props {
    sectors: PaginatedSectors;
    filters: { search?: string; status?: string };
}

const statusBadge = (status: string) => {
    if (status === "active") return <Badge variant="success">Active</Badge>;
    if (status === "inactive") return <Badge variant="warning">Inactive</Badge>;
    return <Badge variant="danger">Closed</Badge>;
};

export default function SectorsIndex({ sectors, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? "");
    const [status, setStatus] = useState(filters.status ?? "all");

    const applyFilters = () => {
        router.get(route("sectors.index"), {
            search: search || undefined,
            status: status !== "all" ? status : undefined,
        }, { preserveScroll: true, preserveState: true });
    };

    const handleDelete = (sector: Sector) => {
        if (!confirm(`Deactivate sector "${sector.name}"? This is a soft delete — financial records are preserved.`)) return;
        router.delete(route("sectors.destroy", sector.id), {
            onSuccess: () => toast.success(`Sector ${sector.name} deactivated`),
            onError: () => toast.error("Failed to deactivate sector"),
        });
    };

    return (
        <AuthenticatedLayout
            title="Sectors"
            actions={
                <Link href={route("sectors.new")}>
                    <Button size="sm"><Plus className="size-4" /> New Sector</Button>
                </Link>
            }
        >
            <Head title="Sectors" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center gap-3">
                    <div className="size-10 rounded-xl bg-primary-soft flex items-center justify-center">
                        <ShoppingBag className="size-5 text-primary" />
                    </div>
                    <div>
                        <h1 className="font-display text-2xl font-bold tracking-tight">Sectors</h1>
                        <p className="text-sm text-muted">{sectors.total} total business sectors</p>
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
                                        placeholder="Name or mobile…"
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
                                        <TableHead>Mobile</TableHead>
                                        <TableHead className="text-right">Balance</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {sectors.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={5} className="text-center py-12 text-muted">
                                                No sectors found. Try adjusting filters or create a new sector.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        sectors.data.map((sec) => (
                                            <TableRow key={sec.id}>
                                                <TableCell className="font-medium">
                                                    <Link href={route("sectors.show", sec.id)} className="hover:text-primary transition-colors">
                                                        {sec.name}
                                                    </Link>
                                                </TableCell>
                                                <TableCell className="text-muted font-num">{sec.mobile ?? "—"}</TableCell>
                                                <TableCell className={cn("text-right font-num font-medium", sec.current_balance > 0 ? "text-success" : "text-danger")}>
                                                    {formatBDT(sec.current_balance)}
                                                </TableCell>
                                                <TableCell>{statusBadge(sec.status)}</TableCell>
                                                <TableCell className="text-right">
                                                    <div className="flex items-center justify-end gap-1">
                                                        <Link href={route("sectors.show", sec.id)}>
                                                            <Button variant="ghost" size="icon" aria-label="View"><Eye className="size-4" /></Button>
                                                        </Link>
                                                        <Link href={route("sectors.edit", sec.id)}>
                                                            <Button variant="ghost" size="icon" aria-label="Edit"><Pencil className="size-4" /></Button>
                                                        </Link>
                                                        <Button variant="ghost" size="icon" aria-label="Deactivate" onClick={() => handleDelete(sec)}>
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
                        {sectors.last_page > 1 && (
                            <div className="flex items-center justify-between px-4 py-3 border-t border-border">
                                <p className="text-xs text-muted">
                                    Showing {sectors.from ?? 0}–{sectors.to ?? 0} of {sectors.total}
                                </p>
                                <div className="flex items-center gap-1">
                                    {sectors.links.map((link, i) => (
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
