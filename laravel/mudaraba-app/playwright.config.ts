import { defineConfig, devices } from "@playwright/test";

/**
 * Playwright configuration for the Mudaraba Laravel app.
 *
 * === Running E2E tests ===
 *
 * Prerequisites:
 *   1. The Docker dev environment must be running:
 *        docker compose up -d --build
 *      (or at minimum, nginx + app + postgres + node must be up)
 *
 *   2. The app must be seeded with the canonical July 2026 data:
 *        docker compose exec app php artisan migrate:fresh --seed
 *
 *   3. Playwright browsers must be installed (one-time):
 *        npx playwright install chromium
 *      (In Docker: docker compose exec node npx playwright install chromium)
 *      IMPORTANT: do NOT use --with-deps — the Node container is Alpine-based
 *      and --with-deps tries to use apt-get which doesn't exist on Alpine.
 *      The required system libraries are already baked into the Dockerfile.
 *
 * Running the tests:
 *   npm run e2e                    # headless, all tests
 *   npm run e2e:ui                 # interactive UI mode (great for debugging)
 *   npm run e2e -- --grep "login"  # run a specific test by name pattern
 *
 * Viewing the HTML report:
 *   npm run e2e:report
 *
 * === Test credentials ===
 *
 * The E2E tests log in with the seeded superadmin:
 *   Username: E0001
 *   Password: Mudaraba@2026
 *
 * These match the July2026Seeder's hardcoded superadmin user.
 *
 * === Base URL ===
 *
 * Tests run against http://localhost:8080 (the Nginx container's mapped port).
 * If you changed APP_PORT in .env.docker, update the baseURL below to match.
 */
export default defineConfig({
    // Directory where E2E test files live
    testDir: "./tests/e2e",

    // Tests run in parallel by default; for E2E against a shared app instance,
    // serial is safer (avoids race conditions on shared DB state)
    fullyParallel: false,
    workers: 1,

    // Fail fast — if the first test fails, the rest likely will too
    retries: 0,
    forbidOnly: !!process.env.CI,

    // Reporter — HTML for local, list + GitHub Actions annotations for CI
    reporter: process.env.CI
        ? [["list"], ["html", { open: "never" }], ["github"]]
        : [["list"], ["html", { open: "never" }]],

    // Global timeout — 60s per test (first test may be slow due to browser startup)
    timeout: 60_000,
    expect: { timeout: 10_000 },

    // Run tests in this order (we use serial mode, so the order matters)
    use: {
        baseURL: "http://localhost:8080",
        trace: "on-first-retry",
        screenshot: "only-on-failure",
        video: "retain-on-failure",
        // Accept downloads — needed for the Excel export test
        acceptDownloads: true,
    },

    projects: [
        {
            name: "chromium",
            use: { ...devices["Desktop Chrome"] },
        },
    ],

    // Auto-start the app before tests run (optional — if you're not already
    // running Docker, uncomment this to let Playwright start `php artisan serve`).
    // We DON'T use this because the app runs in Docker — the webServer config
    // below would conflict with the Nginx container.
    //
    // webServer: {
    //     command: "php artisan serve",
    //     url: "http://localhost:8000",
    //     reuseExistingServer: true,
    //     timeout: 120_000,
    // },
});
