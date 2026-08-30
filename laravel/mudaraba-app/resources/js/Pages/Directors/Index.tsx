import { Head, Link, router } from "@inertiajs/react";
import { useState } from "react";
import { route } from "ziggy-js";
import { AuthenticatedLayout } from "@/Components/layout";
import {
    Card, CardContent,
    Button, Badge, Input, Label,
} from "@/Components/ui";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui";
import { Building2, Plus, Search, Eye, Pencil, Trash2, Crown } from "lucide-react";
import { formatBDT, cn } from "@/lib/utils";
import { toast } from "sonner";

interface Director {
    id: number;
    name: string;
    mobile: string | null;
    address: string | null;
    is_my: boolean;
    current_balance: number;
    created_at: string;
}

interface PaginatedDirectors {
    data: Director[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Props {
    directors: PaginatedDirectors;
    filters: { search?: string };
}

export default function DirectorsIndex({ directors, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? "");

    const applyFilters = () => {
        router.get(route("directors.index"), {
            search: search || undefined,
        }, { preserveScroll: true, preserveState: true });
    };

    const handleDelete = (director: Director) => {
        if (!confirm(`Deactivate director "${director.name}"? This is a soft delete — financial records are preserved.`)) return;
        router.delete(route("directors.destroy", director.id), {
            onSuccess: () => toast.success(`Director ${director.name} deactivated`),
            onError: () => toast.error("Failed to deactivate director"),
        });
    };

    return (
        <AuthenticatedLayout
            title="Directors (M/Y)"
            actions={
                <Link href={route("directors.new")}>
                    <Button size="sm"><Plus className="size-4" /> New Director</Button>
                </Link>
            }
        >
            <Head title="Directors" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center gap-3">
                    <div className="size-10 rounded-xl bg-primary-soft flex items-center justify-center">
                        <Building2 className="size-5 text-primary" />
                    </div>
                    <div>
                        <h1 className="font-display text-2xl font-bold tracking-tight">Directors (M/Y)</h1>
                        <p className="text-sm text-muted">{directors.total} managing partners</p>
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
                            <div className="flex items-end">
                                <Button onClick={applyFilters} className="w-full sm:w-auto">Apply</Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Table */}
                <Card>
                    <CardContent className="p-0">
                        <div className="rounded-lg border border-border overflow-hidden">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Mobile</TableHead>
                                        <TableHead>Role</TableHead>
                                        <TableHead className="text-right">Balance</TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {directors.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={5} className="text-center py-12 text-muted">
                                                No directors found. Create a new director to get started.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        directors.data.map((dir) => (
                                            <TableRow key={dir.id}>
                                                <TableCell className="font-medium">
                                                    <Link href={route("directors.show", dir.id)} className="hover:text-primary transition-colors flex items-center gap-2">
                                                        {dir.is_my && <Crown className="size-4 text-accent" />}
                                                        {dir.name}
                                                    </Link>
                                                </TableCell>
                                                <TableCell className="text-muted font-num">{dir.mobile ?? "—"}</TableCell>
                                                <TableCell>
                                                    {dir.is_my ? (
                                                        <Badge variant="accent"><Crown className="size-3" /> Primary M/Y</Badge>
                                                    ) : (
                                                        <Badge variant="outline">Director</Badge>
                                                    )}
                                                </TableCell>
                                                <TableCell className={cn("text-right font-num font-medium", dir.current_balance > 0 ? "text-success" : "text-muted")}>
                                                    {formatBDT(dir.current_balance)}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <div className="flex items-center justify-end gap-1">
                                                        <Link href={route("directors.show", dir.id)}>
                                                            <Button variant="ghost" size="icon" aria-label="View"><Eye className="size-4" /></Button>
                                                        </Link>
                                                        <Link href={route("directors.edit", dir.id)}>
                                                            <Button variant="ghost" size="icon" aria-label="Edit"><Pencil className="size-4" /></Button>
                                                        </Link>
                                                        <Button variant="ghost" size="icon" aria-label="Deactivate" onClick={() => handleDelete(dir)}>
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
                        {directors.last_page > 1 && (
                            <div className="flex items-center justify-between px-4 py-3 border-t border-border">
                                <p className="text-xs text-muted">
                                    Showing {directors.from ?? 0}–{directors.to ?? 0} of {directors.total}
                                </p>
                                <div className="flex items-center gap-1">
                                    {directors.links.map((link, i) => (
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
