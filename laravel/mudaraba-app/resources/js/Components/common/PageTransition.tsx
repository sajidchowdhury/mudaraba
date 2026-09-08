import { motion } from "framer-motion";
import { ReactNode } from "react";

interface PageTransitionProps {
    children: ReactNode;
    className?: string;
}

/**
 * Wraps page content with a subtle fade+slide-up transition.
 * Use at the top level of each page's main content div.
 */
export function PageTransition({ children, className }: PageTransitionProps) {
    return (
        <motion.div
            initial={{ opacity: 0, y: 8 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.3, ease: [0.16, 1, 0.3, 1] }}
            className={className}
        >
            {children}
        </motion.div>
    );
}

/**
 * Stagger container for lists of cards/rows.
 * Children should use StaggerItem.
 */
export function StaggerContainer({ children, className }: PageTransitionProps) {
    return (
        <motion.div
            initial="hidden"
            animate="visible"
            variants={{
                hidden: { opacity: 0 },
                visible: {
                    opacity: 1,
                    transition: { staggerChildren: 0.05 },
                },
            }}
            className={className}
        >
            {children}
        </motion.div>
    );
}

/**
 * Individual item for stagger animation.
 */
export function StaggerItem({ children, className }: PageTransitionProps) {
    return (
        <motion.div
            variants={{
                hidden: { opacity: 0, y: 10 },
                visible: {
                    opacity: 1,
                    y: 0,
                    transition: { duration: 0.3, ease: [0.16, 1, 0.3, 1] },
                },
            }}
            className={className}
        >
            {children}
        </motion.div>
    );
}
