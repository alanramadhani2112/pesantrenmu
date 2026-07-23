import { mkdirSync } from 'node:fs';
import type { Page } from '@playwright/test';
import { test, expect } from './fixtures';

test.describe.configure({ mode: 'serial' });

const cases = [
  { role: 'superadmin', path: /\/panduan-superadmin$/, heading: 'Panduan Super Admin', prefixes: ['sa-', 'superadmin-'] },
  { role: 'admin', path: /\/panduan-admin$/, heading: 'Panduan Admin', prefixes: ['admin-'] },
  { role: 'asesor', path: /\/panduan-asesor$/, heading: 'Panduan Asesor', prefixes: ['asesor-'] },
  { role: 'pesantren', path: /\/panduan-pesantren$/, heading: 'Panduan Pesantren', prefixes: ['pesantren-'] },
] as const;

const pages = {
  superadmin: 'superadminPage',
  admin: 'adminPage',
  asesor: 'asesorPage',
  pesantren: 'pesantrenPage',
} as const;

const directRoutes = ['/panduan-superadmin', '/panduan-admin', '/panduan-asesor', '/panduan-pesantren'] as const;

for (const item of cases) {
  test(`${item.role} /panduan redirects to matching guide`, async ({ superadminPage, adminPage, asesorPage, pesantrenPage }) => {
    const page = { superadmin: superadminPage, admin: adminPage, asesor: asesorPage, pesantren: pesantrenPage }[item.role];

    await page.goto('/panduan');
    await expect(page).toHaveURL(item.path);
    await expect(page.getByRole('heading', { name: item.heading }).first()).toBeVisible();
    await assertPanduanImagePrefixes(page, item.prefixes);

    mkdirSync('.sisyphus/evidence', { recursive: true });
    await page.screenshot({ path: `.sisyphus/evidence/task-6-${item.role}-panduan.png`, fullPage: true });
  });
}

for (const item of cases.filter((roleCase) => roleCase.role !== 'superadmin')) {
  test(`${item.role} cannot open other role panduan routes`, async ({ adminPage, asesorPage, pesantrenPage }) => {
    const page = { admin: adminPage, asesor: asesorPage, pesantren: pesantrenPage }[item.role];
    const allowedPath = pathText(item.path);

    for (const route of directRoutes.filter((directRoute) => directRoute !== allowedPath)) {
      const response = await page.goto(route);
      expect(response?.status()).toBe(403);
    }
  });
}

async function assertPanduanImagePrefixes(page: Page, prefixes: readonly string[]) {
  const imageNames = await page.locator('img[src*="/images/panduan/"]').evaluateAll((images) =>
    images.map((image) => {
      const src = image.getAttribute('src') ?? '';
      return src.split('/').pop() ?? src;
    }),
  );

  expect(imageNames.length).toBeGreaterThan(0);
  for (const imageName of imageNames) {
    expect(prefixes.some((prefix) => imageName.startsWith(prefix))).toBeTruthy();
  }
}

function pathText(path: RegExp) {
  return path.source.replace('\\/', '/').replace('$', '');
}
