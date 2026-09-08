import * as React from "react";
import { Slot } from "@radix-ui/react-slot";
import { cva, type VariantProps } from "class-variance-authority";
import { cn } from "@/lib/utils";

const buttonVariants = cva(
    "inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:size-4 [&_svg]:shrink-0",
    {
        variants: {
            variant: {
                primary: "bg-primary text-primary-foreground hover:brightness-105 active:scale-[0.98] shadow-sm",
                secondary: "bg-surface-2 text-foreground border border-border hover:bg-muted/40 active:scale-[0.98]",
                outline: "border border-border bg-transparent text-foreground hover:bg-surface-2 active:scale-[0.98]",
                ghost: "text-foreground hover:bg-surface-2 active:scale-[0.98]",
                danger: "bg-danger text-white hover:brightness-105 active:scale-[0.98] shadow-sm",
                accent: "bg-accent text-accent-foreground hover:brightness-105 active:scale-[0.98] shadow-sm",
            },
            size: {
                sm: "h-8 px-3 text-xs",
                md: "h-10 px-4",
                lg: "h-12 px-6 text-base",
                icon: "h-10 w-10",
            },
        },
        defaultVariants: { variant: "primary", size: "md" },
    },
);

export interface ButtonProps
    extends React.ButtonHTMLAttributes<HTMLButtonElement>,
        VariantProps<typeof buttonVariants> {
    asChild?: boolean;
}

const Button = React.forwardRef<HTMLButtonElement, ButtonProps>(
    ({ className, variant, size, asChild = false, ...props }, ref) => {
        const Comp = asChild ? Slot : "button";
        return (
            <Comp
                className={cn(buttonVariants({ variant, size, className }))}
                ref={ref}
                {...props}
            />
        );
    },
);
Button.displayName = "Button";

export { Button, buttonVariants };
