/// <reference types="vite/client" />

interface ImportMetaEnv {
    [key: string]: unknown;
}

interface ImportMeta {
    readonly env: ImportMetaEnv;
    glob: (pattern: string, options?: { eager?: boolean }) => Record<string, unknown>;
}
