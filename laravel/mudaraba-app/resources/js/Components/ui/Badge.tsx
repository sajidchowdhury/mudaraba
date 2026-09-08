import * as React from "react";
import { cva, type VariantProps } from "class-variance-authority";
import { cn } from "@/lib/utils";

const badgeVariants = cva(
    "inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-xs font-medium transition-colors",
    {
        variants: {
            variant: {
                default: "border-transparent bg-surface-2 text-foreground",
                primary: "border-transparent bg-primary-soft text-primary",
                accent: "border-transparent bg-accent-soft text-accent-foreground",
                success: "border-transparent bg-success-soft text-success",
                danger: "border-transparent bg-danger-soft text-danger",
                warning: "border-transparent bg-warning-soft text-warning",
                info: "border-transparent bg-info-soft text-info",
                outline: "border-border text-foreground",
            },
        },
        defaultVariants: { variant: "default" },
    },
);

export interface BadgeProps
    extends React.HTMLAttributes<HTMLDivElement>,
        VariantProps<typeof badgeVariants> {}

function Badge({ className, variant, ...props }: BadgeProps) {
    return <div className={cn(badgeVariants({ variant }), className)} {...props} />;
}

export { Badge, badgeVariants };
