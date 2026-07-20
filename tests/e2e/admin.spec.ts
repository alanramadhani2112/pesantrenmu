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

const userId = (email: string) => execFileSync('php', [
  'artisan',
  'tinker',
  '--execute',
  `echo App\\Models\\User::where('email', '${email}')->value('id');`,
]).toString().trim();

test.beforeEach(() => {
  seed();
});

test('guest cannot open admin akreditasi', async ({ page }) => {
  const response = await page.goto('/admin/akreditasi');

  expect(response?.status()).toBeLessThan(400);
  await expect(page).toHaveURL(/\/login$/);
});

test('admin can open akreditasi list', async ({ adminPage }) => {
  await adminPage.goto('/admin/akreditasi');

  await expect(adminPage.getByRole('heading', { name: 'Akreditasi' }).first()).toBeVisible();
  await expect(adminPage.getByText('Mode Kerja Admin')).toBeVisible();
  await expect(adminPage.getByText('BF Pesantren', { exact: false }).first()).toBeVisible();
});

test('admin cannot open asesor area', async ({ adminPage }) => {
  const response = await adminPage.goto('/asesor/akreditasi');

  expect(response?.status()).toBe(403);
});

test('admin can move pengajuan to berkas review', async ({ adminPage }) => {
  await adminPage.goto(`/admin/akreditasi/${scenarioUuid('BF-HAPPY-001')}`);

  await adminPage.getByRole('button', { name: /buka untuk review/i }).click();

  await expect(adminPage.getByText('Verifikasi Berkas', { exact: false }).first()).toBeVisible();
});

test('admin can approve berkas and assign two asesors', async ({ adminPage }) => {
  await adminPage.goto(`/admin/akreditasi/${scenarioUuid('BF-HAPPY-002')}`);

  await adminPage.getByRole('button', { name: /setujui berkas/i }).click();
  await adminPage.getByLabel('Ketua Kelompok').selectOption(userId('bf.asesor1@test.local'));
  await adminPage.getByLabel('Anggota Kelompok').selectOption(userId('bf.asesor2@test.local'));
  await adminPage.getByRole('button', { name: /setujui & tugaskan tim asesor/i }).click();

  await expect(adminPage.getByText('Review Asesor', { exact: false }).first()).toBeVisible();
});
