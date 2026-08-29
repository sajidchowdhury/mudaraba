import * as React from "react";

type Theme = "light" | "dark" | "system";

interface ThemeProviderContext {
    theme: Theme;
    resolvedTheme: "light" | "dark";
    setTheme: (theme: Theme) => void;
    toggleTheme: () => void;
}

const ThemeContext = React.createContext<ThemeProviderContext | undefined>(undefined);

const STORAGE_KEY = "mudaraba-theme";

function getSystemTheme(): "light" | "dark" {
    if (typeof window === "undefined") return "light";
    return window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
}

function applyTheme(theme: "light" | "dark") {
    const root = document.documentElement;
    root.classList.toggle("dark", theme === "dark");
}

export function ThemeProvider({
    children,
    defaultTheme = "system",
}: {
    children: React.ReactNode;
    defaultTheme?: Theme;
}) {
    const [theme, setThemeState] = React.useState<Theme>(() => {
        if (typeof window === "undefined") return defaultTheme;
        return (localStorage.getItem(STORAGE_KEY) as Theme) || defaultTheme;
    });

    const [resolvedTheme, setResolvedTheme] = React.useState<"light" | "dark">(() => {
        if (typeof window === "undefined") return "light";
        const stored = (localStorage.getItem(STORAGE_KEY) as Theme) || defaultTheme;
        return stored === "system" ? getSystemTheme() : stored;
    });

    // Apply theme to <html> and persist
    React.useEffect(() => {
        const resolved = theme === "system" ? getSystemTheme() : theme;
        setResolvedTheme(resolved);
        applyTheme(resolved);
        localStorage.setItem(STORAGE_KEY, theme);
    }, [theme]);

    // Listen to system theme changes when in "system" mode
    React.useEffect(() => {
        if (theme !== "system") return;
        const mq = window.matchMedia("(prefers-color-scheme: dark)");
        const handler = () => {
            const sys = getSystemTheme();
            setResolvedTheme(sys);
            applyTheme(sys);
        };
        mq.addEventListener("change", handler);
        return () => mq.removeEventListener("change", handler);
    }, [theme]);

    const setTheme = React.useCallback((t: Theme) => setThemeState(t), []);

    const toggleTheme = React.useCallback(() => {
        setThemeState((prev) => (prev === "dark" ? "light" : "dark"));
    }, []);

    const value = React.useMemo(
        () => ({ theme, resolvedTheme, setTheme, toggleTheme }),
        [theme, resolvedTheme, setTheme, toggleTheme],
    );

    return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
}

export function useTheme() {
    const ctx = React.useContext(ThemeContext);
    if (!ctx) throw new Error("useTheme must be used within a ThemeProvider");
    return ctx;
}
