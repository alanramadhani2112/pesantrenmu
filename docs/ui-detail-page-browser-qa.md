<!-- markdownlint-disable MD013 MD032 -->

# Browser QA Detail Akreditasi Lintas Role

## Scope

QA ini memvalidasi hasil standardisasi halaman detail akreditasi lintas role melalui browser, bukan hanya grep/test Blade.

Target:

| Role | URL | Screenshot | Status | Console error |
| --- | --- | --- | --- | --- |
| Super Admin/Admin | `/admin/akreditasi/ada01276-1092-49ee-9ed9-4575a5d27440` | `.sisyphus/evidence/ui-detail-page-standardization/browser-qa/admin-akreditasi-detail.png` | 200 | 0 |
| Pesantren | `/pesantren/akreditasi/5b032b34-8e1e-460a-b59b-1cc6eb2ac410` | `.sisyphus/evidence/ui-detail-page-standardization/browser-qa/pesantren-akreditasi-detail.png` | 200 | 0 |
| Asesor | `/asesor/akreditasi/ada01276-1092-49ee-9ed9-4575a5d27440` | `.sisyphus/evidence/ui-detail-page-standardization/browser-qa/asesor-akreditasi-detail.png` | 200 | 0 |

## Result

- Semua target detail akreditasi lintas role bisa dibuka tanpa 500.
- Browser console bersih dari error JavaScript pada halaman target.
- Screenshot baseline QA tersimpan di folder evidence.
- Halaman detail sudah memakai pola standar utama: `x-ui.page`, detail hero, badge status, metadata ringkas, tab/section, dan density `p-5`/`mb-5`/`row g-5`.

## Visual QA notes

### Admin/Super Admin detail

- Struktur detail sudah lebih lengkap: status, ID, periode, fokus admin, ringkasan, workflow stepper, dan tabs berada dalam satu halaman.
- Halaman cocok sebagai reference detail admin karena action area dan tab workflow terlihat paling lengkap.
- Perlu dicek manual berikutnya: panjang tab/section bawah saat data penuh agar tidak terlalu padat.

### Pesantren detail

- Detail hero sudah mengikuti grammar yang sama: status utama, ID/periode, dan konteks pengajuan.
- Visual lebih sederhana dari admin karena role pesantren hanya melihat/tindak lanjut bagian miliknya; ini acceptable selama shell/hero/status tetap konsisten.
- Perlu dicek manual berikutnya: empty/revision state ketika pengajuan ditolak/perbaikan aktif.

### Asesor detail

- Detail page bisa dibuka tanpa error JS; ini penting setelah bug Alpine `@js(...)` sebelumnya.
- Toolbar action dan hero sudah mengikuti standar density baru.
- Perlu dicek manual berikutnya: tab instrumen/scoring bawah halaman karena kontennya paling kompleks dan raw data paling panjang.

## Follow-up recommended

1. Jalankan visual review manual dari screenshot untuk bagian bawah halaman: tab dokumen, instrumen, catatan, dan audit trail.
2. Ambil responsive screenshot tablet/mobile jika detail page akan sering dipakai di perangkat kecil.
3. Jika ada perbedaan visual minor, patch per role dengan tetap mempertahankan standar `docs/ui-detail-page-standard.md`.
