import { Link } from "@inertiajs/react";
import { route } from "ziggy-js";
import {
    Card, CardContent, CardDescription, CardHeader, CardTitle,
    Button, Input, Label, Textarea, Switch,
} from "@/Components/ui";
import { Badge } from "@/Components/ui";
import { Save, X, UserPlus, Pencil, Crown, Info } from "lucide-react";
import { cn } from "@/lib/utils";

interface DirectorFormData {
    name: string;
    mobile: string;
    address: string;
    is_my: boolean;
}

interface DirectorFormProps {
    mode: "create" | "edit";
    directorId?: number;
    data: DirectorFormData;
    setData: (key: string, value: string | boolean) => void;
    submit: (url: string) => void;
    processing: boolean;
    errors: Record<string, string>;
}

export function DirectorForm({ mode, directorId, data, setData, submit, processing, errors }: DirectorFormProps) {
    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (mode === "create") {
            submit(route("directors.store"));
        } else if (directorId) {
            submit(route("directors.update", directorId));
        }
    };

    const fieldError = (field: string) => errors[field];

    return (
        <div className="max-w-3xl mx-auto space-y-6">
            {/* Header */}
            <div className="flex items-center gap-3">
                <div className="size-10 rounded-xl bg-primary-soft flex items-center justify-center">
                    {mode === "create"
                        ? <UserPlus className="size-5 text-primary" />
                        : <Pencil className="size-5 text-primary" />}
                </div>
                <div>
                    <h1 className="font-display text-2xl font-bold tracking-tight">
                        {mode === "create" ? "New Director" : `Edit ${data.name}`}
                    </h1>
                    <p className="text-sm text-muted">
                        {mode === "create"
                            ? "Add a managing partner (M/Y) to the Mudaraba system"
                            : "Update director details"}
                    </p>
                </div>
            </div>

            <form onSubmit={handleSubmit} className="space-y-6">
                {/* Basic info */}
                <Card>
                    <CardHeader>
                        <CardTitle>Director Information</CardTitle>
                        <CardDescription>The managing partner's identity and contact details.</CardDescription>
                    </CardHeader>
                    <CardContent className="grid sm:grid-cols-2 gap-4">
                        <div className="space-y-2 sm:col-span-2">
                            <Label htmlFor="name">Full Name *</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) => setData("name", e.target.value)}
                                placeholder="e.g. Mohammad"
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

                {/* Primary M/Y toggle */}
                <Card>
                    <CardHeader>
                        <CardTitle>Primary M/Y Status</CardTitle>
                        <CardDescription>
                            Designate this director as the primary Managing Owner (M/Y). Only one
                            director can hold this role at a time — setting it will unset any other.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-center justify-between p-4 rounded-lg border border-border">
                            <div className="flex items-center gap-3">
                                <div className={cn(
                                    "size-10 rounded-lg flex items-center justify-center transition-colors",
                                    data.is_my ? "bg-accent-soft" : "bg-surface-2",
                                )}>
                                    <Crown className={cn("size-5", data.is_my ? "text-accent" : "text-muted")} />
                                </div>
                                <div>
                                    <p className="font-medium">Primary M/Y</p>
                                    <p className="text-xs text-muted">
                                        {data.is_my
                                            ? "This director IS the primary managing owner"
                                            : "Toggle to make this director the primary M/Y"}
                                    </p>
                                </div>
                            </div>
                            <Switch
                                checked={data.is_my}
                                onCheckedChange={(v) => setData("is_my", v)}
                            />
                        </div>

                        {data.is_my && (
                            <div className="mt-3 flex items-start gap-2 p-3 rounded-lg bg-accent-soft/50 border border-accent/20">
                                <Info className="size-4 text-accent shrink-0 mt-0.5" />
                                <p className="text-xs text-accent-foreground">
                                    <strong>Important:</strong> Setting this director as primary M/Y will
                                    automatically unset any other director currently holding this role.
                                    The primary M/Y receives the tier-discount residual profit and 29% of
                                    retained earnings.
                                </p>
                            </div>
                        )}
                        {fieldError("is_my") && <p className="text-xs text-danger mt-2">{fieldError("is_my")}</p>}
                    </CardContent>
                </Card>

                {/* Actions */}
                <div className="flex items-center justify-end gap-3">
                    <Link href={route("directors.index")}>
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
                                {mode === "create" ? "Create Director" : "Save Changes"}
                            </>
                        )}
                    </Button>
                </div>
            </form>
        </div>
    );
}
