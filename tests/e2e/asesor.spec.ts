import { execFileSync } from 'node:child_process';
import { test, expect } from './fixtures';

test.describe.configure({ mode: 'serial' });

const seed = () => {
  execFileSync('php', ['artisan', 'db:seed', '--class=TestDataSeeder', '--no-interaction'], {
    stdio: 'ignore',
  });
};

const scenarioUuid = (code: string) => execFileSync('php', [
  'artisan',
  'tinker',
  '--execute',
  `echo App\\Models\\Akreditasi::where('catatan', 'like', '[${code}]%')->value('uuid');`,
]).toString().trim();

const futureDate = (days: number) => {
  const date = new Date();
  date.setDate(date.getDate() + days);
  return date.toISOString().slice(0, 10);
};

test.beforeEach(() => {
  seed();
});

test('asesor can open assigned akreditasi list', async ({ asesorPage }) => {
  await asesorPage.goto('/asesor/akreditasi');

  await expect(asesorPage.getByRole('heading', { name: /akreditasi/i }).first()).toBeVisible();
  await expect(asesorPage.getByText('BF Pesantren', { exact: false }).first()).toBeVisible();
});

test('ketua asesor can schedule visitasi from detail', async ({ asesorPage }) => {
  await asesorPage.goto('/asesor/akreditasi');

  await asesorPage.locator('tr', { hasText: 'Review Asesor' }).first().getByRole('button', { name: /aksi/i }).click();
  await asesorPage.getByRole('menuitem', { name: /atur jadwal/i }).first().click();
  const form = asesorPage.locator('form[action*="schedule-visitasi"]').first();
  await form.locator('input[name="tanggal_mulai"]').fill(futureDate(8));
  await form.locator('input[name="tanggal_akhir"]').fill(futureDate(10));
  await form.locator('textarea[name="catatan"]').fill('Jadwal visitasi E2E asesor.');
  await asesorPage.getByRole('button', { name: /atur jadwal visitasi/i }).click();

  await expect(asesorPage.locator('td', { hasText: 'Visitasi Terjadwal' }).first()).toBeVisible();
});

test('asesor can save NA score from instrumen tab', async ({ asesorPage }) => {
  await asesorPage.goto(`/asesor/akreditasi/${scenarioUuid('BF-HAPPY-005')}?activeTab=instrumen`);

  const response = asesorPage.waitForResponse((res) =>
    res.url().includes('/asesor/akreditasi/save-na') && res.status() === 200,
  );
  await asesorPage.getByLabel(/Nilai NA butir/i).first().selectOption('4');
  await response;

  await expect(asesorPage.getByLabel(/Nilai NA butir/i).first()).toHaveValue('4');
});
