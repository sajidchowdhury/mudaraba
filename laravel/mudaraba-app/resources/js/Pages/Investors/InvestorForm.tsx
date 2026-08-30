import { Link } from "@inertiajs/react";
import { route } from "ziggy-js";
import {
    Card, CardContent, CardDescription, CardHeader, CardTitle,
    Button, Input, Label, Textarea,
} from "@/Components/ui";
import { Save, X, UserPlus, Pencil, ShieldCheck } from "lucide-react";
import { cn } from "@/lib/utils";
import { toast } from "sonner";

interface InvestorFormData {
    name: string;
    reference: string;
    mobile: string;
    address: string;
    deed_ratio: string;
    start_profit_month: string;
    end_profit_month: string;
    status: string;
}

interface InvestorFormProps {
    mode: "create" | "edit";
    investorId?: number;
    data: InvestorFormData;
    setData: (key: string, value: string) => void;
    submit: (url: string) => void;
    processing: boolean;
    errors: Record<string, string>;
}

const TIERS = [
    { value: "100", label: "100%", desc: "Full share", tone: "success" },
    { value: "80", label: "80%", desc: "Reduced", tone: "warning" },
    { value: "60", label: "60%", desc: "Lowest", tone: "info" },
] as const;

const STATUSES = [
    { value: "active", label: "Active", tone: "success" },
    { value: "inactive", label: "Inactive", tone: "warning" },
    { value: "closed", label: "Closed", tone: "danger" },
] as const;

export function InvestorForm({ mode, investorId, data, setData, submit, processing, errors }: InvestorFormProps) {
    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (mode === "create") {
            submit(route("investors.store"));
        } else if (investorId) {
            submit(route("investors.update", investorId));
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
                        {mode === "create" ? "New Investor" : `Edit ${data.name}`}
                    </h1>
                    <p className="text-sm text-muted">
                        {mode === "create"
                            ? "Add a new investor to the Mudaraba pool"
                            : "Update investor details"}
                    </p>
                </div>
            </div>

            <form onSubmit={handleSubmit} className="space-y-6">
                {/* Basic info */}
                <Card>
                    <CardHeader>
                        <CardTitle>Basic Information</CardTitle>
                        <CardDescription>The investor's identity and contact details.</CardDescription>
                    </CardHeader>
                    <CardContent className="grid sm:grid-cols-2 gap-4">
                        <div className="space-y-2 sm:col-span-2">
                            <Label htmlFor="name">Full Name *</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) => setData("name", e.target.value)}
                                placeholder="e.g. Kazi Afzal Noor"
                                aria-invalid={!!fieldError("name")}
                            />
                            {fieldError("name") && <p className="text-xs text-danger">{fieldError("name")}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="reference">Reference</Label>
                            <Input
                                id="reference"
                                value={data.reference}
                                onChange={(e) => setData("reference", e.target.value)}
                                placeholder="e.g. MD, German, Family"
                            />
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

                {/* Deed tier (segmented selector) */}
                <Card>
                    <CardHeader>
                        <CardTitle>Deed Tier *</CardTitle>
                        <CardDescription>
                            Profit-sharing tier. 100% = full proportional share. Lower tiers receive
                            a reduced share; the difference accrues to the M/Y.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-3 gap-3">
                            {TIERS.map((tier) => (
                                <button
                                    key={tier.value}
                                    type="button"
                                    onClick={() => setData("deed_ratio", tier.value)}
                                    className={cn(
                                        "p-4 rounded-xl border-2 transition-all text-left",
                                        data.deed_ratio === tier.value
                                            ? "border-primary bg-primary-soft"
                                            : "border-border hover:border-primary/40 hover:bg-surface-2",
                                    )}
                                >
                                    <div className="flex items-center justify-between">
                                        <span className="font-display text-2xl font-bold">{tier.label}</span>
                                        {data.deed_ratio === tier.value && (
                                            <ShieldCheck className="size-5 text-primary" />
                                        )}
                                    </div>
                                    <p className="text-xs text-muted mt-1">{tier.desc}</p>
                                </button>
                            ))}
                        </div>
                        {fieldError("deed_ratio") && <p className="text-xs text-danger mt-2">{fieldError("deed_ratio")}</p>}
                    </CardContent>
                </Card>

                {/* Profit month window + status */}
                <Card>
                    <CardHeader>
                        <CardTitle>Profit Window & Status</CardTitle>
                        <CardDescription>
                            When the investor starts/stops earning profit, and their current account status.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="grid sm:grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="start_profit_month">Start Profit Month</Label>
                            <Input
                                id="start_profit_month"
                                type="date"
                                value={data.start_profit_month}
                                onChange={(e) => setData("start_profit_month", e.target.value)}
                                className="font-num"
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="end_profit_month">End Profit Month</Label>
                            <Input
                                id="end_profit_month"
                                type="date"
                                value={data.end_profit_month}
                                onChange={(e) => setData("end_profit_month", e.target.value)}
                                className="font-num"
                            />
                            {fieldError("end_profit_month") && <p className="text-xs text-danger">{fieldError("end_profit_month")}</p>}
                        </div>
                        <div className="space-y-2 sm:col-span-2">
                            <Label>Status *</Label>
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
                        </div>
                    </CardContent>
                </Card>

                {/* Actions */}
                <div className="flex items-center justify-end gap-3">
                    <Link href={route("investors.index")}>
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
                                {mode === "create" ? "Create Investor" : "Save Changes"}
                            </>
                        )}
                    </Button>
                </div>
            </form>
        </div>
    );
}
