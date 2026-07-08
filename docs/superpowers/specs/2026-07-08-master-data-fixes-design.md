# Master Data Fixes Design

## Goal

Fix confirmed Master Data defects with the smallest safe diff:

1. Role permission matrix must not revoke hidden permissions when filtered.
2. Master EDPM edit buttons must not allow stored JS execution or broken JS payloads.
3. Master Dokumen files with restricted category visibility must not be exposed through direct public URLs.
4. Sort/action UI should match backend behavior and avoid confusing forbidden actions.

## Scope

In scope:

- Admin Role Permission Matrix.
- Admin Master EDPM.
- Admin Master Dokumen and shared document library.
- Admin Master Kategori Dokumen.
- Admin Role Management sort/action polish.
- Focused regression tests for each fixed behavior.

Out of scope:

- Redesigning Master Data screens.
- Reworking the permission model.
- Migrating existing production files between disks automatically.
- Adding new document workflows beyond secure download.

## Approach

Use TDD. Add failing tests for the confirmed bugs first, then make minimum code changes to pass.

Recommended execution order:

1. Security/data-loss fixes:
   - role permission preserve-hidden-grants behavior
   - EDPM safe JS serialization
   - document authenticated download
2. UX/correctness polish:
   - sort param normalization/whitelisting
   - hide canonical role mutation actions

## Design

### 1. Role permission matrix

Problem: filtered/search matrix submits only visible checkboxes. Current save builds `$newGranted` from submitted matrix and calls `sync()`, which revokes non-visible grants.

Fix: include the visible permission scope in the form and preserve existing grants outside that scope server-side.

Server behavior per role:

- `$visiblePermissionIds` = submitted `visible_permission_ids`.
- `$submittedGrantedIds` = checked permissions in request for that role.
- `$existingIds` = current role permissions.
- `$preservedIds` = existing IDs not in visible scope.
- `$newGranted` = preserved IDs + submitted visible checked IDs.
- `sync($newGranted)` only if changed.

If no visible scope is submitted, preserve current behavior for full matrix submit.

### 2. Master EDPM edit serialization

Problem: `butir_pernyataan` is inserted into an Alpine click handler with `addslashes()` and a JS template literal. Backticks or `${...}` can break JS or execute when clicked.

Fix: serialize all modal arguments with Laravel `@js` / `Js::from` instead of manual quoting. Do not use template literals for DB content.

### 3. Master Dokumen private download

Problem: uploads use the public disk and views expose `Storage::url($file_path)`, so category visibility is bypassed by direct URL sharing.

Fix:

- Store newly uploaded master documents on a private disk/path.
- Add an authenticated download route that loads the document and authorizes by role/category visibility.
- Replace direct `Storage::url(...)` links in admin and user document views with the download route.
- Keep backward-compatible read fallback for existing public-disk files so old fixtures/local data still work.

Authorization:

- Admin/super admin can download all documents.
- Pesantren can download public + pesantren_secret active documents.
- Asesor can download public + asesor_secret active documents.
- Unknown/unauthenticated users get 403.

### 4. Sort/action UI polish

Problem: views send `sort`/`direction`; controllers read `sortField`/`sortAsc`. Sorting links are no-op. Repositories also pass raw sort columns to `orderBy()`.

Fix:

- Controllers accept both formats and normalize to existing `$sortField`/`$sortAsc` variables.
- Whitelist sortable columns per module before calling repositories/query builders.
- Views may keep their existing `sort`/`direction` links after normalization.
- Hide edit/delete actions for canonical roles id `1..4` because backend forbids mutations.

## Tests

Add focused feature tests:

- `RolePermissionMatrixTest`
  - filtered save preserves permissions not visible in the current filter
  - visible unchecked permission is revoked
- `MasterEdpmSecurityTest`
  - edit button safely serializes backticks and `${...}` text
- `DocumentDownloadAuthorizationTest`
  - secret asesor doc is not directly linked with `/storage/...`
  - pesantren cannot download asesor_secret doc
  - asesor can download asesor_secret doc
  - admin can download all docs
- `MasterDataSortTest`
  - master dokumen accepts `sort`/`direction`
  - master kategori accepts `sort`/`direction`
  - roles accepts `sort`/`direction`
  - invalid sort falls back safely
- `RoleManagementUiTest`
  - canonical roles do not show edit/delete actions

## Verification

Run:

```bash
php artisan test tests/Feature/RolePermissionMatrixTest.php \
  tests/Feature/MasterEdpmSecurityTest.php \
  tests/Feature/DocumentDownloadAuthorizationTest.php \
  tests/Feature/MasterDataSortTest.php \
  tests/Feature/RoleManagementUiTest.php
php artisan test
npm run build
git diff --check
```

## Risks

- Existing public document files may remain accessible if already under `public/storage`; the code stops generating direct links and stores new uploads privately. A production cleanup/migration can be handled separately if needed.
- Download response behavior depends on local filesystem state. Tests should use `Storage::fake()` to avoid touching real files.
