import type { Config } from 'ziggy-js';

declare global {
    interface Window {
        csrfToken: string;
    }
}

export {};

declare module 'vue' {
    interface ComponentCustomProperties {
        $route: typeof import('ziggy-js').route;
    }
}
