import { defineConfig, devices } from '@playwright/test'

/**
 * Röktest mot en seedad api/: starta API:et (php artisan serve) och seeda med
 * `php artisan db:seed --class=DemoContentSeeder` i api/ innan `npm run test:e2e`.
 * Next byggs och startas i produktionsläge på egen port så att ISR beter sig som i drift.
 */
const PORT = 3100
const baseURL = `http://localhost:${PORT}`

export default defineConfig({
  testDir: './e2e',
  fullyParallel: true,
  reporter: 'list',
  timeout: 30_000,
  use: { baseURL, trace: 'retain-on-failure' },
  projects: [
    { name: 'desktop', use: { ...devices['Desktop Chrome'] } },
    { name: 'mobile', use: { ...devices['Pixel 7'] } },
  ],
  webServer: {
    command: `npm run build && npm run start -- --port ${PORT}`,
    url: baseURL,
    reuseExistingServer: !process.env.CI,
    timeout: 240_000,
    env: {
      API_URL: process.env.API_URL ?? 'http://localhost:8000',
      SITE_SLUG: process.env.SITE_SLUG ?? 'ludvika',
      SITE_URL: baseURL,
    },
  },
})
