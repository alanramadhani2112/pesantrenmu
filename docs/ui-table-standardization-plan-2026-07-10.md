# Table Standardization Plan — 2026-07-10

## Goal

Standardisasi semua tabel di seluruh role supaya konsisten:
- Search: `x-datatable.search`
- Filter controls: `x-ui.select` / `x-ui.input` di dalam `<form>` dengan `id`
- Per-page: `x-ui.table-per-page`
- Pagination: `x-ui.pagination`
- Table wrapper: `x-ui.table` (Metronic card + thead/tbody + footer)

## Target Component

Semua tabel operasional pindah ke `x-ui.table` pattern:

```blade
<x-ui.table title="..." subtitle="..." :records="$paginator">
    <x-slot name="toolbar">
        <x-ui.button>Tambah</x-ui.button>
    </x-slot>

    <x-slot name="filters">
        <form id="..." method="GET" action="...">
            <x-datatable.search name="search" :value="$search" form="..." />
            <x-ui.select name="status" ... />
            <x-ui.table-per-page form="..." />
        </form>
    </x-slot>

    <x-slot name="thead">
        <x-ui.table-th>Name</x-ui.table-th>
        <x-ui.table-th align="end">Aksi</x-ui.table-th>
    </x-slot>

    <x-slot name="tbody">
        @forelse($items as $item)
            <tr>...</tr>
        @empty
            <tr><td colspan="..."><x-ui.empty-state ... /></td></tr>
        @endforelse
    </x-slot>
</x-ui.table>
```

Pagination otomatis dari `x-ui.table` via `$records->links()` + `x-ui.table-per-page` di footer.

## Perubahan vs Saat Ini

| Halaman | Perubahan |
|---------|-----------|
| Admin Roles | `x-ui.simple-table` + raw form → `x-ui.table` |
| Admin Master Dokumen | `x-ui.simple-table` + raw form → `x-ui.table` |
| Admin Kategori Dokumen | `x-ui.simple-table` + raw form → `x-ui.table` |
| Admin Role-Permission | `x-ui.simple-table` + raw form → `x-ui.table` |
| Admin Akreditasi | `x-ui.simple-table` + footer manual → `x-ui.table` |
| Admin Akreditasi Detail Audit Trail | `->links()` → `x-ui.table` |
| Admin Asesor List | raw form → `x-ui.table` |
| Admin Pesantren List | raw form → `x-ui.table` |
| Admin Trash | `x-datatable.layout` → `x-ui.table` |
| Admin Failed Notif | `x-datatable.layout` → `x-ui.table` |
| Admin Banding | `x-ui.simple-table` → `x-ui.table` |
| Asesor Akreditasi | `x-datatable.layout` → `x-ui.table` |
| Pesantren Akreditasi | `x-ui.simple-table` → `x-ui.table` |
| Pesantren SDM | `x-ui.simple-table` → `x-ui.table` |

**TIDAK diubah:**
- Detail pages (tab partials dengan tabel statis/read-only)
- EDPM komponen list (bukan tabel operasional)
- Score tables (instrumen)
- Dashboard stat cards/metrics

## Execution Order

### Slice 1: Admin list pages (highest volume)
- Roles
- Master Dokumen
- Kategori Dokumen
- Role-Permission

### Slice 2: Admin operational pages
- Akreditasi list + audit-trail
- Asesor list
- Pesantren list
- Trash
- Failed Notif
- Banding

### Slice 3: Asesor + Pesantren
- Asesor Akreditasi list
- Pesantren Akreditasi list
- Pesantren SDM

## Definition of Done

Per halaman:
- pakai `x-ui.table` atau `x-datatable.layout` (consistent wrapper)
- search pakai `x-datatable.search`
- filter pakai `x-ui.select` / `x-ui.input`
- per-page pakai `x-ui.table-per-page`
- pagination pakai `x-ui.pagination` atau footer dari `x-ui.table`
- empty state pakai `x-ui.empty-state`
- no raw `{{ $x->links() }}` atau raw `<select class="form-select">`

## Verification

Per slice:
- `php artisan test tests/Feature/MetronicFrontendTest.php`
- `php artisan test tests/Feature/<role>Test.php` yang relevan
- `npm run build`
- Smoke route test
