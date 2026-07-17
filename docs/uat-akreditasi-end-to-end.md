<!-- markdownlint-disable MD013 MD032 MD060 -->

# UAT Akreditasi End-to-End

Dokumen ini mencatat hasil UAT workflow akreditasi end-to-end setelah rangkaian refactor UI. Scope UAT: validasi flow otomatis dan browser smoke halaman workflow utama per role.

## Scope

UAT mencakup:

- Pesantren membuat pengajuan akreditasi.
- Admin membuka review dan menyetujui berkas.
- Asesor menjadwalkan/menyelesaikan visitasi dan mengisi penilaian.
- Admin memfinalisasi hasil sampai status selesai.
- Browser smoke dashboard/list/detail workflow untuk Admin, Pesantren, dan Asesor.

UAT ini tidak mengubah workflow bisnis, route, permission, status legal, database schema, atau controller.

## Automated E2E Tests

Command:

```bash
php artisan test tests\Feature\E2E\HybridAccreditationFlowTest.php tests\Feature\AkreditasiWorkflow\FullHappyPathTest.php tests\Feature\AkreditasiWorkflow\WorkflowE2ESmokeTest.php --stop-on-failure
```

Result:

| Test file | Result | Coverage |
|---|---:|---|
| `tests\Feature\E2E\HybridAccreditationFlowTest.php` | 2 passed | HTTP canonical accreditation flow and negative actor/premature transition checks |
| `tests\Feature\AkreditasiWorkflow\FullHappyPathTest.php` | 5 passed | Service-level full happy path, peringkat boundaries, missing document and NV guards |
| `tests\Feature\AkreditasiWorkflow\WorkflowE2ESmokeTest.php` | 2 passed | E2E happy path to selesai and negative post-visitasi document guard |

Total: `9 passed (160 assertions)`.

## Browser Smoke Evidence

Evidence folder: `.sisyphus/evidence/uat-akreditasi-e2e`

| Role | Page | URL | HTTP | Console errors | Screenshot | Result |
|---|---|---|---:|---:|---|---|
| Admin | Dashboard | `/dashboard` | 200 | 0 | `.sisyphus/evidence/uat-akreditasi-e2e/admin-dashboard.png` | Pass |
| Admin | Akreditasi List | `/admin/akreditasi` | 200 | 0 | `.sisyphus/evidence/uat-akreditasi-e2e/admin-akreditasi-list.png` | Pass |
| Admin | Akreditasi Detail | `/admin/akreditasi/ada01276-1092-49ee-9ed9-4575a5d27440` | 200 | 0 | `.sisyphus/evidence/uat-akreditasi-e2e/admin-akreditasi-detail.png` | Pass |
| Pesantren | Dashboard | `/dashboard` | 200 | 0 | `.sisyphus/evidence/uat-akreditasi-e2e/pesantren-dashboard.png` | Pass |
| Pesantren | Akreditasi List | `/pesantren/akreditasi` | 200 | 0 | `.sisyphus/evidence/uat-akreditasi-e2e/pesantren-akreditasi-list.png` | Pass |
| Pesantren | Akreditasi Detail | `/pesantren/akreditasi/5b032b34-8e1e-460a-b59b-1cc6eb2ac410` | 200 | 0 | `.sisyphus/evidence/uat-akreditasi-e2e/pesantren-akreditasi-detail.png` | Pass |
| Asesor | Dashboard | `/dashboard` | 200 | 0 | `.sisyphus/evidence/uat-akreditasi-e2e/asesor-dashboard.png` | Pass |
| Asesor | Akreditasi List | `/asesor/akreditasi` | 200 | 0 | `.sisyphus/evidence/uat-akreditasi-e2e/asesor-akreditasi-list.png` | Pass |
| Asesor | Akreditasi Detail | `/asesor/akreditasi/ada01276-1092-49ee-9ed9-4575a5d27440` | 200 | 0 | `.sisyphus/evidence/uat-akreditasi-e2e/asesor-akreditasi-detail.png` | Pass |

## Notes

- `asesor@spm.test` menggunakan password lokal `password` dari reset lokal yang sudah disetujui untuk browser smoke QA.
- Browser smoke ini menguji akses dan stabilitas halaman utama workflow, bukan submit mutasi produksi manual.
- Automated E2E tests menguji mutasi workflow lengkap dengan database test terisolasi.

## Conclusion

UAT akreditasi end-to-end lulus:

- flow otomatis mencapai status selesai;
- negative guards tetap aktif;
- halaman workflow utama per role HTTP 200;
- console error 0 pada browser smoke.

Tidak ada bug blocker baru yang perlu patch kode dari UAT ini.
