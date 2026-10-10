import { defineConfig, devices } from '@playwright/test'

/**
 * End-to-end tests drive a real Chromium against the real Laravel API and a
 * real Postgres database: no mocked network layer. Both dev servers are
 * started here (not assumed running) so `npx playwright test` is a complete
 * command in CI and locally alike; `reuseExistingServer` lets a developer who
 * already has them running skip the relaunch.
 *
 * The backend must be migrated and seeded first
 * (`php artisan migrate:fresh --seed --database=pgsql_migrator`): the specs
 * log in as the seeded admin.
 *
 * Sanctum's SPA cookie pattern relies on the frontend and API sharing a
 * cookie-visible hostname. `localhost` and `127.0.0.1` are different
 * hostnames as far as the cookie jar and CORS are concerned, so every origin
 * here uses `localhost`, never `127.0.0.1`.
 */
export default defineConfig({
  testDir: './tests/e2e',
  fullyParallel: false,
  retries: process.env.CI ? 1 : 0,
  workers: 1,
  reporter: 'line',
  use: {
    baseURL: 'http://localhost:3000',
    trace: 'retain-on-failure',
  },
  projects: [
    {
      name: 'chromium',
      use: {
        ...devices['Desktop Chrome'],
        // Set PLAYWRIGHT_CHROMIUM_PATH when the environment ships a Chromium
        // build that does not match the revision this Playwright version
        // expects, instead of running `playwright install`.
        launchOptions: { executablePath: process.env.PLAYWRIGHT_CHROMIUM_PATH || undefined },
      },
    },
  ],
  webServer: [
    {
      command: 'php artisan serve --host=localhost --port=8000',
      cwd: '..',
      url: 'http://localhost:8000/up',
      reuseExistingServer: !process.env.CI,
      timeout: 30_000,
    },
    {
      command: 'npm run dev -- --host=localhost --port=3000',
      cwd: '.',
      url: 'http://localhost:3000',
      reuseExistingServer: !process.env.CI,
      timeout: 60_000,
    },
  ],
})
