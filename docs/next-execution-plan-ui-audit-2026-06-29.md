# Next Execution Plan - UI Audit and Final MVP Push

Tanggal: 29 Juni 2026  
Status: aktif sesudah batch backend P1 selesai

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
- [ ] audit dashboard Super Admin
- [ ] audit dashboard Admin
- [ ] audit dashboard Asesor
- [ ] audit dashboard Pesantren
- [ ] kumpulkan temuan visual dan operasional per role

### UI Polish
- [ ] rapikan hierarchy, spacing, badge, CTA
- [ ] rapikan empty state, error state, success feedback
- [ ] rapikan konsistensi card, table, filter, action button
- [ ] rapikan wording yang masih ambigu untuk operator

### Browser Audit
- [ ] smoke flow nyata Super Admin
- [ ] smoke flow nyata Admin
- [ ] smoke flow nyata Asesor
- [ ] smoke flow nyata Pesantren

### Hardening Tambahan
- [ ] finalize scoring happy path penuh
- [ ] negative-path kecil workflow yang masih tersisa
- [ ] audit kontrak frontend-backend dari temuan UI

### Hygiene
- [ ] cleanup docs historis legacy reactive layer bila dibutuhkan
- [ ] update scorecard setelah audit UI selesai

## Definisi Selesai Batch Berikutnya

Batch ini dianggap selesai bila:

- semua dashboard role sudah diaudit nyata,
- temuan UI prioritas tinggi sudah dipatch,
- flow utama per role lolos smoke test browser,
- scorecard bisa dinaikkan dari baseline backend-heavy saat ini,
- tidak ada gap UX besar yang menghalangi operasi harian.

