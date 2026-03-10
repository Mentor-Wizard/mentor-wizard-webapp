// @ts-check
import { expect, test } from '@playwright/test';

const BASE_URL = 'http://localhost';
const MENTORS_URL = `${BASE_URL}/mentors`;

async function waitForPageReady(page) {
  await page.waitForLoadState('domcontentloaded');
  await page.getByRole('heading', { name: 'Find Mentors', level: 1 }).waitFor({ state: 'visible' });
}

test.describe('Mentors Search Page', () => {
  test('page loads successfully with heading, mentor count, and sort dropdown', async ({
    page,
  }) => {
    await page.goto(MENTORS_URL);
    await waitForPageReady(page);

    await expect(page.getByRole('heading', { name: 'Find Mentors', level: 1 })).toBeVisible();
    await expect(page.getByRole('status')).toBeVisible();
    await expect(page.getByRole('status')).toContainText('mentors');
    await expect(page.getByLabel('Sort mentors')).toBeVisible();
  });

  test('filter sidebar is visible on desktop viewport', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 800 });
    await page.goto(MENTORS_URL);
    await waitForPageReady(page);

    const sidebar = page.locator('aside');
    await expect(sidebar).toBeVisible();

    const sectionHeadings = [
      'Technology Stacks',
      'Languages',
      'Experience Level',
      'Price Range (per hour)',
      'Minimum Rating',
    ];

    for (const heading of sectionHeadings) {
      await expect(sidebar.getByRole('button', { name: new RegExp(heading) })).toBeVisible();
    }
  });

  test('mobile filter drawer opens and closes', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 812 });
    await page.goto(MENTORS_URL);
    await waitForPageReady(page);

    const sidebar = page.locator('aside');
    await expect(sidebar).toBeHidden();

    await page.getByLabel('Open filters').click();

    const drawerHeading = page.getByRole('heading', { name: 'Filters' });
    await expect(drawerHeading).toBeVisible();

    const showResultsButton = page.getByRole('button', { name: /Show results/ });
    await expect(showResultsButton).toBeVisible();

    await showResultsButton.click();

    await expect(drawerHeading).toBeHidden();
  });

  test('sort dropdown changes URL params', async ({ page }) => {
    await page.goto(MENTORS_URL);
    await waitForPageReady(page);

    const sortSelect = page.getByLabel('Sort mentors');
    await sortSelect.selectOption({ label: 'Price: Low to High' });

    await waitForPageReady(page);

    expect(page.url()).toContain('sort=rate');
  });

  test('pagination navigates to page 2 when available', async ({ page }) => {
    await page.goto(MENTORS_URL);
    await waitForPageReady(page);

    const paginationButtons = page.locator('[aria-current="page"]');
    const hasPagination = (await paginationButtons.count()) > 0;

    if (!hasPagination) {
      test.skip();
      return;
    }

    const page2Button = page.getByRole('button', { name: '2', exact: true });
    const page2Exists = (await page2Button.count()) > 0;

    if (!page2Exists) {
      test.skip();
      return;
    }

    await page2Button.click();
    await waitForPageReady(page);

    expect(page.url()).toContain('page=2');
  });

  test('empty state displays when impossible filter is applied', async ({ page }) => {
    await page.goto(`${MENTORS_URL}?filter[stacks]=ThisStackDoesNotExist99999`);
    await waitForPageReady(page);

    await expect(page.getByText('No mentors found')).toBeVisible();

    const clearButton = page.getByRole('button', { name: 'Clear all filters' });
    const chipsClearButton = page.getByRole('button', { name: 'Clear all' });

    const hasClearButton = (await clearButton.count()) > 0;
    const hasChipsClear = (await chipsClearButton.count()) > 0;

    if (hasClearButton) {
      await expect(clearButton.first()).toBeVisible();
    }

    if (hasChipsClear) {
      await expect(chipsClearButton.first()).toBeVisible();
    }
  });

  test('URL filter parameters persist after page load', async ({ page }) => {
    const url = `${MENTORS_URL}?filter[experience]=senior&sort=-rate`;
    await page.goto(url);
    await waitForPageReady(page);

    expect(page.url()).toContain('filter%5Bexperience%5D=senior');
    expect(page.url()).toContain('sort=-rate');

    const sortSelect = page.getByLabel('Sort mentors');
    await expect(sortSelect).toHaveValue('-rate');
  });

  test('filter chips appear when filtering and clear all removes them', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 800 });
    await page.goto(MENTORS_URL);
    await waitForPageReady(page);

    const sidebar = page.locator('aside');
    const stackCheckboxes = sidebar
      .locator('div')
      .filter({ hasText: 'Technology Stacks' })
      .locator('input[type="checkbox"]');

    const checkboxCount = await stackCheckboxes.count();

    if (checkboxCount === 0) {
      test.skip();
      return;
    }

    await stackCheckboxes.first().check();
    await waitForPageReady(page);

    expect(page.url()).toContain('filter');

    const clearAllButton = page.getByRole('button', { name: 'Clear all', exact: true });
    const hasClearAll = (await clearAllButton.count()) > 0;

    if (hasClearAll) {
      await clearAllButton.click();
      await waitForPageReady(page);

      expect(page.url()).not.toContain('filter%5Bstacks%5D');
    }
  });

  test('responsive grid adjusts columns at different viewports', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 800 });
    await page.goto(MENTORS_URL);
    await waitForPageReady(page);

    const mentorCards = page.locator('section').locator('[class*="grid"]').first();
    const hasCards = (await mentorCards.count()) > 0;

    if (!hasCards) {
      test.skip();
      return;
    }

    const getGridColumns = async () => {
      return mentorCards.evaluate((el) => {
        const style = window.getComputedStyle(el);
        const columns = style.getPropertyValue('grid-template-columns');
        return columns.split(' ').filter((c) => c !== '').length;
      });
    };

    const desktopCols = await getGridColumns();
    expect(desktopCols).toBe(3);

    await page.setViewportSize({ width: 768, height: 1024 });
    await page.waitForTimeout(300);

    const tabletCols = await getGridColumns();
    expect(tabletCols).toBe(2);

    await page.setViewportSize({ width: 375, height: 812 });
    await page.waitForTimeout(300);

    const mobileCols = await getGridColumns();
    expect(mobileCols).toBe(1);
  });

  test('accessibility attributes are present on key elements', async ({ page }) => {
    await page.goto(MENTORS_URL);
    await waitForPageReady(page);

    const mentorCount = page.getByRole('status');
    await expect(mentorCount).toHaveAttribute('aria-live', 'polite');

    const sortDropdown = page.getByLabel('Sort mentors');
    await expect(sortDropdown).toBeVisible();

    const activePage = page.locator('[aria-current="page"]');
    const hasPagination = (await activePage.count()) > 0;

    if (hasPagination) {
      await expect(activePage.first()).toHaveAttribute('aria-current', 'page');
    }
  });
});
