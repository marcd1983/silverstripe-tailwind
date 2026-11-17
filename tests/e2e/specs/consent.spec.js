import { test, expect } from '@playwright/test';

/**
 * Cookie consent gating (Settings > Scripts, store-location map embeds,
 * Silverwind's consent-gate.js). The demo seed adds a script in the
 * Analytics group that sets window.fernbrookAnalytics.
 */
test.use({ reducedMotion: 'reduce' });

test('analytics waits for consent, then runs without a reload', async ({ page }) => {
  await page.goto('/');

  await expect(page.locator('script[type="text/plain"][data-cookie-consent="Analytics"]')).toHaveCount(1);
  expect(await page.evaluate(() => window.fernbrookAnalytics)).toBeUndefined();

  const navigations = [];
  page.on('framenavigated', (frame) => frame === page.mainFrame() && navigations.push(frame.url()));

  await page.getByRole('link', { name: 'Accept all cookies' }).click();

  await expect.poll(() => page.evaluate(() => window.fernbrookAnalytics)).toBe(true);
  expect(navigations, 'no page reload').toEqual([]);
});

test('declining keeps analytics off, also on the next page', async ({ page }) => {
  await page.goto('/');
  await page.getByRole('link', { name: 'Accept only necessary cookies' }).click();
  await page.goto('/about-us');

  expect(await page.evaluate(() => window.fernbrookAnalytics)).toBeUndefined();
});

test('accepted analytics loads straight away on later visits', async ({ page, context, baseURL }) => {
  await context.addCookies([{ name: 'CookieConsent', value: 'Necessary,Analytics', url: baseURL }]);
  await page.goto('/');

  await expect(page.locator('script[data-cookie-consent]')).toHaveCount(0);
  expect(await page.evaluate(() => window.fernbrookAnalytics)).toBe(true);
});

test('map shows a placeholder until loaded on request', async ({ page, context, baseURL }) => {
  await context.addCookies([{ name: 'CookieConsent', value: 'Necessary', url: baseURL }]);
  await page.goto('/locations/westbrook-yard');

  const map = page.locator('iframe[title^="Map of"]');
  await expect(map).not.toHaveAttribute('src', /.+/);
  await expect(page.getByText('The map is provided by Google')).toBeVisible();

  await page.getByRole('button', { name: 'Load map' }).click();

  await expect(map).toHaveAttribute('src', /google\.com\/maps/);
  await expect(page.getByRole('button', { name: 'Load map' })).toBeHidden();
});

test('accepting all cookies in the banner loads the map', async ({ page }) => {
  await page.goto('/locations/westbrook-yard');
  await page.getByRole('link', { name: 'Accept all cookies' }).click();

  await expect(page.locator('iframe[title^="Map of"]')).toHaveAttribute('src', /google\.com\/maps/);
  await expect(page.getByText('The map is provided by Google')).toBeHidden();
});
