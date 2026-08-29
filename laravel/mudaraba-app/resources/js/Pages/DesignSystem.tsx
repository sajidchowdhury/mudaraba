import { Head } from "@inertiajs/react";
import { useState } from "react";
import {
    Button, Card, CardContent, CardDescription, CardHeader, CardTitle,
    Badge, Input, Label, Textarea, Checkbox, Switch, Separator, Skeleton,
    Avatar, AvatarFallback, Progress,
    Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger,
    Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle, SheetTrigger,
    DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger,
    Tabs, TabsContent, TabsList, TabsTrigger,
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
    Tooltip, TooltipContent, TooltipProvider, TooltipTrigger,
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
    Popover, PopoverContent, PopoverTrigger,
} from "@/Components/ui";
import { ThemeToggle } from "@/Components/ThemeToggle";
import { AuthenticatedLayout } from "@/Components/layout";
import { toast } from "sonner";
import {
    Sparkles, Layers, ChevronRight, Plus, Settings, User, Trash2,
    Download, Bell, Search, Mail, Calendar, ArrowRight, CheckCircle2,
} from "lucide-react";

interface DesignSystemProps {
    appName: string;
}

export default function DesignSystem({ appName }: DesignSystemProps) {
    const [progress, setProgress] = useState(68);
    const [switchOn, setSwitchOn] = useState(true);
    const [checkbox, setCheckbox] = useState(true);
    const [tab, setTab] = useState("overview");
    const [select, setSelect] = useState("");
    const [sheetOpen, setSheetOpen] = useState(false);

    return (
        <AuthenticatedLayout
            title="Design System"
            actions={<ThemeToggle />}
            hideBreadcrumb
        >
            <main className="mx-auto max-w-7xl px-6 py-12 space-y-12">
                    {/* Hero */}
                    <section className="space-y-3">
                        <Badge variant="primary">Component Library</Badge>
                        <h1 className="font-display text-4xl md:text-5xl font-bold tracking-tight">
                            Design System Showcase
                        </h1>
                        <p className="text-lg text-muted max-w-2xl">
                            Every primitive rendered with light/dark variants. Toggle the theme
                            in the top right — the change is instant and persists across sessions.
                        </p>
                    </section>

                    {/* Color tokens */}
                    <ShowcaseSection title="Color Palette" description="Brand, semantic, and neutral tokens">
                        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                            {[
                                ["Primary", "bg-primary", "text-primary-foreground", "Emerald"],
                                ["Accent", "bg-accent", "text-accent-foreground", "Amber"],
                                ["Success", "bg-success", "text-white", "Green"],
                                ["Danger", "bg-danger", "text-white", "Red"],
                                ["Warning", "bg-warning", "text-white", "Amber"],
                                ["Info", "bg-info", "text-white", "Cyan"],
                            ].map(([name, bg, fg, hint]) => (
                                <div key={name} className={`rounded-xl p-4 ${bg} ${fg} shadow-sm`}>
                                    <p className="text-sm font-semibold">{name}</p>
                                    <p className="text-xs opacity-80 mt-1">{hint}</p>
                                </div>
                            ))}
                            <div className="rounded-xl p-4 border border-border bg-surface">
                                <p className="text-sm font-semibold">Surface</p>
                                <p className="text-xs text-muted mt-1">Card bg</p>
                            </div>
                            <div className="rounded-xl p-4 border border-border bg-surface-2">
                                <p className="text-sm font-semibold">Surface-2</p>
                                <p className="text-xs text-muted mt-1">Table header</p>
                            </div>
                            <div className="rounded-xl p-4 border border-border bg-background">
                                <p className="text-sm font-semibold">Background</p>
                                <p className="text-xs text-muted mt-1">Canvas</p>
                            </div>
                            <div className="rounded-xl p-4 border border-border bg-background">
                                <p className="text-sm font-semibold text-muted">Muted</p>
                                <p className="text-xs text-muted mt-1">Secondary text</p>
                            </div>
                        </div>
                    </ShowcaseSection>

                    {/* Typography */}
                    <ShowcaseSection title="Typography" description="Display, body, and monospace numerics">
                        <div className="space-y-3">
                            <p className="font-display text-4xl font-bold tracking-tight">Display · Inter Tight</p>
                            <p className="text-2xl font-semibold">Heading · Inter Semibold</p>
                            <p className="text-base">Body text uses Inter for clean readability across all screens.</p>
                            <p className="font-num text-base">
                                ৳ 1,635,000.00 · 29.13% · -130,000.00 · numbers mono-aligned
                            </p>
                            <p className="text-xs text-muted uppercase tracking-wider">
                                Eyebrow · Muted Uppercase
                            </p>
                        </div>
                    </ShowcaseSection>

                    {/* Buttons */}
                    <ShowcaseSection title="Buttons" description="Six variants × four sizes">
                        <div className="space-y-4">
                            <div className="flex flex-wrap gap-2">
                                <Button>Primary</Button>
                                <Button variant="secondary">Secondary</Button>
                                <Button variant="outline">Outline</Button>
                                <Button variant="ghost">Ghost</Button>
                                <Button variant="accent">Accent</Button>
                                <Button variant="danger">Danger</Button>
                            </div>
                            <div className="flex flex-wrap items-center gap-2">
                                <Button size="sm">Small</Button>
                                <Button size="md">Medium</Button>
                                <Button size="lg">Large</Button>
                                <Button size="icon" aria-label="add"><Plus /></Button>
                                <Button>
                                    With Icon <ArrowRight className="size-4" />
                                </Button>
                                <Button disabled>Disabled</Button>
                            </div>
                        </div>
                    </ShowcaseSection>

                    {/* Badges */}
                    <ShowcaseSection title="Badges" description="Status, financial tone, count">
                        <div className="flex flex-wrap gap-2">
                            <Badge>Default</Badge>
                            <Badge variant="primary">Primary</Badge>
                            <Badge variant="success">Receivable</Badge>
                            <Badge variant="danger">Payable</Badge>
                            <Badge variant="warning">Variance</Badge>
                            <Badge variant="info">Info</Badge>
                            <Badge variant="accent">Retained</Badge>
                            <Badge variant="outline">Outline</Badge>
                        </div>
                    </ShowcaseSection>

                    {/* Form controls */}
                    <ShowcaseSection title="Form Controls" description="Inputs, selects, switches, checkboxes">
                        <div className="grid md:grid-cols-2 gap-6">
                            <div className="space-y-3">
                                <div className="space-y-1.5">
                                    <Label htmlFor="name">Investor Name</Label>
                                    <Input id="name" placeholder="Kazi Afzal Noor" />
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="amount">Investment Amount (BDT)</Label>
                                    <Input id="amount" type="number" placeholder="0.00" className="font-num" />
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="remarks">Remarks</Label>
                                    <Textarea id="remarks" placeholder="Optional notes…" />
                                </div>
                                <div className="space-y-1.5">
                                    <Label>Deed Tier</Label>
                                    <Select value={select} onValueChange={setSelect}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select tier…" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="100">100% · Full share</SelectItem>
                                            <SelectItem value="80">80% · Reduced</SelectItem>
                                            <SelectItem value="60">60% · Lowest</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                            <div className="space-y-4">
                                <div className="flex items-center gap-3">
                                    <Switch checked={switchOn} onCheckedChange={setSwitchOn} />
                                    <Label>Auto-distribute retained earnings</Label>
                                </div>
                                <div className="flex items-center gap-3">
                                    <Checkbox checked={checkbox} onCheckedChange={(v) => setCheckbox(v === true)} />
                                    <Label>Lock month after finalization</Label>
                                </div>
                                <Separator />
                                <div className="space-y-2">
                                    <Label>Reconciliation progress</Label>
                                    <Progress value={progress} />
                                    <p className="text-xs text-muted">{progress}% — 17 of 17 sectors entered</p>
                                </div>
                                <Separator />
                                <div className="space-y-2">
                                    <Label>Skeleton loaders</Label>
                                    <div className="space-y-2">
                                        <Skeleton className="h-4 w-3/4" />
                                        <Skeleton className="h-4 w-1/2" />
                                        <Skeleton className="h-20 w-full rounded-xl" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </ShowcaseSection>

                    {/* Overlays */}
                    <ShowcaseSection title="Overlays" description="Dialogs, sheets, popovers, tooltips">
                        <div className="flex flex-wrap gap-3">
                            <Dialog>
                                <DialogTrigger asChild>
                                    <Button variant="outline">Open Dialog</Button>
                                </DialogTrigger>
                                <DialogContent>
                                    <DialogHeader>
                                        <DialogTitle>Finalize Month?</DialogTitle>
                                        <DialogDescription>
                                            This will lock July 2026 profit distribution. All investor
                                            due ledgers will be updated. This action can be reversed by an admin.
                                        </DialogDescription>
                                    </DialogHeader>
                                    <DialogFooter>
                                        <Button variant="ghost">Cancel</Button>
                                        <Button>Finalize Month</Button>
                                    </DialogFooter>
                                </DialogContent>
                            </Dialog>

                            <Sheet open={sheetOpen} onOpenChange={setSheetOpen}>
                                <SheetTrigger asChild>
                                    <Button variant="outline">Open Sheet</Button>
                                </SheetTrigger>
                                <SheetContent>
                                    <SheetHeader>
                                        <SheetTitle>Add Investor</SheetTitle>
                                        <SheetDescription>
                                            Slide-over form panel — perfect for quick data entry on desktop and mobile.
                                        </SheetDescription>
                                    </SheetHeader>
                                    <div className="px-6 py-4 space-y-3 flex-1 overflow-y-auto">
                                        <Input placeholder="Investor name" />
                                        <Input placeholder="Mobile" />
                                        <Select>
                                            <SelectTrigger><SelectValue placeholder="Tier" /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="100">100%</SelectItem>
                                                <SelectItem value="80">80%</SelectItem>
                                                <SelectItem value="60">60%</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <SheetFooter>
                                        <Button variant="ghost" onClick={() => setSheetOpen(false)}>Cancel</Button>
                                        <Button onClick={() => { setSheetOpen(false); toast.success("Investor added"); }}>
                                            Save
                                        </Button>
                                    </SheetFooter>
                                </SheetContent>
                            </Sheet>

                            <Popover>
                                <PopoverTrigger asChild>
                                    <Button variant="outline">Open Popover</Button>
                                </PopoverTrigger>
                                <PopoverContent>
                                    <div className="space-y-2">
                                        <p className="text-sm font-medium">Quick info</p>
                                        <p className="text-xs text-muted">Popovers are great for inline details.</p>
                                    </div>
                                </PopoverContent>
                            </Popover>

                            <TooltipProvider>
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <Button variant="ghost" size="icon"><Bell /></Button>
                                    </TooltipTrigger>
                                    <TooltipContent>3 pending reconciliations</TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        </div>
                    </ShowcaseSection>

                    {/* Dropdown menu */}
                    <ShowcaseSection title="Dropdown Menu" description="Contextual actions">
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="outline">
                                    Actions <ChevronRight className="size-4 -rotate-90" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="start">
                                <DropdownMenuLabel>Investor</DropdownMenuLabel>
                                <DropdownMenuItem><User /> View profile</DropdownMenuItem>
                                <DropdownMenuItem><Settings /> Edit</DropdownMenuItem>
                                <DropdownMenuItem><Download /> Export ledger</DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem className="text-danger focus:text-danger">
                                    <Trash2 /> Deactivate
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </ShowcaseSection>

                    {/* Tabs */}
                    <ShowcaseSection title="Tabs" description="Section navigation">
                        <Tabs value={tab} onValueChange={setTab}>
                            <TabsList>
                                <TabsTrigger value="overview">Overview</TabsTrigger>
                                <TabsTrigger value="investments">Investments</TabsTrigger>
                                <TabsTrigger value="profit">Profit History</TabsTrigger>
                                <TabsTrigger value="ledger">Ledger</TabsTrigger>
                            </TabsList>
                            <TabsContent value="overview" className="mt-4">
                                <Card><CardContent className="p-6 text-sm text-muted">Overview content placeholder for the active investor.</CardContent></Card>
                            </TabsContent>
                            <TabsContent value="investments" className="mt-4">
                                <Card><CardContent className="p-6 text-sm text-muted">Investment transaction history.</CardContent></Card>
                            </TabsContent>
                            <TabsContent value="profit" className="mt-4">
                                <Card><CardContent className="p-6 text-sm text-muted">Monthly profit distributions.</CardContent></Card>
                            </TabsContent>
                            <TabsContent value="ledger" className="mt-4">
                                <Card><CardContent className="p-6 text-sm text-muted">Running balance ledger.</CardContent></Card>
                            </TabsContent>
                        </Tabs>
                    </ShowcaseSection>

                    {/* Table */}
                    <ShowcaseSection title="Table" description="Excel-like data display with sticky header">
                        <div className="rounded-lg border border-border overflow-hidden">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Sector</TableHead>
                                        <TableHead className="text-right">Investment</TableHead>
                                        <TableHead className="text-right">Primary</TableHead>
                                        <TableHead className="text-right">Actual</TableHead>
                                        <TableHead className="text-right">Variance</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {[
                                        ["China House BD", 46155000, 750000, 750000, 0],
                                        ["Bike X", 44603550, 150000, 120000, 30000],
                                        ["SKS", 5780000, 200000, 220000, -20000],
                                        ["JFT", 9147000, 200000, 150000, 50000],
                                        ["JFMR", 8690000, 300000, 250000, 50000],
                                    ].map(([name, inv, prim, act, varc]) => (
                                        <TableRow key={name as string}>
                                            <TableCell className="font-medium">{name as string}</TableCell>
                                            <TableCell className="text-right font-num">{(inv as number).toLocaleString("en-IN")}</TableCell>
                                            <TableCell className="text-right font-num">{(prim as number).toLocaleString("en-IN")}</TableCell>
                                            <TableCell className="text-right font-num">{(act as number).toLocaleString("en-IN")}</TableCell>
                                            <TableCell className={`text-right font-num ${(varc as number) > 0 ? "text-success" : (varc as number) < 0 ? "text-danger" : "text-muted"}`}>
                                                {(varc as number) > 0 ? "+" : ""}{(varc as number).toLocaleString("en-IN")}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    </ShowcaseSection>

                    {/* Toasts */}
                    <ShowcaseSection title="Toasts" description="Sonner-powered notifications">
                        <div className="flex flex-wrap gap-2">
                            <Button variant="outline" onClick={() => toast.success("Month finalized", { description: "July 2026 ledger updated." })}>
                                <CheckCircle2 /> Success
                            </Button>
                            <Button variant="outline" onClick={() => toast.error("Reconciliation failed", { description: "Sector 'Bike X' has missing actual profit." })}>
                                Error
                            </Button>
                            <Button variant="outline" onClick={() => toast.warning("Variance detected", { description: "Primary exceeds actual by ৳ 130,000." })}>
                                Warning
                            </Button>
                            <Button variant="outline" onClick={() => toast.info("Tip", { description: "Press ⌘K to open the command palette." })}>
                                Info
                            </Button>
                            <Button variant="outline" onClick={() => toast("Investor 'Kazi Afzal Noor' updated", { description: "Deed ratio changed from 80% to 100%." })}>
                                Default
                            </Button>
                        </div>
                    </ShowcaseSection>

                    {/* Avatars */}
                    <ShowcaseSection title="Avatars" description="User + entity initials">
                        <div className="flex flex-wrap gap-3">
                            <Avatar><AvatarFallback>SA</AvatarFallback></Avatar>
                            <Avatar><AvatarFallback>KN</AvatarFallback></Avatar>
                            <Avatar><AvatarFallback className="bg-primary-soft text-primary">MY</AvatarFallback></Avatar>
                            <Avatar className="size-8"><AvatarFallback className="text-xs">SM</AvatarFallback></Avatar>
                            <Avatar className="size-14"><AvatarFallback className="text-lg">XL</AvatarFallback></Avatar>
                        </div>
                    </ShowcaseSection>

                    {/* Footer */}
                    <footer className="pt-8 border-t border-border text-center text-xs text-muted">
                        <p>Mudaraba Design System · Phase 0 · Session 0.2</p>
                        <p className="mt-1">
                            {new Set(["Button","Card","Badge","Input","Label","Textarea","Checkbox","Switch","Separator","Skeleton","Avatar","Progress","Dialog","Sheet","DropdownMenu","Popover","Tooltip","Table","Tabs","Select","Toaster"]).size} components ·
                            Light + Dark mode · WCAG AA contrast
                        </p>
                    </footer>
                </main>
        </AuthenticatedLayout>
    );
}

function ShowcaseSection({ title, description, children }: { title: string; description: string; children: React.ReactNode }) {
    return (
        <section className="space-y-3">
            <div>
                <h2 className="font-display text-2xl font-semibold tracking-tight">{title}</h2>
                <p className="text-sm text-muted">{description}</p>
            </div>
            <Card>
                <CardContent className="p-6">{children}</CardContent>
            </Card>
        </section>
    );
}
