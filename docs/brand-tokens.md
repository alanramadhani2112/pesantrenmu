# Brand Tokens

Dokumen ini mendefinisikan token warna SPM untuk penggunaan Metronic dan komponen UI baru.

## Core Colors

```text
primary: #1e3a5f
primary-active: #162c49
primary-hover: #24476f
primary-light: #e8eef5
primary-subtle: #f2f6fb
primary-rgb: 30, 58, 95
```

Primary digunakan untuk:

- aksi utama,
- menu aktif,
- link penting,
- focus ring,
- workflow step aktif,
- elemen identitas sistem.

## Semantic Colors

```text
success: #10b981
success-active: #059669
success-light: #ecfdf5

warning: #f59e0b
warning-active: #d97706
warning-light: #fffbeb

danger: #ef4444
danger-active: #dc2626
danger-light: #fef2f2

info: #0088ff
info-active: #006fd1
info-light: #eff6ff
```

## Workflow Mapping

```text
Pengajuan: primary or info
Review Asesor: warning
Visitasi: info
Validasi: primary
Berhasil: success
Ditolak: danger
Perlu Perbaikan: warning
Terkunci: primary-light with primary text
```

## Implementation

Token Metronic disesuaikan di:

```text
resources/css/metronic-overrides.css
```

File tersebut mengisi variabel Bootstrap/Metronic seperti:

```text
--bs-primary
--bs-primary-active
--bs-primary-light
--bs-primary-rgb
--bs-success
--bs-warning
--bs-danger
--bs-info
```

## Usage Rule

- Gunakan class Metronic standar jika sudah cukup, misalnya `btn btn-primary` atau `badge badge-light-primary`.
- Gunakan class custom `spm-*` hanya untuk pola domain SPM, misalnya workflow step.
- Jangan hardcode warna baru di Blade kecuali sedang migrasi file lama secara bertahap.
- Saat membuat komponen Blade baru, gunakan variant seperti `primary`, `success`, `warning`, `danger`, dan `info`.
