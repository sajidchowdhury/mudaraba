import { clsx, type ClassValue } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

/** Format a number as BDT currency (Bangladeshi Taka) */
export function formatBDT(amount: number, withSymbol = true): string {
    const formatted = new Intl.NumberFormat("en-IN", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount);
    return withSymbol ? `৳ ${formatted}` : formatted;
}

/** Format a number with Indian/Bangla comma grouping (lakh system) */
export function formatNumber(amount: number): string {
    return new Intl.NumberFormat("en-IN").format(amount);
}

/** Format a percentage */
export function formatPercent(value: number, decimals = 2): string {
    return `${value.toFixed(decimals)}%`;
}
