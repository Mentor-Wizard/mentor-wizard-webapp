# E2E Testing

> Reference for: Test Master
> Load when: E2E strategy, user flow testing

## E2E Test Strategy

```typescript
const criticalPaths = [
  'User registration and login',
  'Core product/service workflow',
  'Payment/checkout flow',
  'Settings and profile management',
];
```

## User Flow Testing

```typescript
import { test, expect } from '@playwright/test';

test.describe('User Registration Flow', () => {
  test('complete registration', async ({ page }) => {
    await page.goto('/register');
    await page.getByLabel('Email').fill('new@example.com');
    await page.getByLabel('Password').fill('SecurePass123!');
    await page.getByRole('button', { name: 'Register' }).click();

    await expect(page).toHaveURL(/dashboard/);
    await expect(page.getByText('Welcome')).toBeVisible();
  });

  test('shows validation errors', async ({ page }) => {
    await page.goto('/register');
    await page.getByLabel('Email').fill('invalid');
    await page.getByRole('button', { name: 'Register' }).click();

    await expect(page.getByText('Invalid email')).toBeVisible();
  });
});
```

## Cross-Browser Testing

```typescript
// playwright.config.ts
export default defineConfig({
  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
    { name: 'firefox', use: { ...devices['Desktop Firefox'] } },
    { name: 'webkit', use: { ...devices['Desktop Safari'] } },
    { name: 'mobile-chrome', use: { ...devices['Pixel 5'] } },
  ],
});
```

## Quick Reference

| Priority | Test Coverage |
|----------|---------------|
| **P0** | Registration, login, core feature |
| **P1** | Payment, settings, common flows |
| **P2** | Edge cases, admin features |
| **P3** | Rare scenarios |
