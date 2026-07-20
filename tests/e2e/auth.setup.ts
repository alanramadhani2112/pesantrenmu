import { execFileSync } from 'node:child_process';
import { mkdirSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { expect, test as setup } from '@playwright/test';
import { authUsers } from './auth';

setup.beforeAll(() => {
  if (process.env.PLAYWRIGHT_SEED !== '0') {
    execFileSync('php', ['artisan', 'db:seed', '--class=TestDataSeeder', '--no-interaction'], {
      stdio: 'ignore',
    });
  }
});

for (const [role, user] of Object.entries(authUsers)) {
  setup(`authenticate ${role}`, async ({ page }) => {
    mkdirSync(dirname(resolve(user.state)), { recursive: true });

    await page.goto('/login');
    await page.locator('input[name="email"]').fill(user.email);
    await page.locator('input[name="password"]').fill(user.password);
    await page.getByRole('button', { name: /masuk/i }).click();
    await expect(page).toHaveURL(/\/dashboard(?:$|\?)/);
    await page.context().storageState({ path: user.state });
  });
}
