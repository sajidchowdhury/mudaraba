import { Head, router } from "@inertiajs/react";
import { useState, useRef } from "react";
import { route } from "ziggy-js";
import { AuthenticatedLayout } from "@/Components/layout";
import {
    Card, CardContent, CardDescription, CardHeader, CardTitle,
    Button, Badge, Input, Label,
} from "@/Components/ui";
import {
    Zap, Download, Upload, FileSpreadsheet, CheckCircle2, AlertCircle,
    TrendingUp, Wallet, Users, Calendar,
} from "lucide-react";
import { formatBDT, formatPercent } from "@/lib/utils";
import { toast } from "sonner";
import { PageTransition } from "@/Components/common";

interface Results {
    investors_processed: number;
    investors_skipped: number;
    sectors_processed: number;
    sectors_skipped: number;
    retained_earnings_set: boolean;
    calculation_run: boolean;
    errors: string[];
    summary?: {
        total_estimated: number;
        total_actual: number;
        total_investment: number;
        total_investor_due: number;
        my_profit: number;
        my_profit_ratio: number;
        retained_total: number;
    };
    details_count?: number;
}

interface Props {
    month: string;
    flash?: {
        success?: string;
        error?: string;
        results?: Results;
    };
}

export default function AutoCalcIndex({ month, flash }: Props) {
    const [selectedMonth, setSelectedMonth] = useState(month);
    const [uploading, setUploading] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const handleDownload = () => {
        const url = route("auto-calc.template") + "?month=" + selectedMonth;
        window.open(url, "_blank");
        toast.success("Downloading template…");
    };

    const handleUpload = (e: React.FormEvent) => {
        e.preventDefault();
        const file = fileInputRef.current?.files?.[0];
        if (!file) {
            toast.error("Please select a file to upload");
            return;
        }

        setUploading(true);

        const formData = new FormData();
        formData.append("file", file);
        formData.append("month", selectedMonth);

        router.post(route("auto-calc.upload"), formData, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: (page) => {
                setUploading(false);
                toast.success("Auto-calculation completed successfully!");
                if (fileInputRef.current) fileInputRef.current.value = "";
            },
            onError: (errors) => {
                setUploading(false);
                const errorMsg = errors.file || errors.month || "Upload failed";
                toast.error(errorMsg);
            },
        });
    };

    const results = flash?.results;

    return (
        <AuthenticatedLayout title="Auto Calculation">
            <Head title="Auto Calculation" />

            <PageTransition><div className="space-y-6">
                {/* Header */}
                <div className="flex items-center gap-3">
                    <div className="size-10 rounded-xl bg-accent-soft flex items-center justify-center">
                        <Zap className="size-5 text-accent" />
                    </div>
                    <div>
                        <h1 className="font-display text-2xl font-bold tracking-tight">Auto Calculation</h1>
                        <p className="text-sm text-muted">
                            Download a template, fill in investor + sector data, upload to auto-calculate everything
                        </p>
                    </div>
                </div>

                {/* Step 1: Download Template */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Badge variant="primary" className="size-6 justify-center text-xs">1</Badge>
                            Download Template
                        </CardTitle>
                        <CardDescription>
                            Download an Excel file pre-filled with all investors, sectors, and their current balances.
                            The file has 4 sheets: Instructions, Investors, Sectors, Retained Earnings.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="flex items-center gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="month">Month</Label>
                                <Input
                                    id="month"
                                    type="month"
                                    value={selectedMonth.slice(0, 7)}
                                    onChange={(e) => setSelectedMonth(e.target.value + "-01")}
                                    className="w-40"
                                />
                            </div>
                            <Button onClick={handleDownload} className="mt-6">
                                <Download className="size-4" />
                                Download Template
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Step 2: Fill in the template */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Badge variant="primary" className="size-6 justify-center text-xs">2</Badge>
                            Fill in the Template
                        </CardTitle>
                        <CardDescription>
                            Open the downloaded Excel file and fill in the blank columns.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid sm:grid-cols-3 gap-4">
                            <div className="p-4 rounded-lg border border-border bg-surface-2/50">
                                <FileSpreadsheet className="size-5 text-primary mb-2" />
                                <p className="text-sm font-medium">Investors sheet</p>
                                <p className="text-xs text-muted mt-1">
                                    Fill in "New Investment" for each investor. Leave 0 if no new investment.
                                </p>
                            </div>
                            <div className="p-4 rounded-lg border border-border bg-surface-2/50">
                                <FileSpreadsheet className="size-5 text-primary mb-2" />
                                <p className="text-sm font-medium">Sectors sheet</p>
                                <p className="text-xs text-muted mt-1">
                                    Fill in "New Allocation", "Estimated Profit" (Z), and "Actual Profit" (X) for each sector.
                                </p>
                            </div>
                            <div className="p-4 rounded-lg border border-border bg-surface-2/50">
                                <FileSpreadsheet className="size-5 text-primary mb-2" />
                                <p className="text-sm font-medium">Retained Earnings sheet</p>
                                <p className="text-xs text-muted mt-1">
                                    Set "Total Amount" and the investor/M-Y split percentages.
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Step 3: Upload + Auto-Calculate */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Badge variant="primary" className="size-6 justify-center text-xs">3</Badge>
                            Upload & Calculate
                        </CardTitle>
                        <CardDescription>
                            Upload the filled Excel file. The system will automatically create all transactions,
                            run the 8-phase calculation engine, and show you the results.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleUpload} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="file">Select Excel file (.xlsx)</Label>
                                <input
                                    ref={fileInputRef}
                                    id="file"
                                    type="file"
                                    accept=".xlsx,.xls"
                                    className="block w-full text-sm text-muted file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:font-medium file:bg-primary file:text-primary-foreground hover:file:bg-primary/90 cursor-pointer"
                                />
                            </div>
                            <Button type="submit" disabled={uploading}>
                                {uploading ? (
                                    <>
                                        <span className="size-4 rounded-full border-2 border-white/30 border-t-white animate-spin" />
                                        Processing…
                                    </>
                                ) : (
                                    <>
                                        <Upload className="size-4" />
                                        Upload & Auto-Calculate
                                    </>
                                )}
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                {/* Results (shown after upload) */}
                {results && (
                    <Card className="border-2 border-primary/30">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <CheckCircle2 className="size-5 text-success" />
                                Auto-Calculation Results
                            </CardTitle>
                            <CardDescription>
                                {results.calculation_run
                                    ? "8-phase calculation engine completed successfully."
                                    : "Data uploaded but no calculation run (no sector profits found)."}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            {/* Processing summary */}
                            <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                <ResultStat
                                    icon={Users}
                                    label="Investors Processed"
                                    value={String(results.investors_processed)}
                                    skipped={results.investors_skipped}
                                />
                                <ResultStat
                                    icon={FileSpreadsheet}
                                    label="Sectors Processed"
                                    value={String(results.sectors_processed)}
                                    skipped={results.sectors_skipped}
                                />
                                <ResultStat
                                    icon={TrendingUp}
                                    label="Retained Earnings"
                                    value={results.retained_earnings_set ? "Set" : "Not set"}
                                />
                                <ResultStat
                                    icon={Zap}
                                    label="Calculation"
                                    value={results.calculation_run ? "Completed" : "Skipped"}
                                />
                            </div>

                            {/* Calculation summary */}
                            {results.summary && (
                                <div>
                                    <h4 className="text-sm font-semibold mb-3">8-Phase Calculation Summary</h4>
                                    <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                        <SummaryCard
                                            label="Total Investment (D181)"
                                            value={formatBDT(results.summary.total_investment)}
                                            icon={Wallet}
                                        />
                                        <SummaryCard
                                            label="Total Estimated (Z2)"
                                            value={formatBDT(results.summary.total_estimated)}
                                            icon={TrendingUp}
                                        />
                                        <SummaryCard
                                            label="Total Actual (X2)"
                                            value={formatBDT(results.summary.total_actual)}
                                            icon={TrendingUp}
                                        />
                                        <SummaryCard
                                            label="M/Y Profit (AG184)"
                                            value={formatBDT(results.summary.my_profit)}
                                            hint={formatPercent(results.summary.my_profit_ratio)}
                                            icon={Zap}
                                        />
                                    </div>
                                </div>
                            )}

                            {/* Errors */}
                            {results.errors.length > 0 && (
                                <div className="p-4 rounded-lg border border-danger/30 bg-danger/5">
                                    <div className="flex items-center gap-2 mb-2">
                                        <AlertCircle className="size-4 text-danger" />
                                        <span className="text-sm font-medium text-danger">
                                            {results.errors.length} warning(s)
                                        </span>
                                    </div>
                                    <ul className="space-y-1">
                                        {results.errors.map((err, i) => (
                                            <li key={i} className="text-xs text-muted">{err}</li>
                                        ))}
                                    </ul>
                                </div>
                            )}

                            {/* Links */}
                            <div className="flex flex-wrap gap-3">
                                <Button variant="outline" size="sm" onClick={() => router.get(route("profit.investor.index") + "?month=" + selectedMonth)}>
                                    <FileSpreadsheet className="size-4" /> View Investor Profit
                                </Button>
                                <Button variant="outline" size="sm" onClick={() => router.get(route("dashboard"))}>
                                    <TrendingUp className="size-4" /> View Dashboard
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Flash messages */}
                {flash?.error && (
                    <Card className="border-2 border-danger/30">
                        <CardContent className="p-4">
                            <div className="flex items-center gap-2 text-danger">
                                <AlertCircle className="size-5" />
                                <span className="font-medium">{flash.error}</span>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div></PageTransition>
        </AuthenticatedLayout>
    );
}

function ResultStat({ icon: Icon, label, value, skipped }: { icon: typeof Users; label: string; value: string; skipped?: number }) {
    return (
        <div className="p-4 rounded-lg border border-border bg-surface-2/50">
            <Icon className="size-4 text-muted mb-2" />
            <p className="text-xs text-muted">{label}</p>
            <p className="font-num text-lg font-bold mt-1">{value}</p>
            {skipped !== undefined && skipped > 0 && (
                <p className="text-xs text-muted mt-1">{skipped} skipped</p>
            )}
        </div>
    );
}

function SummaryCard({ label, value, hint, icon: Icon }: { label: string; value: string; hint?: string; icon: typeof Wallet }) {
    return (
        <div className="p-4 rounded-lg border border-border">
            <Icon className="size-4 text-primary mb-2" />
            <p className="text-xs text-muted">{label}</p>
            <p className="font-num text-lg font-bold mt-1">{value}</p>
            {hint && <p className="text-xs text-muted mt-1">{hint}</p>}
        </div>
    );
}
