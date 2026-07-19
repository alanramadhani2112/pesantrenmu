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

test.beforeEach(() => {
  seed();
});

test('pesantren can open akreditasi center', async ({ pesantrenPage }) => {
  await pesantrenPage.goto('/pesantren/akreditasi');

  await expect(pesantrenPage.getByRole('heading', { name: 'Pusat Akreditasi' })).toBeVisible();
  await expect(pesantrenPage.getByText('Riwayat Pengajuan')).toBeVisible();
  await expect(pesantrenPage.getByRole('button', { name: /aksi/i }).first()).toBeVisible();
});

test('pesantren can open akreditasi detail', async ({ pesantrenPage }) => {
  await pesantrenPage.goto(`/pesantren/akreditasi/${scenarioUuid('BF-HAPPY-005')}`);

  await expect(pesantrenPage.getByRole('heading', { name: 'Detail Akreditasi' })).toBeVisible();
  await expect(pesantrenPage.getByText('Tahapan Akreditasi LP2M')).toBeVisible();
  await expect(pesantrenPage.getByRole('heading', { name: 'Kartu Kendali' })).toBeVisible();
});

test('pesantren can upload kartu kendali', async ({ pesantrenPage }) => {
  await pesantrenPage.goto(`/pesantren/akreditasi/${scenarioUuid('BF-HAPPY-005')}`);

  await pesantrenPage.locator('input[name="kartu_kendali_file"]').setInputFiles({
    name: 'kartu-kendali-e2e.pdf',
    mimeType: 'application/pdf',
    buffer: Buffer.from('%PDF-1.4\n% e2e kartu kendali\n'),
  });
  await pesantrenPage.getByRole('button', { name: /upload/i }).click();
  await pesantrenPage.getByRole('button', { name: /ya, upload/i }).click();

  await expect(pesantrenPage.getByText('Dokumen Terunggah')).toBeVisible();
});
