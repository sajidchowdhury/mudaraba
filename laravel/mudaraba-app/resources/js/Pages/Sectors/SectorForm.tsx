import { Link } from "@inertiajs/react";
import { route } from "ziggy-js";
import {
    Card, CardContent, CardDescription, CardHeader, CardTitle,
    Button, Input, Label, Textarea,
} from "@/Components/ui";
import { Save, X, PlusCircle, Pencil } from "lucide-react";
import { cn } from "@/lib/utils";

interface SectorFormData {
    name: string;
    mobile: string;
    address: string;
    status: string;
}

interface SectorFormProps {
    mode: "create" | "edit";
    sectorId?: number;
    data: SectorFormData;
    setData: (key: string, value: string) => void;
    submit: (url: string) => void;
    processing: boolean;
    errors: Record<string, string>;
}

const STATUSES = [
    { value: "active", label: "Active", tone: "success" },
    { value: "inactive", label: "Inactive", tone: "warning" },
    { value: "closed", label: "Closed", tone: "danger" },
] as const;

export function SectorForm({ mode, sectorId, data, setData, submit, processing, errors }: SectorFormProps) {
    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (mode === "create") {
            submit(route("sectors.store"));
        } else if (sectorId) {
            submit(route("sectors.update", sectorId));
        }
    };

    const fieldError = (field: string) => errors[field];

    return (
        <div className="max-w-3xl mx-auto space-y-6">
            {/* Header */}
            <div className="flex items-center gap-3">
                <div className="size-10 rounded-xl bg-primary-soft flex items-center justify-center">
                    {mode === "create"
                        ? <PlusCircle className="size-5 text-primary" />
                        : <Pencil className="size-5 text-primary" />}
                </div>
                <div>
                    <h1 className="font-display text-2xl font-bold tracking-tight">
                        {mode === "create" ? "New Sector" : `Edit ${data.name}`}
                    </h1>
                    <p className="text-sm text-muted">
                        {mode === "create"
                            ? "Add a new business sector to the Mudaraba pool"
                            : "Update sector details"}
                    </p>
                </div>
            </div>

            <form onSubmit={handleSubmit} className="space-y-6">
                {/* Basic info */}
                <Card>
                    <CardHeader>
                        <CardTitle>Sector Information</CardTitle>
                        <CardDescription>The business venture where capital is deployed.</CardDescription>
                    </CardHeader>
                    <CardContent className="grid sm:grid-cols-2 gap-4">
                        <div className="space-y-2 sm:col-span-2">
                            <Label htmlFor="name">Sector Name *</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) => setData("name", e.target.value)}
                                placeholder="e.g. China House BD"
                                aria-invalid={!!fieldError("name")}
                            />
                            {fieldError("name") && <p className="text-xs text-danger">{fieldError("name")}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="mobile">Mobile</Label>
                            <Input
                                id="mobile"
                                value={data.mobile}
                                onChange={(e) => setData("mobile", e.target.value)}
                                placeholder="01XXXXXXXXX"
                                className="font-num"
                            />
                        </div>
                        <div className="space-y-2 sm:col-span-2">
                            <Label htmlFor="address">Address</Label>
                            <Textarea
                                id="address"
                                value={data.address}
                                onChange={(e) => setData("address", e.target.value)}
                                placeholder="Optional address notes…"
                                rows={2}
                            />
                        </div>
                    </CardContent>
                </Card>

                {/* Status */}
                <Card>
                    <CardHeader>
                        <CardTitle>Status *</CardTitle>
                        <CardDescription>Current operational status of this sector.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-3 gap-2">
                            {STATUSES.map((s) => (
                                <button
                                    key={s.value}
                                    type="button"
                                    onClick={() => setData("status", s.value)}
                                    className={cn(
                                        "p-3 rounded-lg border-2 text-sm font-medium transition-all",
                                        data.status === s.value
                                            ? "border-primary bg-primary-soft text-primary"
                                            : "border-border hover:border-primary/40",
                                    )}
                                >
                                    {s.label}
                                </button>
                            ))}
                        </div>
                        {fieldError("status") && <p className="text-xs text-danger mt-2">{fieldError("status")}</p>}
                    </CardContent>
                </Card>

                {/* Actions */}
                <div className="flex items-center justify-end gap-3">
                    <Link href={route("sectors.index")}>
                        <Button variant="outline" type="button">
                            <X className="size-4" /> Cancel
                        </Button>
                    </Link>
                    <Button type="submit" disabled={processing}>
                        {processing ? (
                            <>
                                <span className="size-4 rounded-full border-2 border-white/30 border-t-white animate-spin" />
                                {mode === "create" ? "Creating…" : "Saving…"}
                            </>
                        ) : (
                            <>
                                <Save className="size-4" />
                                {mode === "create" ? "Create Sector" : "Save Changes"}
                            </>
                        )}
                    </Button>
                </div>
            </form>
        </div>
    );
}
