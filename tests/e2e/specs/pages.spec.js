import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

/** Demo pages from the seed task, covering every block type in use */
const pages = [
  ['home', '/'],
  ['services', '/services'],
  ['service', '/services/patios-and-paving'],
  ['about', '/about-us'],
  ['contact', '/contact-us'],
  ['locations', '/locations'],
  ['location', '/locations/westbrook-yard'],
  ['search', '/home/SearchForm?Search=hardwood&action_results=Search'],
];

test.beforeEach(async ({ context, baseURL }) => {
  // Necessary cookies only: hides the cookie banner without loading anything gated
  await context.addCookies([{ name: 'CookieConsent', value: 'Necessary', url: baseURL }]);
});

for (const [name, path] of pages) {
  test.describe(name, () => {
    test('has no serious accessibility issues', async ({ page }) => {
      await page.goto(path);
      const results = await new AxeBuilder({ page })
        .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
        .analyze();
      const serious = results.violations.filter((v) => ['serious', 'critical'].includes(v.impact));
      const summary = serious.map((v) => `${v.id} (${v.impact}): ${v.help} — ${v.nodes.length} element(s), e.g. ${v.nodes[0]?.target}`);
      expect(summary, summary.join('\n')).toEqual([]);
    });

    test('matches the screenshot', async ({ page }) => {
      await page.goto(path);
      await page.waitForLoadState('networkidle');
      await expect(page).toHaveScreenshot(`${name}.png`, {
        fullPage: true,
        // Time-dependent: open/closed badges, today's row, holiday dates
        mask: [page.locator('.badge'), page.locator('[aria-current="date"]'), page.locator('time')],
      });
    });
  });
}

test('every page has exactly one h1', async ({ page }) => {
  for (const [, path] of pages) {
    await page.goto(path);
    await expect(page.locator('h1'), path).toHaveCount(1);
  }
});
