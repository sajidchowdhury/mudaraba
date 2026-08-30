import { Head, useForm } from "@inertiajs/react";
import { useState } from "react";
import { motion } from "framer-motion";
import { zodResolver } from "@hookform/resolvers/zod";
import { useForm as useRhfForm } from "react-hook-form";
import { z } from "zod";
import {
    Layers,
    Eye,
    EyeOff,
    Lock,
    User,
    ArrowRight,
    ShieldCheck,
    TrendingUp,
    Users,
    Sparkles,
    Moon,
    Sun,
} from "lucide-react";
import { Button } from "@/Components/ui/Button";
import { Input } from "@/Components/ui/Input";
import { Checkbox } from "@/Components/ui/Checkbox";
import { useTheme } from "@/Components/ThemeProvider";
import { cn } from "@/lib/utils";

interface LoginProps {
    appName: string;
}

// Zod schema — validates username + password before submit
const loginSchema = z.object({
    username: z
        .string()
        .min(1, "Username is required")
        .min(3, "Username must be at least 3 characters")
        .max(50, "Username must be less than 50 characters"),
    password: z
        .string()
        .min(1, "Password is required")
        .min(8, "Password must be at least 8 characters"),
    remember: z.boolean().default(false),
});

type LoginFormData = z.infer<typeof loginSchema>;

export default function Login({ appName }: LoginProps) {
    const [showPassword, setShowPassword] = useState(false);
    const { resolvedTheme, toggleTheme } = useTheme();

    // Inertia form (handles the actual POST in Session 2.2)
    const { data, setData, post, processing, errors: inertiaErrors } = useForm({
        username: "",
        password: "",
        remember: false,
    });

    // React Hook Form (handles client-side validation)
    const {
        register,
        handleSubmit,
        formState: { errors: rhfErrors },
    } = useRhfForm<LoginFormData>({
        resolver: zodResolver(loginSchema),
        defaultValues: { username: "", password: "", remember: false },
    });

    const onSubmit = (validated: LoginFormData) => {
        // Sync RHF-validated data into the Inertia form, then POST
        setData("username", validated.username);
        setData("password", validated.password);
        setData("remember", validated.remember);
        post("/login", {
            preserveScroll: true,
        });
    };

    // Merge client + server errors for display
    const fieldError = (field: keyof LoginFormData) =>
        rhfErrors[field]?.message || inertiaErrors[field];

    return (
        <>
            <Head title="Sign In" />

            <div className="min-h-screen flex flex-col lg:flex-row bg-background">
                {/* ============================================================
                    LEFT — Brand panel (lg+), compact header on mobile
                    ============================================================ */}
                <motion.aside
                    initial={{ opacity: 0, x: -20 }}
                    animate={{ opacity: 1, x: 0 }}
                    transition={{ duration: 0.6, ease: [0.16, 1, 0.3, 1] }}
                    className={cn(
                        "relative overflow-hidden flex flex-col justify-between",
                        "lg:w-1/2 lg:min-h-screen p-8 lg:p-12",
                        // Mobile: compact header band
                        "bg-gradient-to-br from-primary via-emerald-600 to-emerald-700 text-white",
                    )}
                >
                    {/* Decorative blurred circles */}
                    <div className="absolute inset-0 overflow-hidden pointer-events-none">
                        <div className="absolute -top-24 -right-24 size-96 rounded-full bg-white/10 blur-3xl" />
                        <div className="absolute top-1/3 -left-32 size-80 rounded-full bg-emerald-300/20 blur-3xl" />
                        <div className="absolute bottom-0 right-1/4 size-72 rounded-full bg-amber-300/10 blur-3xl" />
                    </div>

                    {/* Mobile: compact header */}
                    <div className="relative flex items-center justify-between lg:block">
                        <div className="flex items-center gap-3">
                            <motion.div
                                animate={{ scale: [1, 1.05, 1] }}
                                transition={{ duration: 2, repeat: Infinity, ease: "easeInOut" }}
                                className="size-11 lg:size-12 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center ring-1 ring-white/30"
                            >
                                <Layers className="size-6 lg:size-7 text-white" />
                            </motion.div>
                            <div className="lg:mt-0">
                                <p className="font-display text-xl font-bold leading-none">{appName}</p>
                                <p className="text-xs text-white/70 mt-1 lg:hidden">Profit Management</p>
                            </div>
                        </div>

                        {/* Theme toggle (mobile) */}
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={toggleTheme}
                            className="lg:hidden text-white hover:bg-white/10"
                            aria-label="Toggle theme"
                        >
                            {resolvedTheme === "dark" ? <Sun className="size-5" /> : <Moon className="size-5" />}
                        </Button>
                    </div>

                    {/* Desktop: hero copy */}
                    <div className="relative hidden lg:block">
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.7, delay: 0.2, ease: [0.16, 1, 0.3, 1] }}
                        >
                            <h1 className="font-display text-4xl xl:text-5xl font-bold leading-tight tracking-tight">
                                The Mudaraba profit engine,{" "}
                                <span className="text-amber-200">reimagined.</span>
                            </h1>
                            <p className="mt-6 text-lg text-white/80 max-w-md">
                                Premium money management for Islamic-finance profit-sharing pools.
                                Eight-phase calculation engine, tier-based profit sharing, retained
                                earnings — all automated.
                            </p>
                        </motion.div>

                        {/* Feature highlights */}
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.7, delay: 0.4, ease: [0.16, 1, 0.3, 1] }}
                            className="mt-12 space-y-4"
                        >
                            {[
                                { icon: TrendingUp, label: "8-phase profit engine", detail: "Excel-accurate reconciliation" },
                                { icon: Users, label: "151 investors, 17 sectors", detail: "Proportional tier-based sharing" },
                                { icon: ShieldCheck, label: "Audit-grade ledger", detail: "Every transaction traceable" },
                            ].map((feat, i) => (
                                <div key={i} className="flex items-center gap-4">
                                    <div className="size-10 rounded-xl bg-white/15 backdrop-blur-sm flex items-center justify-center ring-1 ring-white/20 shrink-0">
                                        <feat.icon className="size-5 text-white" />
                                    </div>
                                    <div>
                                        <p className="font-medium">{feat.label}</p>
                                        <p className="text-sm text-white/60">{feat.detail}</p>
                                    </div>
                                </div>
                            ))}
                        </motion.div>
                    </div>

                    {/* Footer */}
                    <div className="relative hidden lg:block text-sm text-white/50">
                        <p>© 2026 {appName} · Islamic Finance Platform</p>
                    </div>
                </motion.aside>

                {/* ============================================================
                    RIGHT — Login form
                    ============================================================ */}
                <main className="flex-1 flex flex-col lg:min-h-screen relative">
                    {/* Desktop theme toggle (top-right) */}
                    <div className="absolute top-6 right-6 hidden lg:block">
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={toggleTheme}
                            aria-label="Toggle theme"
                        >
                            {resolvedTheme === "dark" ? <Sun className="size-5" /> : <Moon className="size-5" />}
                        </Button>
                    </div>

                    <motion.div
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.6, delay: 0.3, ease: [0.16, 1, 0.3, 1] }}
                        className="flex-1 flex items-center justify-center p-6 sm:p-8 lg:p-12"
                    >
                        <div className="w-full max-w-md">
                            {/* Header */}
                            <div className="mb-8">
                                <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary-soft text-primary text-xs font-medium mb-4">
                                    <Sparkles className="size-3" />
                                    Welcome back
                                </div>
                                <h2 className="font-display text-3xl font-bold tracking-tight">
                                    Sign in to your account
                                </h2>
                                <p className="mt-2 text-muted">
                                    Enter your credentials to access the Mudaraba dashboard.
                                </p>
                            </div>

                            {/* Form */}
                            <form onSubmit={handleSubmit(onSubmit)} className="space-y-5" noValidate>
                                {/* Username */}
                                <div className="space-y-2">
                                    <label htmlFor="username" className="text-sm font-medium">
                                        Username
                                    </label>
                                    <div className="relative">
                                        <User className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground/50 pointer-events-none" />
                                        <Input
                                            id="username"
                                            type="text"
                                            autoComplete="username"
                                            placeholder="E0001"
                                            className="pl-10"
                                            aria-invalid={!!fieldError("username")}
                                            aria-describedby={fieldError("username") ? "username-error" : undefined}
                                            {...register("username")}
                                        />
                                    </div>
                                    {fieldError("username") && (
                                        <motion.p
                                            initial={{ opacity: 0, y: -4 }}
                                            animate={{ opacity: 1, y: 0 }}
                                            id="username-error"
                                            className="text-xs text-danger flex items-center gap-1"
                                        >
                                            <span className="size-1 rounded-full bg-danger" />
                                            {fieldError("username")}
                                        </motion.p>
                                    )}
                                </div>

                                {/* Password */}
                                <div className="space-y-2">
                                    <div className="flex items-center justify-between">
                                        <label htmlFor="password" className="text-sm font-medium">
                                            Password
                                        </label>
                                        <a
                                            href="#"
                                            className="text-xs text-primary hover:underline font-medium"
                                        >
                                            Forgot password?
                                        </a>
                                    </div>
                                    <div className="relative">
                                        <Lock className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground/50 pointer-events-none" />
                                        <Input
                                            id="password"
                                            type={showPassword ? "text" : "password"}
                                            autoComplete="current-password"
                                            placeholder="••••••••"
                                            className="pl-10 pr-10 font-num"
                                            aria-invalid={!!fieldError("password")}
                                            aria-describedby={fieldError("password") ? "password-error" : undefined}
                                            {...register("password")}
                                        />
                                        <button
                                            type="button"
                                            onClick={() => setShowPassword((v) => !v)}
                                            className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground/60 hover:text-foreground transition-colors focus:outline-none focus:text-foreground"
                                            aria-label={showPassword ? "Hide password" : "Show password"}
                                        >
                                            {showPassword ? (
                                                <EyeOff className="size-4" />
                                            ) : (
                                                <Eye className="size-4" />
                                            )}
                                        </button>
                                    </div>
                                    {fieldError("password") && (
                                        <motion.p
                                            initial={{ opacity: 0, y: -4 }}
                                            animate={{ opacity: 1, y: 0 }}
                                            id="password-error"
                                            className="text-xs text-danger flex items-center gap-1"
                                        >
                                            <span className="size-1 rounded-full bg-danger" />
                                            {fieldError("password")}
                                        </motion.p>
                                    )}
                                </div>

                                {/* Remember me */}
                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="remember"
                                        {...register("remember")}
                                    />
                                    <label htmlFor="remember" className="text-sm text-muted cursor-pointer select-none">
                                        Keep me signed in for 30 days
                                    </label>
                                </div>

                                {/* Submit */}
                                <Button
                                    type="submit"
                                    size="lg"
                                    className="w-full"
                                    disabled={processing}
                                >
                                    {processing ? (
                                        <>
                                            <span className="size-4 rounded-full border-2 border-white/30 border-t-white animate-spin" />
                                            Signing in…
                                        </>
                                    ) : (
                                        <>
                                            Sign in
                                            <ArrowRight className="size-4" />
                                        </>
                                    )}
                                </Button>
                            </form>

                            {/* Demo hint */}
                            <div className="mt-8 p-4 rounded-lg border border-border bg-surface-2/50">
                                <p className="text-xs text-muted text-center">
                                    <span className="font-medium text-foreground">Demo:</span>{" "}
                                    Authentication backend is wired up in Session 2.2.
                                    This page currently demonstrates the premium UI only.
                                </p>
                            </div>
                        </div>
                    </motion.div>

                    {/* Footer */}
                    <footer className="py-4 px-6 text-center text-xs text-muted border-t border-border">
                        <p>{appName} · v0.2.0 · Phase 2</p>
                    </footer>
                </main>
            </div>
        </>
    );
}
