import { Toaster as Sonner, type ToasterProps } from "sonner";
import { useTheme } from "@/Components/ThemeProvider";

export function Toaster(props: ToasterProps) {
    const { theme } = useTheme();
    return (
        <Sonner
            theme={theme as ToasterProps["theme"]}
            className="toaster group"
            toastOptions={{
                classNames: {
                    toast:
                        "group toast group-[.toaster]:bg-surface group-[.toaster]:text-foreground group-[.toaster]:border-border group-[.toaster]:shadow-[var(--shadow-lifted)]",
                    description: "group-[.toast]:text-muted",
                    actionButton: "group-[.toast]:bg-primary group-[.toast]:text-primary-foreground",
                    cancelButton: "group-[.toast]:bg-surface-2 group-[.toast]:text-muted",
                },
            }}
            {...props}
        />
    );
}
