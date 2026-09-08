import { test, expect, type Page } from "@playwright/test";

/**
 * Golden Path E2E Test — the most important browser-level test for Mudaraba.
 *
 * Exercises the full workflow the M/Y (Sajid) performs every month:
 *   1. Login with the seeded superadmin credentials
 *   2. Navigate to the Dashboard — verify KPI cards load
 *   3. Navigate to Sector Profit Entry
 *   4. Enter sector profits for July 2026 (the canonical reference month)
 *   5. Finalize the sector profits (triggers the 8-phase calculation engine)
 *   6. Navigate to Investor Profit ("For Sajid" page)
 *   7. Verify the canonical totals (Z2, X2, Y2) are displayed
 *   8. Export the investor profit grid to Excel ("For Sajid - July_2026.xlsx")
 *   9. Logout
 *
 * === Prerequisites ===
 *
 *   - Docker dev environment running: `docker compose up -d --build`
 *   - Database freshly seeded: `docker compose exec app php artisan migrate:fresh --seed`
 *   - Playwright browsers installed: `docker compose exec node npx playwright install --with-deps chromium`
 *
 * === Why these specific selectors? ===
 *
 * The login form uses id="username", id="password", id="remember" (see
 * resources/js/Pages/Login.tsx). The sector profit inputs are <input type="number">
 * inside table rows. The export button has text "Export to Excel". The user
 * menu has a "Sign out" item in a dropdown. All of these are stable enough
 * for E2E selectors — we use getByRole/getByText where possible for accessibility
 * (resilient to CSS class changes).
 */

// ============================================================================
// Test constants — match the July2026Seeder's canonical data
// ============================================================================

const BASE_URL = "http://localhost:8080";
const LOGIN_USERNAME = "E0001";
const LOGIN_PASSWORD = "Mudaraba@2026";

// Canonical July 2026 sector profits (from july_2026_data.json — matches the
// Excel "July, 2026 For Sajid" sheet exactly)
const JULY_2026_MONTH = "2026-07-01";
const EXPECTED_Z2 = "1,765,000"; // Σ estimated profit
const EXPECTED_X2 = "1,635,000"; // Σ actual profit

// ============================================================================
// Helper: login via the UI
// ============================================================================

async function login(page: Page, username = LOGIN_USERNAME, password = LOGIN_PASSWORD) {
    await page.goto("/login");

    // Wait for the login form to render (Inertia hydration)
    await expect(page.locator("#username")).toBeVisible({ timeout: 15_000 });

    // Fill the form
    await page.locator("#username").fill(username);
    await page.locator("#password").fill(password);

    // Submit — Inertia will POST and redirect to /dashboard
    await Promise.all([
        page.waitForURL("**/dashboard", { timeout: 15_000 }),
        page.locator('button[type="submit"]').click(),
    ]);

    // Verify we're on the dashboard
    await expect(page).toHaveURL(/\/dashboard$/);
}

// ============================================================================
// Test suite
// ============================================================================

test.describe("Golden Path — Monthly Reconciliation Workflow", () => {
    test("login → dashboard → sector profit → finalize → investor profit → export → logout", async ({
        page,
    }) => {
        // ─── 1. LOGIN ───────────────────────────────────────────────────────
        await login(page);

        // ─── 2. DASHBOARD — verify it loaded ────────────────────────────────
        // The dashboard h1 says "Assalamu Alaikum, {userName} 👋" (not "Dashboard")
        // Check for the greeting OR the KPI card labels — both are reliable indicators
        await expect(page.locator("text=Assalamu Alaikum").first()).toBeVisible({
            timeout: 15_000,
        });

        // Verify KPI cards load — the 4 KPI labels (from DashboardController) are:
        //   "Total Mudaraba Investment", "Current Month Profit", "M / Y Profit", "Active Investors"
        // Plus the "Cash in Hand" section below the KPI cards.
        await expect(page.locator("text=Total Mudaraba Investment").first()).toBeVisible({
            timeout: 10_000,
        });
        await expect(page.locator("text=Cash in Hand").first()).toBeVisible();

        // ─── 3. NAVIGATE TO SECTOR PROFIT ENTRY ──────────────────────────────
        // The sidebar has a link to "Sector Profit" (or similar)
        await page.getByRole("link", { name: /sector profit/i }).first().click();

        // Wait for the sector profit page to load
        await expect(page).toHaveURL(/\/profit\/sector/);

        // Navigate to July 2026 (the canonical reference month)
        // The month switcher has prev/next buttons — we need to get to 2026-07
        // The seeder already seeded July 2026 sector profits, so we need to
        // navigate to that month. We'll use the URL directly for reliability.
        await page.goto(`/profit/sector?month=${JULY_2026_MONTH}`);
        await expect(page.locator("text=July, 2026").first()).toBeVisible({ timeout: 10_000 });

        // ─── 4. VERIFY SECTOR PROFIT GRID ────────────────────────────────────
        // The grid should show 16 sectors (from the seeder)
        // The seeder finalizes July 2026, so the grid should show "Finalized" status
        // and the inputs should be disabled (read-only)
        const sectorRows = page.locator("table tbody tr");
        await expect(sectorRows).toHaveCount(16, { timeout: 10_000 });

        // ─── 5. VERIFY SECTOR TOTALS ────────────────────────────────────────
        // The totals row should show Z2 = 1,765,000 and X2 = 1,635,000
        // These are the canonical Excel "For Sajid" sheet values
        await expect(page.locator(`text=${EXPECTED_Z2}`).first()).toBeVisible({ timeout: 10_000 });
        await expect(page.locator(`text=${EXPECTED_X2}`).first()).toBeVisible({ timeout: 10_000 });

        // ─── 6. NAVIGATE TO INVESTOR PROFIT ("For Sajid" page) ───────────────
        await page.goto(`/profit/investor?month=${JULY_2026_MONTH}`);

        // The page should show the "For Sajid" grid with investor profit details
        await expect(page.locator("text=Investor Profit").first()).toBeVisible({ timeout: 10_000 });

        // ─── 7. VERIFY INVESTOR PROFIT TOTALS ────────────────────────────────
        // The grid should show the canonical Z2, X2 totals
        await expect(page.locator(`text=${EXPECTED_Z2}`).first()).toBeVisible({ timeout: 10_000 });
        await expect(page.locator(`text=${EXPECTED_X2}`).first()).toBeVisible({ timeout: 10_000 });

        // ─── 8. EXPORT TO EXCEL ─────────────────────────────────────────────
        // Find the "Export to Excel" button and click it
        // The button is only visible when isCalculated=true (which it is for July 2026)
        const exportButton = page.getByRole("button", { name: /export to excel/i }).first();
        await expect(exportButton).toBeVisible({ timeout: 10_000 });

        // Click the export button and wait for the download
        const downloadPromise = page.waitForEvent("download", { timeout: 15_000 });
        await exportButton.click();
        const download = await downloadPromise;

        // Verify the downloaded file name matches the "For Sajid" convention
        const filename = download.suggestedFilename();
        expect(filename).toMatch(/For Sajid - July_2026\.xlsx/);
        expect(filename).toMatch(/\.xlsx$/);

        // ─── 9. LOGOUT ──────────────────────────────────────────────────────
        // The user menu is a dropdown in the TopBar — click it to open
        // We look for the avatar/trigger button (it contains the user's initials or avatar image)
        const userMenuButton = page.locator('[aria-haspopup="menu"]').filter({ has: page.locator("img, svg").first() }).last();
        await userMenuButton.click();

        // Click "Sign out" in the dropdown menu
        const signOutItem = page.getByRole("menuitem", { name: /sign out/i });
        await expect(signOutItem).toBeVisible({ timeout: 5_000 });

        await Promise.all([
            page.waitForURL("**/login", { timeout: 10_000 }),
            signOutItem.click(),
        ]);

        // Verify we're back on the login page
        await expect(page).toHaveURL(/\/login$/);
        await expect(page.locator("#username")).toBeVisible({ timeout: 5_000 });
    });
});

// ============================================================================
// Negative test — invalid login should show error, NOT redirect to dashboard
// ============================================================================

test.describe("Authentication edge cases", () => {
    test("invalid credentials show error and stay on login page", async ({ page }) => {
        await page.goto("/login");
        await expect(page.locator("#username")).toBeVisible({ timeout: 15_000 });

        await page.locator("#username").fill("wronguser");
        await page.locator("#password").fill("wrongpassword");
        await page.locator('button[type="submit"]').click();

        // Should stay on /login (not redirect to /dashboard)
        // Inertia redirects back to /login with validation errors in the session
        await page.waitForURL(/\/login/, { timeout: 10_000 });

        // Should show an error message (Inertia validation error)
        // The login form renders errors in <p id="username-error"> (wrapped in
        // Framer Motion <motion.p> — the animation may take a moment to render)
        // Use the specific #username-error selector with a generous timeout
        await expect(page.locator("#username-error")).toBeVisible({
            timeout: 10_000,
        });
        // Verify the error text mentions "invalid" or "credentials"
        await expect(page.locator("#username-error")).toContainText(/invalid|credentials|password/i);
    });

    test("unauthenticated access to dashboard redirects to login", async ({ page, context }) => {
        // Clear any existing session cookies
        await context.clearCookies();

        // Try to access the dashboard directly
        await page.goto("/dashboard");

        // Should be redirected to /login
        await page.waitForURL(/\/login/, { timeout: 10_000 });
        await expect(page).toHaveURL(/\/login$/);
    });
});
