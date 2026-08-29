import * as React from "react";
import { ChevronDown, Calendar } from "lucide-react";
import { Button } from "@/Components/ui/Button";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/Components/ui/DropdownMenu";
import { cn } from "@/lib/utils";

interface MonthSwitcherProps {
    /** Currently selected month in YYYY-MM format */
    value: string;
    onChange: (month: string) => void;
    /** Recent months to show (default: last 12) */
    months?: string[];
    className?: string;
}

function lastNMonths(n: number): string[] {
    const now = new Date();
    const out: string[] = [];
    for (let i = 0; i < n; i++) {
        const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
        out.push(`${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}`);
    }
    return out;
}

function formatMonth(ym: string): string {
    const [y, m] = ym.split("-").map(Number);
    return new Date(y, m - 1, 1).toLocaleDateString("en-US", {
        month: "long",
        year: "numeric",
    });
}

export function MonthSwitcher({
    value,
    onChange,
    months,
    className,
}: MonthSwitcherProps) {
    const monthList = months ?? lastNMonths(12);
    const current = formatMonth(value);

    return (
        <div className={cn("flex items-center", className)}>
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button variant="outline" size="md" className="gap-2 min-w-[180px] justify-between">
                        <span className="flex items-center gap-2">
                            <Calendar className="size-4 text-primary" />
                            <span className="font-medium">{current}</span>
                        </span>
                        <ChevronDown className="size-4 opacity-50" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="start" className="min-w-[220px]">
                    <DropdownMenuLabel>Active Month</DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    <DropdownMenuRadioGroup value={value} onValueChange={onChange}>
                        {monthList.map((m) => (
                            <DropdownMenuRadioItem key={m} value={m}>
                                {formatMonth(m)}
                                {m === value && (
                                    <span className="ml-auto text-[10px] text-primary font-num">●</span>
                                )}
                            </DropdownMenuRadioItem>
                        ))}
                    </DropdownMenuRadioGroup>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    );
}
