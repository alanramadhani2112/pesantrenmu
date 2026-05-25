# Update Modul Manajemen Hak Akses

Tanggal: 24 Mei 2026

## Ringkasan

Dokumen ini mencatat perubahan terbaru pada modul **Manajemen Hak Akses** untuk super admin. Fokus pekerjaan adalah membuat halaman `/admin/master-role-permission` lebih layak dipakai secara operasional, lebih konsisten dengan reusable UI component Metronic, dan lebih aman untuk perubahan permission yang berdampak ke role sistem.

Scope ini juga mencakup perbaikan kecil pada security header lokal agar browser console di `http://spm_fix.test` tidak menampilkan error yang mengganggu proses QA visual.

## Tujuan Perubahan

- Super admin bisa mencari permission berdasarkan label, key, atau deskripsi.
- Super admin bisa memfilter permission berdasarkan grup fitur.
- Super admin bisa memberi atau mencabut permission secara massal hanya pada permission yang sedang terlihat.
- Super admin bisa melihat ringkasan perubahan sebelum menekan tombol simpan.
- Super admin bisa melihat riwayat perubahan role dan permission dari audit log.
- Halaman tetap memakai komponen UI reusable yang sudah distandarkan.
- Console browser lokal bersih dari error COOP pada domain `.test`.

## File Yang Berubah

| File | Perubahan |
| --- | --- |
| `resources/views/livewire/pages/admin/master/role-permission.blade.php` | Menambah search, group filter, bulk action, change summary, audit history, dan markup reusable Metronic. |
| `app/Http/Middleware/SecurityHeaders.php` | Membatasi pengiriman `Cross-Origin-Opener-Policy` hanya untuk HTTPS atau loopback host. |
| `tests/Feature/Livewire/RolePermissionMatrixTest.php` | Menambah coverage untuk search/filter, bulk visible action, audit history, dan sinkronisasi event notifikasi. |
| `tests/Feature/SecurityHeadersTest.php` | Menambah coverage untuk perilaku COOP pada domain `.test` dan HTTPS. |

## Detail Implementasi

### 1. Search dan Filter Permission

Halaman matriks sekarang memiliki state:

- `search`
- `groupFilter`

Filter diterapkan pada data permission berdasarkan:

- `key`
- `label`
- `description`
- `group`

Dengan ini, super admin bisa mempersempit matriks sebelum melakukan perubahan. Contoh QA yang sudah dicek: filter grup `Master` dan search `EDPM` hanya menampilkan permission `master.edpm`.

### 2. Bulk Action Berdasarkan Permission Terlihat

Ditambahkan action:

- `grantVisibleForRole(int $roleId)`
- `revokeVisibleForRole(int $roleId)`
- `grantVisibleForAllRoles()`
- `revokeVisibleForAllRoles()`

Perilaku penting:

- Bulk action hanya memengaruhi permission yang sedang terlihat setelah search/filter.
- Role yang bisa diedit tetap dibatasi ke `Admin`, `Asesor`, dan `Pesantren`.
- `Super Admin` tetap tidak ditampilkan karena memiliki full access melalui bypass di `User::hasPermission()`.

### 3. Ringkasan Perubahan Belum Tersimpan

Halaman sekarang menghitung perbedaan antara state matrix saat ini dan data pivot database.

Ringkasan ini menampilkan:

- permission yang akan ditambahkan untuk role tertentu,
- permission yang akan dicabut dari role tertentu.

Tujuannya adalah mengurangi risiko salah simpan saat super admin melakukan perubahan massal.

### 4. Riwayat Perubahan

Ditambahkan panel **Riwayat Perubahan** dari `PermissionAuditLog`.

Informasi yang ditampilkan:

- waktu perubahan,
- aktor,
- role terdampak,
- permission yang ditambahkan,
- permission yang dicabut.

Audit log tetap bersifat immutable melalui model `PermissionAuditLog`.

### 5. Reusable UI Component

Halaman diarahkan memakai komponen UI reusable:

- `x-ui.page`
- `x-ui.section-card`
- `x-ui.simple-table`
- `x-ui.input`
- `x-ui.filter-select`
- `x-ui.button`
- `x-ui.badge`
- `x-ui.checkbox`
- `x-ui.alert`
- `x-ui.empty-state`

Ini menjaga halaman tetap konsisten dengan arah visual Metronic yang sudah dipakai di project.

### 6. Perbaikan COOP Header Untuk Local QA

Sebelumnya browser menampilkan error:

```text
The Cross-Origin-Opener-Policy header has been ignored, because the URL's origin was untrustworthy.
```

Penyebabnya adalah header `Cross-Origin-Opener-Policy: same-origin` dikirim pada `http://spm_fix.test`. Browser mengabaikan COOP pada origin HTTP yang tidak dianggap trustworthy.

Perubahan:

- COOP tetap dikirim pada HTTPS.
- COOP tetap dikirim pada loopback host: `localhost`, `127.0.0.1`, dan `::1`.
- COOP tidak dikirim pada HTTP `.test`, sehingga console lokal bersih untuk QA.

## Verifikasi

Command yang sudah dijalankan:

```bash
php artisan test tests/Feature/Livewire/RolePermissionMatrixTest.php tests/Unit/PermissionSystemTest.php tests/Unit/SidebarMenuServiceTest.php tests/Feature/SecurityHeadersTest.php --stop-on-failure
```

Hasil:

```text
55 passed, 440 assertions
```

Command compile Blade:

```bash
php artisan view:cache --no-ansi
```

Hasil:

```text
Blade templates cached successfully.
```

QA browser:

- URL: `http://spm_fix.test/admin/master-role-permission`
- Login: super admin
- Filter grup `Master` + search `EDPM` menampilkan 1 permission.
- Console setelah reload: `0 errors`, `0 warnings`.

## Catatan Teknis

- `npm run build` tidak dijalankan karena perubahan scope ini tidak menyentuh CSS atau JS asset.
- Modul masih menggunakan Livewire Volt karena halaman ini membutuhkan state interaktif.
- Bulk action belum langsung menyimpan ke database. Super admin tetap harus menekan **Simpan Perubahan**, sesuai pola aman untuk perubahan permission.

## Status

Status scope ini: **selesai dan terverifikasi secara targeted**.

Potential follow-up:

- Menambah pagination atau virtual grouping jika jumlah permission bertambah sangat besar.
- Menambah label kategori permission yang lebih user-friendly jika permission catalog makin kompleks.
- Menambah audit filter berdasarkan role, aktor, atau tanggal jika volume audit log sudah tinggi.
