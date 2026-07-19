import { test as base, expect, type BrowserContext, type Page } from '@playwright/test';
import { authStates } from './auth';

type RoleFixtures = {
  adminContext: BrowserContext;
  asesorContext: BrowserContext;
  pesantrenContext: BrowserContext;
  adminPage: Page;
  asesorPage: Page;
  pesantrenPage: Page;
};

export const test = base.extend<RoleFixtures>({
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
