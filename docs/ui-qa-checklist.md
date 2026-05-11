# UI QA Checklist

Checklist ini dipakai setelah perubahan frontend Metronic.

## Shell

- Sidebar tidak terasa padat atau norak.
- Hover menu clean dan konsisten.
- Breadcrumb muncul dari komponen reusable.
- Font memakai `Inter`.
- Loading progress tidak tampil mengganggu.

## List Views

- Header halaman jelas.
- Table memakai komponen reusable.
- Filter, badge, action, dan button konsisten.
- Action destructive selalu punya confirmation.
- Checkbox dan radio mengikuti standar Metronic.

## Forms and Modals

- Label, helper text, dan error state dekat field.
- Modal memakai header/body/footer reusable.
- Input solid style konsisten.
- SweetAlert aktif untuk aksi utama.

## Detail Pages

- Ringkasan, status, dan aksi utama mudah dipindai.
- Section panjang dipisah logis.
- Empty state jelas ketika data belum ada.

## Dashboard

- Stat card ringkas.
- Chart dan monitoring terbaca.
- Role-aware content sesuai tugas masing-masing role.

## Roles To Check

- Admin
- Pesantren
- Asesor

## Acceptance

- `php artisan test --no-ansi` pass
- `npm run build` pass
- Browser check pada halaman utama pass
