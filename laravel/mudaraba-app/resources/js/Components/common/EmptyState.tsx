import { ReactNode } from "react";
import { LucideIcon } from "lucide-react";
import { cn } from "@/lib/utils";
import { Button } from "@/Components/ui";

interface EmptyStateProps {
    icon: LucideIcon;
    title: string;
    description?: string;
    action?: {
        label: string;
        href?: string;
        onClick?: () => void;
    };
    className?: string;
}

/**
 * Premium empty state with icon, title, description, and optional CTA.
 * Used when a page has no data to display.
 */
export function EmptyState({ icon: Icon, title, description, action, className }: EmptyStateProps) {
    return (
        <div className={cn("flex flex-col items-center justify-center py-16 px-6 text-center", className)}>
            <div className="relative mb-6">
                <div className="absolute inset-0 bg-primary/10 blur-2xl rounded-full" />
                <div className="relative size-16 rounded-2xl bg-primary-soft flex items-center justify-center">
                    <Icon className="size-8 text-primary" />
                </div>
            </div>
            <h3 className="font-display text-lg font-semibold tracking-tight">{title}</h3>
            {description && (
                <p className="text-sm text-muted mt-2 max-w-sm">{description}</p>
            )}
            {action && (
                <div className="mt-6">
                    {action.href ? (
                        <a href={action.href}>
                            <Button>{action.label}</Button>
                        </a>
                    ) : (
                        <Button onClick={action.onClick}>{action.label}</Button>
                    )}
                </div>
            )}
        </div>
    );
}
