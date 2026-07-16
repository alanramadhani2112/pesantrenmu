<!-- markdownlint-disable MD013 MD032 -->

# Browser QA Tab Detail Akreditasi Lintas Role

## Scope

QA ini memvalidasi tab bagian bawah halaman detail akreditasi lintas role setelah standardisasi detail page.

Evidence folder:

`.sisyphus/evidence/ui-detail-page-standardization/browser-qa-tabs/`

## Captured pages

| Role | URL | Status | Console error | Captured tabs |
| --- | --- | --- | --- | --- |
| Admin/Super Admin | `/admin/akreditasi/ada01276-1092-49ee-9ed9-4575a5d27440` | 200 | 0 | Profil, IPM, SDM, EDPM, Nilai, Laporan Visitasi, Audit Trail |
| Pesantren | `/pesantren/akreditasi/5b032b34-8e1e-460a-b59b-1cc6eb2ac410` | 200 | 0 | Profil, IPM, SDM, EDPM, Asesor, Hasil |
| Asesor | `/asesor/akreditasi/ada01276-1092-49ee-9ed9-4575a5d27440` | 200 | 0 | Profil, IPM, SDM, EDPM |

## Screenshot files

### Admin/Super Admin

- `admin-default.png`
- `admin-01-profil.png`
- `admin-02-ipm.png`
- `admin-03-sdm.png`
- `admin-04-edpm.png`
- `admin-05-nilai.png`
- `admin-06-laporan-visitasi.png`
- `admin-07-audit-trail.png`

### Pesantren

- `pesantren-default.png`
- `pesantren-01-profil.png`
- `pesantren-02-ipm.png`
- `pesantren-03-sdm.png`
- `pesantren-04-edpm.png`
- `pesantren-05-asesor.png`
- `pesantren-06-hasil.png`

### Asesor

- `asesor-default.png`
- `asesor-01-profil.png`
- `asesor-02-ipm.png`
- `asesor-03-sdm.png`
- `asesor-04-edpm.png`

## Findings

### Pass

- Semua halaman detail dan tab yang diuji berhasil dibuka dengan status 200.
- Tidak ada browser console error pada target QA.
- Tab dasar `Profil`, `IPM`, `SDM`, dan `EDPM` tersedia di semua role yang diuji.
- Perbedaan jumlah tab sesuai scope role: admin punya tab audit/penilaian/laporan; pesantren punya tab hasil/asesor; asesor fokus pada data penilaian.

## Re-run after tab casing fix

Admin tab visual casing sudah diverifikasi ulang setelah CSS detail tab disesuaikan.

| Role | URL | Screenshot | Result | Console error |
| --- | --- | --- | --- | --- |
| Admin/Super Admin | `/admin/akreditasi/ada01276-1092-49ee-9ed9-4575a5d27440` | `.sisyphus/evidence/ui-detail-page-standardization/browser-qa-tabs/admin-tabs-title-case-rerun.png` | Semua tab `text-transform: none` | 0 |

### Visual inconsistencies to watch next

1. **Admin tab count paling panjang.** Tujuh tab bisa terasa padat pada layar kecil. Perlu dicek responsive/manual: jika wrap buruk, gabungkan tab sekunder ke dropdown atau kurangi padding tab.
2. **Konten tab scoring/laporan butuh review manual.** QA screenshot berhasil, tetapi bagian tabel panjang dan form scoring tetap perlu review visual manusia karena screenshot full-page tidak menggantikan penilaian usability detail.

## Recommended follow-up

1. Review responsive tab admin pada viewport tablet/mobile.
2. Lanjut mini-refactor hanya jika screenshot menunjukkan density/table/form drift nyata di tab `Nilai`, `Laporan Visitasi`, atau `Audit Trail`.
