import { defineConfig, devices } from '@playwright/test';

/**
 * Runs against a site seeded with the demo content:
 *   docker exec silverstripe_web vendor/bin/sake tasks:seed-demo-site --reset
 *
 * BASE_URL defaults to the docker-compose site. Screenshots are compared
 * with the committed baselines in specs/*-snapshots; refresh them after an
 * intended visual change with `npm run update-snapshots`.
 */
export default defineConfig({
  testDir: './specs',
  timeout: 60_000,
  retries: process.env.CI ? 1 : 0,
  reporter: [['list'], ['html', { open: 'never' }]],
  use: {
    baseURL: process.env.BASE_URL || 'http://localhost:8080',
    // Swiper skips autoplay and CSS transitions settle: steadier screenshots
    reducedMotion: 'reduce',
    trace: 'retain-on-failure',
  },
  expect: {
    toHaveScreenshot: { maxDiffPixelRatio: 0.01, animations: 'disabled' },
  },
  projects: [
    { name: 'phone', use: { ...devices['Pixel 7'] } },
    { name: 'tablet', use: { ...devices['Desktop Chrome'], viewport: { width: 820, height: 1180 } } },
    { name: 'desktop', use: { ...devices['Desktop Chrome'], viewport: { width: 1440, height: 900 } } },
  ],
});
