import '../css/app.css';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { route } from 'ziggy-js';
import { ThemeProvider } from '@/Components/ThemeProvider';
import { Toaster } from '@/Components/ui';

declare global {
    interface Window {
        route: typeof route;
    }
}
window.route = route;

createInertiaApp({
    title: (title) => (title ? `${title} · Mudaraba` : 'Mudaraba'),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.tsx`,
            import.meta.glob('./Pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        createRoot(el).render(
            <ThemeProvider defaultTheme="system">
                <App {...props} />
                <Toaster />
            </ThemeProvider>,
        );
    },
    progress: {
        color: '#10B981',
        showSpinner: true,
    },
});
