# Pesantren Form Metronic Polish Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Polish `/pesantren/profile`, `/pesantren/ipm`, `/pesantren/sdm`, and `/pesantren/edpm` so their form surfaces match the existing Metronic visual system.

**Architecture:** Keep current Blade data flow and `x-ui` component structure. Add one focused CSS override file for Pesantren form surfaces, then add semantic hook classes to the four Blade pages. No controller, route, DB, or JS behavior changes.

**Tech Stack:** Laravel Blade, Metronic CSS utilities, existing `x-ui` Blade components, Vite CSS import pipeline, PHPUnit feature/view tests.

---

## File Structure

- Create: `tests/Feature/PesantrenFormMetronicPolishTest.php`
  - View contract tests proving the four pages render and expose the new polish hook classes.
- Create: `resources/css/metronic-overrides/52-pesantren-forms.css`
  - Additive CSS only. Scoped to `data-module-page^="pesantren-"` and `spm-pesantren-*` classes.
- Modify: `resources/css/metronic-overrides.css`
  - Import the new CSS after dashboard styles and before landing/auth styles.
- Modify: `resources/views/pesantren/profile.blade.php`
  - Add page-level class, section/card density hooks, upload card wrappers, sticky action bar class.
- Modify: `resources/views/pesantren/ipm.blade.php`
  - Add page-level class, criteria card/status hooks, file upload styling hooks.
- Modify: `resources/views/pesantren/sdm.blade.php`
  - Add page-level class, table/card hooks, numeric input class, save action class.
- Modify: `resources/views/pesantren/edpm.blade.php`
  - Add page-level class, sticky stepper card hooks, step button hooks, table/input hooks, action bar class.

---

### Task 1: Add failing form polish view contract tests

**Files:**
- Create: `tests/Feature/PesantrenFormMetronicPolishTest.php`

- [ ] **Step 1: Create the failing test file**

Create `tests/Feature/PesantrenFormMetronicPolishTest.php` with:

```php
<?php

namespace Tests\Feature;

use App\Models\Pesantren;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DocumentCategorySeeder;
use Database\Seeders\MasterEdpmSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PesantrenFormMetronicPolishTest extends TestCase
{
    use RefreshDatabase;

    private User $pesantrenUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        $this->seed(DocumentCategorySeeder::class);

        $this->pesantrenUser = User::factory()->create([
            'role_id' => Role::ID_PESANTREN,
            'email_verified_at' => now(),
        ]);

        Pesantren::create([
            'user_id' => $this->pesantrenUser->id,
            'nama_pesantren' => 'Pesantren Polish',
            'ns_pesantren' => 'NSP-001',
            'alamat' => 'Jl. Metronic No. 1',
            'tahun_pendirian' => 2000,
            'layanan_satuan_pendidikan' => ['sd', 'smp'],
            'is_locked' => false,
        ]);
    }

    public function test_profile_page_exposes_metrionic_form_polish_hooks(): void
    {
        $this->actingAs($this->pesantrenUser)
            ->get('/pesantren/profile')
            ->assertOk()
            ->assertSee('data-module-page="pesantren-profile"', false)
            ->assertSee('spm-pesantren-form-page', false)
            ->assertSee('spm-profile-upload-card', false)
            ->assertSee('spm-pesantren-form-actions', false);
    }

    public function test_ipm_page_exposes_metrionic_upload_polish_hooks(): void
    {
        $this->actingAs($this->pesantrenUser)
            ->get('/pesantren/ipm')
            ->assertOk()
            ->assertSee('data-module-page="pesantren-ipm"', false)
            ->assertSee('spm-pesantren-form-page', false)
            ->assertSee('spm-ipm-criteria-card', false)
            ->assertSee('spm-pesantren-file-control', false);
    }

    public function test_sdm_page_exposes_metrionic_table_polish_hooks(): void
    {
        $this->actingAs($this->pesantrenUser)
            ->get('/pesantren/sdm')
            ->assertOk()
            ->assertSee('data-module-page="pesantren-sdm"', false)
            ->assertSee('spm-pesantren-form-page', false)
            ->assertSee('spm-sdm-table-card', false)
            ->assertSee('spm-sdm-number-input', false);
    }

    public function test_edpm_page_exposes_metrionic_stepper_polish_hooks(): void
    {
        $this->seed(MasterEdpmSeeder::class);

        $this->actingAs($this->pesantrenUser)
            ->get('/pesantren/edpm')
            ->assertOk()
            ->assertSee('data-module-page="pesantren-edpm"', false)
            ->assertSee('spm-pesantren-form-page', false)
            ->assertSee('spm-edpm-stepper-card', false)
            ->assertSee('spm-edpm-input-table', false);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run:

```bash
php artisan test tests/Feature/PesantrenFormMetronicPolishTest.php
```

Expected: FAIL. Each test should fail on missing hook classes such as `spm-pesantren-form-page`, `spm-profile-upload-card`, `spm-ipm-criteria-card`, `spm-sdm-table-card`, or `spm-edpm-stepper-card`.

- [ ] **Step 3: Commit failing test**

Do not commit yet if the team avoids red commits. If committing red tests is not acceptable, keep this change staged/unstaged and continue Task 2.

---

### Task 2: Add scoped Pesantren form CSS

**Files:**
- Create: `resources/css/metronic-overrides/52-pesantren-forms.css`
- Modify: `resources/css/metronic-overrides.css`

- [ ] **Step 1: Create the CSS file**

Create `resources/css/metronic-overrides/52-pesantren-forms.css`:

```css
/*
 * Pesantren form pages — additive Metronic polish.
 * Scoped to Pesantren data-entry pages only.
 */

.spm-pesantren-form-page .spm-section-card {
    border: 1px solid var(--bs-gray-200);
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
}

.spm-pesantren-form-page .spm-section-card .card-header {
    min-height: 68px;
    background: linear-gradient(180deg, rgba(var(--spm-primary-rgb), 0.035), rgba(255, 255, 255, 0));
}

.spm-pesantren-form-page .spm-form-field {
    margin-bottom: 0;
}

.spm-pesantren-form-page .form-control-solid,
.spm-pesantren-form-page .form-select-solid,
.spm-pesantren-form-page .form-control[data-ui-file-upload="metronic"] {
    border: 1px solid var(--bs-gray-300);
    background-color: var(--bs-gray-100);
}

.spm-pesantren-form-page .form-control-solid:hover,
.spm-pesantren-form-page .form-select-solid:hover,
.spm-pesantren-form-page .form-control[data-ui-file-upload="metronic"]:hover {
    border-color: rgba(var(--spm-primary-rgb), 0.35);
}

.spm-pesantren-form-page textarea.form-control {
    min-height: 108px;
    resize: vertical;
}

.spm-pesantren-form-page .form-text,
.spm-pesantren-form-page .text-muted.fs-8 {
    color: var(--spm-text-muted) !important;
    font-weight: 500;
}

.spm-profile-upload-card,
.spm-ipm-criteria-card {
    position: relative;
    height: 100%;
    padding: 1rem;
    border: 1px dashed var(--bs-gray-300);
    border-radius: 0.75rem;
    background: var(--bs-gray-100);
    transition: border-color 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
}

.spm-profile-upload-card:hover,
.spm-ipm-criteria-card:hover {
    border-color: rgba(var(--spm-primary-rgb), 0.45);
    background: #fff;
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
}

.spm-profile-upload-card.is-complete,
.spm-ipm-criteria-card.is-complete {
    border-style: solid;
    border-color: rgba(80, 205, 137, 0.45);
    background: rgba(80, 205, 137, 0.06);
}

.spm-pesantren-file-control {
    cursor: pointer;
}

.spm-pesantren-form-actions {
    position: sticky;
    bottom: 0;
    z-index: 5;
    margin-top: 1.5rem;
    padding: 1rem;
    border: 1px solid var(--bs-gray-200);
    border-radius: 0.75rem;
    background: rgba(255, 255, 255, 0.94);
    box-shadow: 0 -8px 24px rgba(15, 23, 42, 0.06);
    backdrop-filter: blur(8px);
}

.spm-sdm-table-card .spm-table-shell {
    border: 1px solid var(--bs-gray-200);
    border-radius: 0.75rem;
    overflow: auto;
}

.spm-sdm-table-card .table th {
    white-space: nowrap;
    color: var(--spm-text-heading);
    font-size: 0.78rem;
    font-weight: 700;
}

.spm-sdm-table-card .table td {
    vertical-align: middle;
}

.spm-sdm-number-input {
    width: 92px;
    min-width: 92px;
    margin: 0 auto;
    font-weight: 700;
}

.spm-edpm-stepper-card {
    position: sticky;
    top: 96px;
    padding: 1rem;
    border: 1px solid var(--bs-gray-200);
    border-radius: 0.75rem;
    background: #fff;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
}

.spm-edpm-step-button {
    min-height: 54px;
    border: 1px solid transparent;
}

.spm-edpm-step-button.btn-primary {
    box-shadow: 0 8px 18px rgba(var(--spm-primary-rgb), 0.16);
}

.spm-edpm-step-button.btn-light:hover {
    border-color: rgba(var(--spm-primary-rgb), 0.25);
    color: var(--spm-primary);
}

.spm-edpm-input-table .table-responsive {
    border: 1px solid var(--bs-gray-200);
    border-radius: 0.75rem;
}

.spm-edpm-input-table table th {
    white-space: nowrap;
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--spm-text-heading);
}

.spm-edpm-input-table table td:first-child {
    font-weight: 700;
    color: var(--spm-primary);
}

.spm-edpm-input-table .form-select,
.spm-edpm-input-table .form-control {
    min-width: 112px;
}

.spm-edpm-input-table input[type="url"] {
    min-width: 190px;
}

@media (max-width: 991.98px) {
    .spm-edpm-stepper-card {
        position: static;
    }

    .spm-pesantren-form-actions {
        position: static;
    }
}
```

- [ ] **Step 2: Import the CSS file**

Modify `resources/css/metronic-overrides.css` so lines around dashboard import become:

```css
@import "./metronic-overrides/45-form-modal.css";
@import "./metronic-overrides/50-dashboard.css";
@import "./metronic-overrides/52-pesantren-forms.css";
@import "./metronic-overrides/55-landing.css";
```

- [ ] **Step 3: Run CSS build**

Run:

```bash
npm run build
```

Expected: PASS. Vite should compile with no missing import error.

---

### Task 3: Add Profile page polish hooks

**Files:**
- Modify: `resources/views/pesantren/profile.blade.php`

- [ ] **Step 1: Add page-level class**

Change the `<x-ui.page>` opening block from:

```blade
<x-ui.page
    title="Profil Pesantren"
    subtitle="Kelola data profil, unit layanan, dan dokumen pendukung pesantren."
    data-module-page="pesantren-profile"
>
```

to:

```blade
<x-ui.page
    title="Profil Pesantren"
    subtitle="Kelola data profil, unit layanan, dan dokumen pendukung pesantren."
    data-module-page="pesantren-profile"
    class="spm-pesantren-form-page"
>
```

- [ ] **Step 2: Add upload card wrappers to primary documents**

In the primary document loop, replace this block:

```blade
<div class="col-lg-6">
    <x-ui.form-field label="{{ $doc['label'] }}" for="{{ $inputName }}">
        @if($hasFile)
            <div class="d-flex align-items-center gap-2 mb-2">
                <x-ui.icon name="check-circle" class="fs-5 text-success" />
                <a href="{{ Storage::url($existingPath) }}" target="_blank"
                    class="fw-semibold text-success fs-7 text-hover-primary">Lihat Dokumen</a>
            </div>
        @endif
        @if(!$isLocked)
            <input type="file" name="{{ $inputName }}" id="{{ $inputName }}"
                class="form-control form-control-solid @error($inputName) is-invalid @enderror"
                accept=".pdf,.jpg,.jpeg,.png">
            <div class="text-muted fs-8 mt-1">PDF, JPG, PNG. Maks 2MB.</div>
            @error($inputName) <div class="invalid-feedback">{{ $message }}</div> @enderror
        @endif
    </x-ui.form-field>
</div>
```

with:

```blade
<div class="col-lg-6">
    <div class="spm-profile-upload-card {{ $hasFile ? 'is-complete' : '' }}">
        <x-ui.form-field label="{{ $doc['label'] }}" for="{{ $inputName }}">
            @if($hasFile)
                <div class="d-flex align-items-center gap-2 mb-3">
                    <x-ui.icon name="check-circle" class="fs-5 text-success" />
                    <a href="{{ Storage::url($existingPath) }}" target="_blank"
                        class="fw-semibold text-success fs-7 text-hover-primary">Lihat Dokumen</a>
                </div>
            @endif
            @if(!$isLocked)
                <input type="file" name="{{ $inputName }}" id="{{ $inputName }}"
                    class="form-control form-control-solid spm-pesantren-file-control @error($inputName) is-invalid @enderror"
                    accept=".pdf,.jpg,.jpeg,.png">
                <div class="text-muted fs-8 mt-2">PDF, JPG, PNG. Maks 2MB.</div>
                @error($inputName) <div class="invalid-feedback">{{ $message }}</div> @enderror
            @endif
        </x-ui.form-field>
    </div>
</div>
```

- [ ] **Step 3: Add upload card wrappers to secondary documents**

Apply the same replacement inside the secondary document loop. The replacement code is identical to Step 2.

- [ ] **Step 4: Add sticky action class**

Replace:

```blade
<div class="d-flex align-items-center justify-content-end gap-3">
```

near the submit buttons with:

```blade
<div class="spm-pesantren-form-actions d-flex align-items-center justify-content-end gap-3">
```

- [ ] **Step 5: Run profile test**

Run:

```bash
php artisan test tests/Feature/PesantrenFormMetronicPolishTest.php --filter=profile
```

Expected: PASS for `test_profile_page_exposes_metrionic_form_polish_hooks`.

---

### Task 4: Add IPM page polish hooks

**Files:**
- Modify: `resources/views/pesantren/ipm.blade.php`

- [ ] **Step 1: Add page-level class**

Change the `<x-ui.page>` block to include:

```blade
class="spm-pesantren-form-page"
```

Full block:

```blade
<x-ui.page
    title="Indikator Pemenuhan Mutlak (IPM)"
    subtitle="Unggah dokumen pendukung untuk empat kriteria pemenuhan mutlak."
    data-module-page="pesantren-ipm"
    class="spm-pesantren-form-page"
>
```

- [ ] **Step 2: Add card classes to each criterion**

Replace:

```blade
<x-ui.section-card :title="$item['label']" :subtitle="$item['description']" class="spm-card-lift">
```

with:

```blade
<x-ui.section-card :title="$item['label']" :subtitle="$item['description']" class="spm-card-lift spm-ipm-criteria-card {{ $hasFile ? 'is-complete' : '' }}">
```

- [ ] **Step 3: Make file input solid and scoped**

Replace:

```blade
class="form-control form-control-sm @error($item['input']) is-invalid @enderror"
```

with:

```blade
class="form-control form-control-sm form-control-solid spm-pesantren-file-control @error($item['input']) is-invalid @enderror"
```

- [ ] **Step 4: Add action bar class**

Replace:

```blade
<div class="d-flex justify-content-end gap-3 mt-6">
```

with:

```blade
<div class="spm-pesantren-form-actions d-flex justify-content-end gap-3 mt-6">
```

- [ ] **Step 5: Run IPM test**

Run:

```bash
php artisan test tests/Feature/PesantrenFormMetronicPolishTest.php --filter=ipm
```

Expected: PASS for `test_ipm_page_exposes_metrionic_upload_polish_hooks`.

---

### Task 5: Add SDM page polish hooks

**Files:**
- Modify: `resources/views/pesantren/sdm.blade.php`

- [ ] **Step 1: Add page-level class**

Change the `<x-ui.page>` block to:

```blade
<x-ui.page
    title="Data SDM Pesantren"
    subtitle="Kelola rekap santri, ustadz, pamong, musyrif, dan tenaga kependidikan."
    data-module-page="pesantren-sdm"
    class="spm-pesantren-form-page"
>
```

- [ ] **Step 2: Add table card class**

Replace:

```blade
<x-ui.section-card :title="$category['label']" subtitle="Input rekap per unit layanan" class="mb-6">
```

with:

```blade
<x-ui.section-card :title="$category['label']" subtitle="Input rekap per unit layanan" class="mb-6 spm-sdm-table-card">
```

- [ ] **Step 3: Add number input class to male row**

Replace the male row input class:

```blade
class="form-control form-control-sm text-center @error('data.' . $level . '.' . $catKey . '_l') is-invalid @enderror"
```

with:

```blade
class="form-control form-control-sm form-control-solid text-center spm-sdm-number-input @error('data.' . $level . '.' . $catKey . '_l') is-invalid @enderror"
```

Remove the inline style from the same input:

```blade
style="width: 90px; margin: 0 auto;"
```

- [ ] **Step 4: Add number input class to female row**

Replace the female row input class:

```blade
class="form-control form-control-sm text-center @error('data.' . $level . '.' . $catKey . '_p') is-invalid @enderror"
```

with:

```blade
class="form-control form-control-sm form-control-solid text-center spm-sdm-number-input @error('data.' . $level . '.' . $catKey . '_p') is-invalid @enderror"
```

Remove the inline style from the same input:

```blade
style="width: 90px; margin: 0 auto;"
```

- [ ] **Step 5: Add action bar class**

Replace:

```blade
<div class="d-flex justify-content-end mt-6">
```

with:

```blade
<div class="spm-pesantren-form-actions d-flex justify-content-end mt-6">
```

- [ ] **Step 6: Run SDM test**

Run:

```bash
php artisan test tests/Feature/PesantrenFormMetronicPolishTest.php --filter=sdm
```

Expected: PASS for `test_sdm_page_exposes_metrionic_table_polish_hooks`.

---

### Task 6: Add EDPM page polish hooks

**Files:**
- Modify: `resources/views/pesantren/edpm.blade.php`

- [ ] **Step 1: Add page-level class**

Change the `<x-ui.page>` block to:

```blade
<x-ui.page
    title="Evaluasi Diri Pesantren/Madrasah (EDPM)"
    subtitle="Isi nilai evaluasi, tautan bukti, dan catatan untuk setiap komponen."
    data-module-page="pesantren-edpm"
    class="spm-pesantren-form-page"
>
```

- [ ] **Step 2: Add sticky stepper card wrapper**

Replace:

```blade
<div class="col-lg-3">
    <div class="d-flex flex-column gap-2">
```

with:

```blade
<div class="col-lg-3">
    <div class="spm-edpm-stepper-card d-flex flex-column gap-2">
```

- [ ] **Step 3: Add step button class to EDPM buttons**

Replace the EDPM step button class:

```blade
<button type="button" class="btn w-100 text-start"
```

with:

```blade
<button type="button" class="btn w-100 text-start spm-edpm-step-button"
```

- [ ] **Step 4: Add step button class to IPR buttons**

Repeat Step 3 for the IPR step button. There are two matching `<button type="button" class="btn w-100 text-start"` entries; both must include `spm-edpm-step-button`.

- [ ] **Step 5: Add table wrapper class to EDPM section card**

Replace:

```blade
<x-ui.section-card :title="'Komponen ' . $edpmKomponens->first()?->nama">
```

with:

```blade
<x-ui.section-card :title="'Komponen ' . $edpmKomponens->first()?->nama" class="spm-edpm-input-table">
```

- [ ] **Step 6: Add table wrapper class to IPR section card**

Replace:

```blade
<x-ui.section-card title="Komponen IPR">
```

with:

```blade
<x-ui.section-card title="Komponen IPR" class="spm-edpm-input-table">
```

- [ ] **Step 7: Make EDPM table controls solid**

Replace both select classes:

```blade
class="form-select form-select-sm"
```

with:

```blade
class="form-select form-select-sm form-select-solid"
```

Replace both URL input classes:

```blade
class="form-control form-control-sm"
```

with:

```blade
class="form-control form-control-sm form-control-solid"
```

Replace both textarea classes:

```blade
class="form-control"
```

with:

```blade
class="form-control form-control-solid"
```

- [ ] **Step 8: Add action bar class**

Replace:

```blade
<div class="d-flex justify-content-between mt-6">
```

with:

```blade
<div class="spm-pesantren-form-actions d-flex justify-content-between mt-6">
```

- [ ] **Step 9: Run EDPM test**

Run:

```bash
php artisan test tests/Feature/PesantrenFormMetronicPolishTest.php --filter=edpm
```

Expected: PASS for `test_edpm_page_exposes_metrionic_stepper_polish_hooks`.

---

### Task 7: Verify all tests and build

**Files:**
- No new files.

- [ ] **Step 1: Run the new feature test file**

Run:

```bash
php artisan test tests/Feature/PesantrenFormMetronicPolishTest.php
```

Expected: 4 tests pass.

- [ ] **Step 2: Run nearby Pesantren tests**

Run:

```bash
php artisan test --filter=Pesantren
```

Expected: all matching tests pass. If unrelated existing failures appear, capture the exact failing test names and stop for triage.

- [ ] **Step 3: Run frontend build**

Run:

```bash
npm run build
```

Expected: Vite build succeeds. New `52-pesantren-forms.css` import is included through `resources/css/metronic-overrides.css`.

- [ ] **Step 4: Commit implementation**

Run:

```bash
git add resources/css/metronic-overrides.css resources/css/metronic-overrides/52-pesantren-forms.css resources/views/pesantren/profile.blade.php resources/views/pesantren/ipm.blade.php resources/views/pesantren/sdm.blade.php resources/views/pesantren/edpm.blade.php tests/Feature/PesantrenFormMetronicPolishTest.php
git commit -m "style(pesantren): polish Metronic form pages

Co-Authored-By: Claude <noreply@anthropic.com>"
```

---

### Task 8: Runtime visual verification

**Files:**
- No source changes unless verification finds a defect.

- [ ] **Step 1: Ensure a local Pesantren user exists**

Run if no usable Pesantren login exists:

```bash
php artisan tinker --execute="App\Models\User::updateOrCreate(['email'=>'verify.pesantren@test.local'], ['name'=>'Verify Pesantren', 'password'=>Illuminate\Support\Facades\Hash::make('password'), 'role_id'=>3, 'status'=>1, 'email_verified_at'=>now()]); App\Models\Pesantren::updateOrCreate(['user_id'=>App\Models\User::where('email','verify.pesantren@test.local')->value('id')], ['nama_pesantren'=>'Verify Pesantren', 'ns_pesantren'=>'VERIFY-001', 'alamat'=>'Jl. Verifikasi 1', 'tahun_pendirian'=>2000, 'layanan_satuan_pendidikan'=>['sd','smp'], 'is_locked'=>false]);"
```

- [ ] **Step 2: Drive the four URLs in browser or HTTP surface**

Open or fetch as the Pesantren user:

```text
http://spm_fix.test/pesantren/profile
http://spm_fix.test/pesantren/ipm
http://spm_fix.test/pesantren/sdm
http://spm_fix.test/pesantren/edpm
```

Expected observations:

- Profile: section cards render, upload cards use dashed/complete styling, save bar remains visible near bottom.
- IPM: criteria cards render with clearer uploaded/missing status and file inputs keep Metronic density.
- SDM: numeric table inputs are compact and readable; totals remain emphasized.
- EDPM: desktop stepper sits in a card and remains sticky while the content table scrolls; controls remain usable.

- [ ] **Step 3: Probe mobile-ish width if browser tooling is available**

Set viewport around `390x844` and check:

- EDPM stepper becomes non-sticky and stacks above content.
- Action bar becomes non-sticky.
- Tables remain horizontally scrollable instead of clipping page content.

- [ ] **Step 4: Push branch**

Run:

```bash
git push
```

Expected: current branch updates on origin.

---

## Self-Review

Spec coverage:

- Profile spacing/input/upload/action polish covered in Tasks 2-3.
- IPM upload checklist polish covered in Tasks 2 and 4.
- SDM compact table/input polish covered in Tasks 2 and 5.
- EDPM sticky stepper/table/input polish covered in Tasks 2 and 6.
- Tests/build/runtime verification covered in Tasks 1, 7, and 8.

Placeholder scan: no TBD/TODO placeholders. All file paths, commands, and code snippets are explicit.

Type/property consistency: hook class names used in tests match Blade/CSS tasks: `spm-pesantren-form-page`, `spm-profile-upload-card`, `spm-pesantren-form-actions`, `spm-ipm-criteria-card`, `spm-pesantren-file-control`, `spm-sdm-table-card`, `spm-sdm-number-input`, `spm-edpm-stepper-card`, `spm-edpm-input-table`.
