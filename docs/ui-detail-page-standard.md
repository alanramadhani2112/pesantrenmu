<!-- markdownlint-disable MD013 MD032 MD060 -->

# UI Detail Page Standard

Dokumen ini mengunci standar visual halaman detail untuk sistem SPM akreditasi pesantren. Fokus utama: halaman detail akreditasi lintas role harus terlihat seperti satu keluarga UI walau fungsi tiap role berbeda.

## Prinsip

- Base tetap Metronic 8.1.8 dari `C:\laragon\www\dist\dist`.
- Gunakan komponen `x-ui.*` sebelum raw Metronic markup.
- Role boleh punya workflow berbeda, tetapi shell, spacing, metadata, tab, dan action hierarchy harus konsisten.
- Detail page bukan tempat ornamen besar. Konten utama harus cepat terbaca: status, tahapan, metadata, tindakan.
- Tidak mengubah controller, route, policy, status, permission, database, atau workflow bisnis.

## Target Role dan View

| Role | View | Fungsi | Catatan standar |
|---|---|---|---|
| Super Admin | `resources/views/admin/akreditasi/detail.blade.php` | Melihat detail penuh seperti admin | Super admin memakai grammar admin detail |
| Admin | `resources/views/admin/akreditasi/detail.blade.php` | Review, validasi, audit, keputusan | Reference detail page |
| Admin | `resources/views/admin/akreditasi/detail/tabs/*` | Profil, IPM, SDM, EDPM, dokumen, instrumen, audit | Partial harus mengikuti density detail |
| Pesantren | `resources/views/pesantren/akreditasi-detail.blade.php` | Pantau progres, dokumen, catatan, banding | Ikuti grammar admin tanpa kehilangan workflow pesantren |
| Asesor | `resources/views/asesor/akreditasi-detail.blade.php` | Visitasi, scoring, catatan, laporan | Ikuti grammar admin tanpa kehilangan workflow asesor |
| Asesor | `resources/views/asesor/akreditasi-detail/*` | Tab/partial asesor | Partial harus mengikuti density detail |
| Admin | `resources/views/admin/banding/detail.blade.php` | Detail banding | Ikuti density dan action hierarchy detail |

## Shell Wajib

Semua detail page wajib memakai satu shell:

```blade
<x-ui.page
    title="Detail Akreditasi"
    subtitle="..."
>
```

Tidak boleh menambahkan header lain jika `x-ui.page` sudah cukup:

- Tidak ada `@section('header')` baru.
- Tidak ada `<x-slot name="header">` baru.
- Tidak ada custom heading besar di luar page shell.

## Detail Hero

Detail hero adalah blok pertama setelah flash/error alert. Struktur wajib:

1. Status badge utama.
2. Identifier ringkas seperti ID/periode/peran.
3. Nama pesantren atau konteks detail.
4. Deskripsi singkat sesuai role.
5. Fokus/tanggal/next step di sisi kanan jika tersedia.

Default visual:

- `card mb-5`
- `card-body p-5`
- `d-flex flex-column flex-lg-row justify-content-between gap-4`
- badge/status di baris atas hero
- tidak ada `p-6`, `p-lg-8`, `mb-6`, atau ornamen besar

## Metadata Summary

Setelah hero, gunakan summary grid konsisten:

```blade
<div class="row g-5 mb-5">
    <div class="col-lg-4">
        <x-ui.stat-card ... />
    </div>
</div>
```

Metadata yang diprioritaskan:

- Status
- Periode/tanggal pengajuan
- Tahapan
- Jadwal visitasi
- Tim/role asesor
- Dokumen/scoring summary

Role boleh menyembunyikan metadata yang tidak relevan, tetapi posisi/status utama harus tetap mudah ditemukan.

## Workflow Stepper

Jika halaman terkait akreditasi, workflow stepper diletakkan setelah metadata summary.

```blade
<x-akreditasi.workflow-stepper class="mb-5" />
```

Tidak boleh ada stepper dengan spacing berbeda antar role.

## Tabs dan Sections

Urutan section yang dianjurkan:

1. Ringkasan / Profil
2. IPM
3. SDM
4. EDPM / IPR
5. Dokumen
6. Instrumen / Penilaian
7. Laporan / Catatan / Revisi
8. Riwayat / Audit / Banding

Default visual:

- tab wrapper `mb-5`
- section card `mb-5`
- body `p-5`
- grid `row g-5` atau `row g-4` untuk konten padat

## Action Hierarchy

- Maksimal satu `primary` action per section.
- Workflow positif yang sangat utama boleh `primary` atau `success`.
- Action pendukung gunakan `light`, `secondary`, atau `light-*`.
- Destructive action gunakan `danger`.
- Banyak action gunakan `x-ui.action-menu` atau modal section action.
- Modal trigger harus dekat dengan section yang dipengaruhi.

## Status dan Badge

- Status akreditasi wajib lewat `App\Support\AkreditasiStatusPresenter`.
- Gunakan `x-ui.status-badge` untuk status utama.
- Gunakan `x-ui.badge` untuk role, ID, periode, atau marker kecil.
- Tidak membuat local akreditasi status map baru.

## Copy dan Empty State

Copy wajib Indonesian-first, formal, operasional, dan domain SPM.

Contoh baik:

- “Dokumen belum diunggah.”
- “Catatan asesor belum tersedia.”
- “Tahapan validasi admin belum dimulai.”
- “Jadwal visitasi belum ditentukan.”

Contoh buruk:

- “No data found.”
- “Nothing here.”
- “Item kosong.”
- Copy generik SaaS/CRM/e-commerce.

## Forbidden Detail Patterns

- `row g-6`, `gap-6`, `p-6`, `p-lg-8`, `mb-6` untuk struktur utama detail.
- `bg-light-*` sebagai surface besar.
- `shadow-sm` untuk card/detail block.
- `text-uppercase` untuk label panjang.
- Raw `<button class="btn ...">` jika `x-ui.button` bisa dipakai.
- Raw modal header/body/footer jika `x-ui.modal-*` bisa dipakai.
- Local akreditasi status map.

## Acceptance Checklist

Satu detail page dianggap pass jika:

- Shell memakai `x-ui.page`.
- Hero memakai struktur status + konteks + metadata ringkas.
- Metadata summary langsung terlihat setelah hero.
- Workflow stepper konsisten jika relevan.
- Tabs/sections memakai density yang sama.
- Status utama memakai `AkreditasiStatusPresenter`.
- Maksimal satu primary action per section.
- Empty/copy tetap domain SPM.
- Tidak ada drift pattern di daftar forbidden.
- `php artisan view:cache` dan `MetronicFrontendTest` pass.
