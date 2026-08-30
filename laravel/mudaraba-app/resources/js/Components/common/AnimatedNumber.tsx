import { useState, useEffect, useRef } from "react";

interface AnimatedNumberProps {
    value: number;
    duration?: number;
    format?: (n: number) => string;
    className?: string;
}

/**
 * Animates a number from 0 to the target value using requestAnimationFrame
 * with an ease-out cubic curve. No external library needed.
 */
export function AnimatedNumber({ value, duration = 800, format, className }: AnimatedNumberProps) {
    const [display, setDisplay] = useState(0);
    const rafRef = useRef<number>();

    useEffect(() => {
        const start = performance.now();
        const animate = (now: number) => {
            const elapsed = now - start;
            const progress = Math.min(elapsed / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3); // ease-out cubic
            setDisplay(value * eased);
            if (progress < 1) rafRef.current = requestAnimationFrame(animate);
        };
        rafRef.current = requestAnimationFrame(animate);
        return () => { if (rafRef.current) cancelAnimationFrame(rafRef.current); };
    }, [value, duration]);

    const formatted = format ? format(display) : Math.round(display).toString();

    return <span className={className}>{formatted}</span>;
}
