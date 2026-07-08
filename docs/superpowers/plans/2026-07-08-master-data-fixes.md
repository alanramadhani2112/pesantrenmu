# Master Data Fixes Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix confirmed Master Data data-loss, XSS, private document, sort, and role-action defects.

**Architecture:** Keep the existing Laravel controllers/services/views. Add focused feature tests first, then make the smallest controller/view/service changes needed to pass. Store new master documents privately and serve them through one authenticated download route with legacy public-file fallback.

**Tech Stack:** Laravel 12, PHPUnit 11, Blade/Alpine, Eloquent, Laravel Storage.

---

## Files

- Create: `tests/Feature/RolePermissionMatrixTest.php`
  - Regression coverage for filtered matrix saves preserving hidden grants.
- Create: `tests/Feature/MasterEdpmSecurityTest.php`
  - Regression coverage for safe EDPM edit-button JS serialization.
- Create: `tests/Feature/DocumentDownloadAuthorizationTest.php`
  - Regression coverage for document download authorization and no direct public links.
- Create: `tests/Feature/MasterDataSortTest.php`
  - Regression coverage for `sort`/`direction` param support and sort whitelists.
- Create: `tests/Feature/RoleManagementUiTest.php`
  - Regression coverage for hiding canonical role mutation actions.
- Modify: `routes/web.php`
  - Add `documents/{document}/download` before `documents/{doc?}` wildcard route.
- Modify: `app/Http/Controllers/DocumentController.php`
  - Add authenticated `download()` method.
- Modify: `app/Services/DocumentService.php`
  - Store new master documents on `local`, cleanup both `local` and legacy `public`.
- Modify: `app/Http/Controllers/Admin/RolePermissionController.php`
  - Preserve hidden grants when filtered matrix submits visible scope.
- Modify: `resources/views/admin/role-permission/index.blade.php`
  - Submit visible permission IDs.
- Modify: `resources/views/admin/master-edpm/index.blade.php`
  - Replace manual JS string quoting with `Js::from`/`@js`.
- Modify: `resources/views/documents/index.blade.php`
  - Use authenticated download route.
- Modify: `resources/views/admin/master-dokumen/index.blade.php`
  - Use authenticated download route.
- Modify: `app/Http/Controllers/Admin/MasterDokumenController.php`
  - Normalize and whitelist sort params.
- Modify: `app/Http/Controllers/Admin/MasterKategoriDokumenController.php`
  - Normalize and whitelist sort params.
- Modify: `app/Http/Controllers/Admin/RoleController.php`
  - Normalize and whitelist sort params.
- Modify: `resources/views/admin/roles/index.blade.php`
  - Hide edit/delete controls for role IDs `1..4`.

---

### Task 1: Role permission matrix preserves hidden grants

**Files:**
- Create: `tests/Feature/RolePermissionMatrixTest.php`
- Modify: `app/Http/Controllers/Admin/RolePermissionController.php`
- Modify: `resources/views/admin/role-permission/index.blade.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/RolePermissionMatrixTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_filtered_save_preserves_hidden_granted_permissions(): void
    {
        $this->seedBasePermissions();
        $superAdmin = User::factory()->create(['role_id' => 4, 'email_verified_at' => now()]);
        $adminRole = Role::findOrFail(1);
        $visible = Permission::where('key', 'akreditasi.view')->firstOrFail();
        $hidden = Permission::where('key', 'master.dokumen')->firstOrFail();
        $adminRole->permissions()->sync([$visible->id, $hidden->id]);

        $this->actingAs($superAdmin)->post(route('admin.role-permission.save'), [
            'visible_permission_ids' => [$visible->id],
            'matrix' => [
                $adminRole->id => [$visible->id => 'on'],
            ],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertTrue($adminRole->fresh()->permissions()->whereKey($visible->id)->exists());
        $this->assertTrue($adminRole->fresh()->permissions()->whereKey($hidden->id)->exists());
    }

    public function test_filtered_save_revokes_visible_unchecked_permission_only(): void
    {
        $this->seedBasePermissions();
        $superAdmin = User::factory()->create(['role_id' => 4, 'email_verified_at' => now()]);
        $adminRole = Role::findOrFail(1);
        $visible = Permission::where('key', 'akreditasi.view')->firstOrFail();
        $hidden = Permission::where('key', 'master.dokumen')->firstOrFail();
        $adminRole->permissions()->sync([$visible->id, $hidden->id]);

        $this->actingAs($superAdmin)->post(route('admin.role-permission.save'), [
            'visible_permission_ids' => [$visible->id],
            'matrix' => [
                $adminRole->id => [],
            ],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertFalse($adminRole->fresh()->permissions()->whereKey($visible->id)->exists());
        $this->assertTrue($adminRole->fresh()->permissions()->whereKey($hidden->id)->exists());
    }

    private function seedBasePermissions(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
    }
}
```

- [ ] **Step 2: Run RED check**

```bash
php artisan test tests/Feature/RolePermissionMatrixTest.php
```

Expected: first test FAILS because hidden `master.dokumen` permission is revoked.

- [ ] **Step 3: Implement minimum controller fix**

In `app/Http/Controllers/Admin/RolePermissionController.php`, replace the `$newGranted` block inside `save()` with:

```php
$visiblePermissionIds = collect(request()->input('visible_permission_ids', []))
    ->map(fn ($id) => (int) $id)
    ->all();
$hasVisibleScope = ! empty($visiblePermissionIds);

DB::transaction(function () use ($roles, $matrixInput, $permissionKeys, $visiblePermissionIds, $hasVisibleScope) {
    $actor = auth()->user();

    foreach ($roles as $role) {
        $before = $role->permissions()->pluck('permissions.id')->all();

        $checkedVisible = collect($matrixInput[$role->id] ?? [])
            ->keys()
            ->map(fn ($v) => (int) $v)
            ->all();

        $newGranted = $hasVisibleScope
            ? array_values(array_unique(array_merge(
                array_diff($before, $visiblePermissionIds),
                $checkedVisible
            )))
            : $checkedVisible;

        sort($before);
        sort($newGranted);

        if ($before !== $newGranted) {
            $role->permissions()->sync($newGranted);

            $added = array_diff($newGranted, $before);
            $removed = array_diff($before, $newGranted);

            PermissionAuditLog::create([
                'user_id' => $actor->id,
                'role_id' => $role->id,
                'permissions_added' => collect($added)->map(fn ($id) => $permissionKeys[$id] ?? $id)->values()->all(),
                'permissions_removed' => collect($removed)->map(fn ($id) => $permissionKeys[$id] ?? $id)->values()->all(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }
    }
});
```

Keep the method signature unchanged.

- [ ] **Step 4: Emit visible permission IDs from view**

In `resources/views/admin/role-permission/index.blade.php`, inside `<form id="matrix-form" ...>` after `@csrf`, add:

```blade
@foreach($permissions as $permission)
    <input type="hidden" name="visible_permission_ids[]" value="{{ $permission->id }}">
@endforeach
```

- [ ] **Step 5: Run GREEN check**

```bash
php artisan test tests/Feature/RolePermissionMatrixTest.php
```

Expected: PASS.

- [ ] **Step 6: Commit task**

```bash
git add tests/Feature/RolePermissionMatrixTest.php app/Http/Controllers/Admin/RolePermissionController.php resources/views/admin/role-permission/index.blade.php
git commit -m "fix(master): preserve filtered permissions"
```

---

### Task 2: Master EDPM safe JS serialization

**Files:**
- Create: `tests/Feature/MasterEdpmSecurityTest.php`
- Modify: `resources/views/admin/master-edpm/index.blade.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/MasterEdpmSecurityTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\MasterEdpmButir;
use App\Models\MasterEdpmKomponen;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterEdpmSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_edpm_edit_button_serializes_js_sensitive_text_safely(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create(['role_id' => 1, 'email_verified_at' => now()]);
        $komponen = MasterEdpmKomponen::create(['nama' => 'Komponen Aman', 'ipr' => false]);
        $payload = "Butir `);window.__xss=1;// \\${alert('x')} \" quote ' single";

        MasterEdpmButir::create([
            'komponen_id' => $komponen->id,
            'no_sk' => 'SK-1',
            'nomor_butir' => '1.1',
            'butir_pernyataan' => $payload,
        ]);

        $html = $this->actingAs($admin)
            ->get(route('admin.master-edpm'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('`);window.__xss=1;//', $html);
        $this->assertStringNotContainsString('${alert', $html);
        $this->assertStringContainsString('openButirModal', $html);
        $this->assertStringContainsString('Butir', $html);
    }
}
```

- [ ] **Step 2: Run RED check**

```bash
php artisan test tests/Feature/MasterEdpmSecurityTest.php
```

Expected: FAIL because raw template-literal content appears in the click handler.

- [ ] **Step 3: Implement safe serialization**

In `resources/views/admin/master-edpm/index.blade.php`, replace the edit button click handler:

```blade
x-on:click="openButirModal({{ $komponen->id }}, {{ $butir->id }}, '{{ addslashes($butir->no_sk ?? '') }}', '{{ addslashes($butir->nomor_butir) }}', `{{ addslashes($butir->butir_pernyataan) }}`)"
```

with:

```blade
x-on:click="openButirModal({{ \Illuminate\Support\Js::from($komponen->id) }}, {{ \Illuminate\Support\Js::from($butir->id) }}, {{ \Illuminate\Support\Js::from($butir->no_sk ?? '') }}, {{ \Illuminate\Support\Js::from($butir->nomor_butir) }}, {{ \Illuminate\Support\Js::from($butir->butir_pernyataan) }})"
```

- [ ] **Step 4: Run GREEN check**

```bash
php artisan test tests/Feature/MasterEdpmSecurityTest.php
```

Expected: PASS.

- [ ] **Step 5: Build check for Blade/JS syntax**

```bash
npm run build
```

Expected: PASS.

- [ ] **Step 6: Commit task**

```bash
git add tests/Feature/MasterEdpmSecurityTest.php resources/views/admin/master-edpm/index.blade.php
git commit -m "fix(master): escape edpm edit payloads"
```

---

### Task 3: Authenticated private document downloads

**Files:**
- Create: `tests/Feature/DocumentDownloadAuthorizationTest.php`
- Create/Modify: `tests/Feature/DocumentServiceTest.php`
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/DocumentController.php`
- Modify: `app/Services/DocumentService.php`
- Modify: `resources/views/documents/index.blade.php`
- Modify: `resources/views/admin/master-dokumen/index.blade.php`

- [ ] **Step 1: Write download authorization tests**

Create `tests/Feature/DocumentDownloadAuthorizationTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentDownloadAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_secret_document_view_uses_authenticated_download_route_not_public_storage_url(): void
    {
        Storage::fake('local');
        $this->seedBasePermissions();
        $asesor = User::factory()->create(['role_id' => 2, 'email_verified_at' => now()]);
        $category = $this->category('Asesor Secret', 'asesor_secret', DocumentCategory::VISIBILITY_ASESOR_SECRET);
        $document = $this->document($category, 'documents/secret.pdf');
        Storage::disk('local')->put($document->file_path, 'secret');

        $this->actingAs($asesor)
            ->get(route('documents.index', ['doc' => $category->slug]))
            ->assertOk()
            ->assertSee(route('documents.download', $document), false)
            ->assertDontSee('/storage/documents/secret.pdf', false);
    }

    public function test_pesantren_cannot_download_asesor_secret_document(): void
    {
        Storage::fake('local');
        $this->seedBasePermissions();
        $pesantren = User::factory()->create(['role_id' => 3, 'email_verified_at' => now()]);
        $document = $this->document(
            $this->category('Asesor Secret', 'asesor_secret', DocumentCategory::VISIBILITY_ASESOR_SECRET),
            'documents/asesor-secret.pdf'
        );
        Storage::disk('local')->put($document->file_path, 'secret');

        $this->actingAs($pesantren)
            ->get(route('documents.download', $document))
            ->assertForbidden();
    }

    public function test_asesor_can_download_asesor_secret_document(): void
    {
        Storage::fake('local');
        $this->seedBasePermissions();
        $asesor = User::factory()->create(['role_id' => 2, 'email_verified_at' => now()]);
        $document = $this->document(
            $this->category('Asesor Secret', 'asesor_secret', DocumentCategory::VISIBILITY_ASESOR_SECRET),
            'documents/asesor-secret.pdf'
        );
        Storage::disk('local')->put($document->file_path, 'secret');

        $this->actingAs($asesor)
            ->get(route('documents.download', $document))
            ->assertOk();
    }

    public function test_admin_can_download_any_document(): void
    {
        Storage::fake('local');
        $this->seedBasePermissions();
        $admin = User::factory()->create(['role_id' => 1, 'email_verified_at' => now()]);
        $document = $this->document(
            $this->category('Pesantren Secret', 'pesantren_secret', DocumentCategory::VISIBILITY_PESANTREN_SECRET),
            'documents/pesantren-secret.pdf'
        );
        Storage::disk('local')->put($document->file_path, 'secret');

        $this->actingAs($admin)
            ->get(route('documents.download', $document))
            ->assertOk();
    }

    public function test_legacy_public_disk_file_downloads_through_authenticated_route(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $this->seedBasePermissions();
        $admin = User::factory()->create(['role_id' => 1, 'email_verified_at' => now()]);
        $document = $this->document(
            $this->category('Public', 'public_doc', DocumentCategory::VISIBILITY_PUBLIC),
            'documents/legacy.pdf'
        );
        Storage::disk('public')->put($document->file_path, 'legacy');

        $this->actingAs($admin)
            ->get(route('documents.download', $document))
            ->assertOk();
    }

    private function seedBasePermissions(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
    }

    private function category(string $name, string $slug, string $visibility): DocumentCategory
    {
        return DocumentCategory::create([
            'name' => $name,
            'slug' => $slug,
            'icon' => 'document',
            'visibility' => $visibility,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function document(DocumentCategory $category, string $path): Document
    {
        return Document::create([
            'title' => 'Test Document',
            'category_id' => $category->id,
            'type' => $category->slug,
            'status' => 1,
            'file_path' => $path,
            'is_pesantren' => $category->visibility !== DocumentCategory::VISIBILITY_ASESOR_SECRET,
            'is_asesor' => $category->visibility !== DocumentCategory::VISIBILITY_PESANTREN_SECRET,
        ]);
    }
}
```

- [ ] **Step 2: Write private storage service test**

Create `tests/Feature/DocumentServiceTest.php` if missing, or append this test if the file exists:

```php
<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\User;
use App\Services\DocumentService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_document_stores_new_uploads_on_private_local_disk(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create(['role_id' => 1, 'email_verified_at' => now()]);
        $this->actingAs($admin);
        $category = DocumentCategory::create([
            'name' => 'Private Docs',
            'slug' => 'private_docs',
            'icon' => 'document',
            'visibility' => DocumentCategory::VISIBILITY_ASESOR_SECRET,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        app(DocumentService::class)->saveDocument([
            'title' => 'Private Template',
            'status' => 1,
            'category_id' => $category->id,
            'description' => 'Private',
        ], null, UploadedFile::fake()->create('template.pdf', 10, 'application/pdf'));

        $path = Document::firstOrFail()->file_path;
        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
    }
}
```

If `tests/Feature/DocumentServiceTest.php` already exists, merge imports and only add the method.

- [ ] **Step 3: Run RED check**

```bash
php artisan test tests/Feature/DocumentDownloadAuthorizationTest.php tests/Feature/DocumentServiceTest.php
```

Expected: FAIL because route `documents.download` is missing and service stores on public disk.

- [ ] **Step 4: Add download route**

In `routes/web.php`, replace the current documents route block:

```php
Route::get('documents/{doc?}', [App\Http\Controllers\DocumentController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('documents.index');
```

with:

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('documents/{document}/download', [App\Http\Controllers\DocumentController::class, 'download'])
        ->whereNumber('document')
        ->name('documents.download');

    Route::get('documents/{doc?}', [App\Http\Controllers\DocumentController::class, 'index'])
        ->name('documents.index');
});
```

- [ ] **Step 5: Add controller download method**

In `app/Http/Controllers/DocumentController.php`, add imports:

```php
use App\Models\Document;
use App\Models\DocumentCategory;
use Illuminate\Support\Facades\Storage;
```

Then add this method before the closing brace:

```php
public function download(Document $document)
{
    $user = auth()->user();
    abort_unless($user, 403);

    $document->loadMissing('category');
    abort_unless((int) $document->status === 1, 404);
    abort_unless($document->category?->is_active, 404);

    $visibility = $document->category->visibility;
    $allowed = match (true) {
        $user->canAccessAdminArea() => true,
        $user->isAsesor() => in_array($visibility, [
            DocumentCategory::VISIBILITY_PUBLIC,
            DocumentCategory::VISIBILITY_ASESOR_SECRET,
        ], true),
        $user->isPesantren() => in_array($visibility, [
            DocumentCategory::VISIBILITY_PUBLIC,
            DocumentCategory::VISIBILITY_PESANTREN_SECRET,
        ], true),
        default => false,
    };

    abort_unless($allowed, 403);

    $disk = Storage::disk('local')->exists($document->file_path) ? 'local' : 'public';
    abort_unless(Storage::disk($disk)->exists($document->file_path), 404);

    return Storage::disk($disk)->download($document->file_path, basename($document->file_path));
}
```

- [ ] **Step 6: Store new uploads privately and cleanup both disks**

In `app/Services/DocumentService.php`, change upload storage:

```php
$newPath = $newFile->store('documents', 'public');
```

To:

```php
$newPath = $newFile->store('documents', 'local');
```

Change cleanup in the catch block from public-only:

```php
if ($newPath && Storage::disk('public')->exists($newPath)) {
    Storage::disk('public')->delete($newPath);
}
```

To:

```php
if ($newPath && Storage::disk('local')->exists($newPath)) {
    Storage::disk('local')->delete($newPath);
}
```

Change post-update old-file deletion:

```php
if ($newPath && $existingPath && Storage::disk('public')->exists($existingPath)) {
    Storage::disk('public')->delete($existingPath);
}
```

To:

```php
if ($newPath && $existingPath) {
    foreach (['local', 'public'] as $disk) {
        if (Storage::disk($disk)->exists($existingPath)) {
            Storage::disk($disk)->delete($existingPath);
        }
    }
}
```

Change `deleteDocument()` file deletion from public-only:

```php
if ($doc->file_path && Storage::disk('public')->exists($doc->file_path)) {
    Storage::disk('public')->delete($doc->file_path);
}
```

To:

```php
if ($doc->file_path) {
    foreach (['local', 'public'] as $disk) {
        if (Storage::disk($disk)->exists($doc->file_path)) {
            Storage::disk($disk)->delete($doc->file_path);
        }
    }
}
```

- [ ] **Step 7: Replace direct public links**

In `resources/views/documents/index.blade.php`, replace:

```blade
:href="Storage::url($document->file_path)"
```

With:

```blade
:href="route('documents.download', $document)"
```

In `resources/views/admin/master-dokumen/index.blade.php`, replace:

```blade
<a href="{{ Storage::url($doc->file_path) }}" target="_blank" class="btn btn-icon btn-sm btn-light-info" title="Download">
```

With:

```blade
<a href="{{ route('documents.download', $doc) }}" target="_blank" class="btn btn-icon btn-sm btn-light-info" title="Download">
```

- [ ] **Step 8: Run GREEN check**

```bash
php artisan test tests/Feature/DocumentDownloadAuthorizationTest.php tests/Feature/DocumentServiceTest.php
```

Expected: PASS.

- [ ] **Step 9: Confirm no direct master document links remain**

```bash
rg "Storage::url\(\$doc|Storage::url\(\$document|/storage/" resources/views/admin/master-dokumen resources/views/documents
```

Expected: no output.

- [ ] **Step 10: Commit task**

```bash
git add tests/Feature/DocumentDownloadAuthorizationTest.php tests/Feature/DocumentServiceTest.php routes/web.php app/Http/Controllers/DocumentController.php app/Services/DocumentService.php resources/views/documents/index.blade.php resources/views/admin/master-dokumen/index.blade.php
git commit -m "fix(master): protect document downloads"
```

---

### Task 4: Sort normalization and whitelist guards

**Files:**
- Create: `tests/Feature/MasterDataSortTest.php`
- Modify: `app/Http/Controllers/Admin/MasterDokumenController.php`
- Modify: `app/Http/Controllers/Admin/MasterKategoriDokumenController.php`
- Modify: `app/Http/Controllers/Admin/RoleController.php`

- [ ] **Step 1: Write failing sort tests**

Create `tests/Feature/MasterDataSortTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataSortTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_dokumen_accepts_sort_and_direction_params(): void
    {
        $this->seedBasePermissions();
        $admin = User::factory()->create(['role_id' => 1, 'email_verified_at' => now()]);
        $category = $this->category('Docs', 'docs');
        Document::create(['title' => 'B Doc', 'category_id' => $category->id, 'status' => 1, 'file_path' => 'documents/b.pdf']);
        Document::create(['title' => 'A Doc', 'category_id' => $category->id, 'status' => 1, 'file_path' => 'documents/a.pdf']);

        $response = $this->actingAs($admin)->get(route('admin.master-dokumen.index', [
            'sort' => 'title',
            'direction' => 'asc',
        ]));

        $response->assertOk();
        $response->assertSeeInOrder(['A Doc', 'B Doc']);
    }

    public function test_master_kategori_accepts_sort_and_direction_params(): void
    {
        $this->seedBasePermissions();
        $admin = User::factory()->create(['role_id' => 1, 'email_verified_at' => now()]);
        $this->category('B Category', 'b_category', 20);
        $this->category('A Category', 'a_category', 10);

        $response = $this->actingAs($admin)->get(route('admin.master-kategori-dokumen.index', [
            'sort' => 'name',
            'direction' => 'asc',
        ]));

        $response->assertOk();
        $response->assertSeeInOrder(['A Category', 'B Category']);
    }

    public function test_roles_accept_sort_and_direction_params(): void
    {
        $this->seedBasePermissions();
        $superAdmin = User::factory()->create(['role_id' => 4, 'email_verified_at' => now()]);
        Role::create(['name' => 'Z Custom', 'parameter' => 'z_custom']);
        Role::create(['name' => 'A Custom', 'parameter' => 'a_custom']);

        $response = $this->actingAs($superAdmin)->get(route('admin.roles.index', [
            'sort' => 'name',
            'direction' => 'asc',
        ]));

        $response->assertOk();
        $response->assertSeeInOrder(['A Custom', 'Admin', 'Asesor', 'Pesantren', 'Super Admin', 'Z Custom']);
    }

    public function test_invalid_sort_falls_back_without_sql_error(): void
    {
        $this->seedBasePermissions();
        $admin = User::factory()->create(['role_id' => 1, 'email_verified_at' => now()]);

        $this->actingAs($admin)->get(route('admin.master-kategori-dokumen.index', [
            'sort' => 'bad_column',
            'direction' => 'asc',
        ]))->assertOk();
    }

    private function seedBasePermissions(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
    }

    private function category(string $name, string $slug, int $sortOrder = 1): DocumentCategory
    {
        return DocumentCategory::create([
            'name' => $name,
            'slug' => $slug,
            'icon' => 'document',
            'visibility' => DocumentCategory::VISIBILITY_PUBLIC,
            'is_active' => true,
            'sort_order' => $sortOrder,
        ]);
    }
}
```

- [ ] **Step 2: Run RED check**

```bash
php artisan test tests/Feature/MasterDataSortTest.php
```

Expected: sort order assertions FAIL because controllers ignore `sort`/`direction`.

- [ ] **Step 3: Normalize sort in MasterDokumenController**

In `app/Http/Controllers/Admin/MasterDokumenController.php`, replace lines that set `$sortField` and `$sortAsc` with:

```php
$sortField = $request->input('sort', $request->input('sortField', 'created_at'));
$sortField = in_array($sortField, ['title', 'created_at'], true) ? $sortField : 'created_at';
$direction = $request->input('direction');
$sortAsc = $direction ? $direction === 'asc' : $request->input('sortAsc', 'false') === 'true';
```

- [ ] **Step 4: Normalize sort in MasterKategoriDokumenController**

In `app/Http/Controllers/Admin/MasterKategoriDokumenController.php`, replace lines that set `$sortField` and `$sortAsc` with:

```php
$sortField = $request->input('sort', $request->input('sortField', 'sort_order'));
$sortField = in_array($sortField, ['name', 'sort_order'], true) ? $sortField : 'sort_order';
$direction = $request->input('direction');
$sortAsc = $direction ? $direction === 'asc' : $request->input('sortAsc', 'true') === 'true';
```

- [ ] **Step 5: Normalize sort in RoleController**

In `app/Http/Controllers/Admin/RoleController.php`, replace lines that set `$sortField` and `$sortAsc` with:

```php
$sortField = $request->input('sort', $request->input('sortField', 'id'));
$sortField = in_array($sortField, ['id', 'name', 'parameter'], true) ? $sortField : 'id';
$direction = $request->input('direction');
$sortAsc = $direction ? $direction === 'asc' : $request->input('sortAsc', 'false') === 'true';
```

- [ ] **Step 6: Run GREEN check**

```bash
php artisan test tests/Feature/MasterDataSortTest.php
```

Expected: PASS.

- [ ] **Step 7: Commit task**

```bash
git add tests/Feature/MasterDataSortTest.php app/Http/Controllers/Admin/MasterDokumenController.php app/Http/Controllers/Admin/MasterKategoriDokumenController.php app/Http/Controllers/Admin/RoleController.php
git commit -m "fix(master): normalize sort params"
```

---

### Task 5: Hide canonical role mutation actions

**Files:**
- Create: `tests/Feature/RoleManagementUiTest.php`
- Modify: `resources/views/admin/roles/index.blade.php`

- [ ] **Step 1: Write failing UI test**

Create `tests/Feature/RoleManagementUiTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleManagementUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_canonical_roles_do_not_render_edit_or_delete_actions(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
        $superAdmin = User::factory()->create(['role_id' => 4, 'email_verified_at' => now()]);
        $custom = Role::create(['name' => 'Custom Reviewer', 'parameter' => 'custom_reviewer']);

        $html = $this->actingAs($superAdmin)
            ->get(route('admin.roles.index'))
            ->assertOk()
            ->getContent();

        foreach ([1, 2, 3, 4] as $id) {
            $this->assertStringNotContainsString(route('admin.roles.destroy', $id), $html);
            $this->assertStringNotContainsString("openEditModal({$id},", $html);
        }

        $this->assertStringContainsString(route('admin.roles.destroy', $custom->id), $html);
        $this->assertStringContainsString("openEditModal({$custom->id},", $html);
    }
}
```

- [ ] **Step 2: Run RED check**

```bash
php artisan test tests/Feature/RoleManagementUiTest.php
```

Expected: FAIL because canonical role edit/delete actions are rendered.

- [ ] **Step 3: Hide canonical actions**

In `resources/views/admin/roles/index.blade.php`, wrap the action buttons with:

```blade
@if(! in_array($role->id, [1, 2, 3, 4], true))
    <x-ui.icon-button
        icon="pencil"
        label="Edit"
        variant="primary"
        x-on:click="openEditModal({{ $role->id }}, '{{ addslashes($role->name) }}', '{{ addslashes($role->parameter) }}')"
    />
    <form method="POST" action="{{ route('admin.roles.destroy', $role->id) }}" class="d-inline"
          x-on:submit.prevent="confirmDelete($event)">
        @csrf
        @method('DELETE')
        <x-ui.icon-button
            type="submit"
            icon="trash"
            label="Hapus"
            variant="danger"
        />
    </form>
@endif
```

Leave the backend `authorizeRoleMutation()` guard unchanged.

- [ ] **Step 4: Run GREEN check**

```bash
php artisan test tests/Feature/RoleManagementUiTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit task**

```bash
git add tests/Feature/RoleManagementUiTest.php resources/views/admin/roles/index.blade.php
git commit -m "fix(master): hide canonical role actions"
```

---

### Task 6: Final regression gate

**Files:**
- All files touched by Tasks 1-5.

- [ ] **Step 1: Run focused master data gate**

```bash
php artisan test tests/Feature/RolePermissionMatrixTest.php tests/Feature/MasterEdpmSecurityTest.php tests/Feature/DocumentDownloadAuthorizationTest.php tests/Feature/DocumentServiceTest.php tests/Feature/MasterDataSortTest.php tests/Feature/RoleManagementUiTest.php
```

Expected: PASS.

- [ ] **Step 2: Run full suite**

```bash
php artisan test
```

Expected: PASS.

- [ ] **Step 3: Run frontend build**

```bash
npm run build
```

Expected: PASS. Existing Browserslist age warning is non-blocking.

- [ ] **Step 4: Run whitespace check**

```bash
git diff --check
```

Expected: no output, exit 0.

- [ ] **Step 5: Check final status**

```bash
git status --short
```

Expected: only intended committed changes, or clean if all task commits are done.

---

## Self-review

- Spec coverage: all five approved fix areas map to Tasks 1-5.
- Placeholder scan: no TBD/TODO placeholders; every code change step includes concrete code.
- Type consistency: route name `documents.download`, model names, controller names, and test file paths match existing project conventions.
- Risk handling: existing public files are not migrated; authenticated route has public fallback and code stops generating public links.
