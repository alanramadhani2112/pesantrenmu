import { test as base, expect, type BrowserContext, type Page } from '@playwright/test';
import { authStates } from './auth';

type RoleFixtures = {
  superadminContext: BrowserContext;
  adminContext: BrowserContext;
  asesorContext: BrowserContext;
  pesantrenContext: BrowserContext;
  superadminPage: Page;
  adminPage: Page;
  asesorPage: Page;
  pesantrenPage: Page;
};

export const test = base.extend<RoleFixtures>({
  superadminContext: async ({ browser }, use) => {
    const context = await browser.newContext({ storageState: authStates.superadmin });
    await use(context);
    await context.close();
  },
  adminContext: async ({ browser }, use) => {
    const context = await browser.newContext({ storageState: authStates.admin });
    await use(context);
    await context.close();
  },
  asesorContext: async ({ browser }, use) => {
    const context = await browser.newContext({ storageState: authStates.asesor });
    await use(context);
    await context.close();
  },
  pesantrenContext: async ({ browser }, use) => {
    const context = await browser.newContext({ storageState: authStates.pesantren });
    await use(context);
    await context.close();
  },
  superadminPage: async ({ superadminContext }, use) => {
    const page = await superadminContext.newPage();
    await use(page);
    await page.close();
  },
  adminPage: async ({ adminContext }, use) => {
    const page = await adminContext.newPage();
    await use(page);
    await page.close();
  },
  asesorPage: async ({ asesorContext }, use) => {
    const page = await asesorContext.newPage();
    await use(page);
    await page.close();
  },
  pesantrenPage: async ({ pesantrenContext }, use) => {
    const page = await pesantrenContext.newPage();
    await use(page);
    await page.close();
  },
});

export { expect };
