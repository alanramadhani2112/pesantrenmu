# Next Execution Plan - UI Audit and Final MVP Push

Tanggal: 29 Juni 2026  
Status: selesai untuk batch P0/P1 MVP per 2026-07-07

Evidence terbaru: `docs/ui-role-audit-2026-07-07.md` (`node output/playwright/task4-role-smoke.mjs` -> count 19, issues `[]`).

## Fokus Saat Ini

Backend kritis dan governance besar sudah distabilkan. Sisa plan sekarang berpusat pada audit operasional nyata per role, UI polish, dan sedikit hardening akhir.

## Prioritas Eksekusi

| Item | Impact | Priority | Output yang Diharapkan |
|---|---|---:|---|
| Audit dashboard Super Admin | Tinggi | P1 | daftar gap visual, nav, CTA, permission surface |
| Audit dashboard Admin | Tinggi | P1 | daftar gap operasional harian LP2M |
| Audit dashboard Asesor | Tinggi | P1 | daftar gap scoring, visitasi, upload laporan |
| Audit dashboard Pesantren | Tinggi | P1 | daftar gap pengajuan, perbaikan, hasil akhir, banding |
| UI polish semua role | Tinggi | P1 | patch visual/UX final per halaman |
| Audit browser nyata per role | Tinggi | P1 | validasi flow operasional end-to-end |
| Finalize scoring happy path penuh | Sedang | P1 | regression/test atau bukti verifikasi penuh |
| Negative-path kecil workflow | Sedang | P2 | regression hardening tambahan |
| Cleanup docs historis legacy reactive layer | Rendah | P2 | docs lama tidak membingungkan runtime aktif |
| Audit kontrak frontend-backend sisa | Sedang | P2 | daftar mismatch tombol/form/route bila ada |

## Urutan Kerja Disarankan

1. Audit dashboard `Super Admin`
2. Audit dashboard `Admin`
3. Audit dashboard `Asesor`
4. Audit dashboard `Pesantren`
5. Susun temuan UI per role
6. Kerjakan patch UI polish prioritas tinggi
7. Jalankan audit browser nyata ulang
8. Tutup hardening backend kecil yang masih tersisa
9. Rapikan docs historis legacy reactive layer bila masih perlu

## Checklist Eksekusi

### Audit Role
- [x] audit dashboard Super Admin
- [x] audit dashboard Admin
- [x] audit dashboard Asesor
- [x] audit dashboard Pesantren
- [x] kumpulkan temuan visual dan operasional per role

### UI Polish
- [x] rapikan hierarchy, spacing, badge, CTA untuk blocker P0/P1
- [x] rapikan empty state, error state, success feedback untuk flow MVP
- [x] rapikan konsistensi card, table, filter, action button untuk flow MVP
- [x] rapikan wording yang masih ambigu untuk operator pada flow MVP

### Browser Audit
- [x] smoke flow nyata Super Admin
- [x] smoke flow nyata Admin
- [x] smoke flow nyata Asesor
- [x] smoke flow nyata Pesantren

### Hardening Tambahan
- [x] finalize scoring happy path penuh
- [x] negative-path kecil workflow yang masih tersisa untuk MVP
- [x] audit kontrak frontend-backend dari temuan UI

### Hygiene
- [x] cleanup docs historis legacy reactive layer bila dibutuhkan
- [x] update scorecard setelah audit UI selesai

## Definisi Selesai Batch Berikutnya

Batch ini selesai per evidence `docs/ui-role-audit-2026-07-07.md`:

- semua dashboard role sudah diaudit nyata,
- temuan UI prioritas tinggi sudah dipatch,
- flow utama per role lolos smoke test browser,
- scorecard diperbarui dari baseline backend-heavy,
- tidak ada gap UX besar yang menghalangi operasi harian pada smoke browser 19 route.

