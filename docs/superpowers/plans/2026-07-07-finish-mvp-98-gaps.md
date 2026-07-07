# Finish MVP 98 Gaps Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close the remaining MVP blockers: frontend-backend action contracts, admin NV UI contract, HTTP regression coverage, UI audit evidence, docs/scorecard finalization.

**Architecture:** Keep the current Blade/controller workflow. Do not add new services or JS frameworks. Fix broken contracts at the smallest boundary: Blade forms must submit the fields controllers already require, controllers must validate the names the Blade emits, tests must hit the real HTTP routes.

**Tech Stack:** Laravel 12, PHP 8.2, PHPUnit 11, Blade, Alpine, Metronic 8, Vite.

---

## Current Unfinished Work

Source docs show these still open:

- `docs/mvp-98-audit-scorecard.md`: UI audit final score, P0 finalize scoring HTTP path, P1 route contract sync, P2 docs cleanup.
- `docs/next-execution-plan-ui-audit-2026-06-29.md`: dashboard audit per role, UI polish, browser smoke, scorecard update.
- Runtime inspection found two concrete route/UI contract gaps:
  - `resources/views/asesor/akreditasi-detail.blade.php` dynamic POSTs submit only CSRF; controller requires `akreditasi_id`.
  - `resources/views/admin/akreditasi/detail/tabs/instrumen/*` shows NV values but does not submit `adminNvs[...]` / `nvReasons[...]` inputs.

## File Structure

Modify only existing runtime files plus focused tests/docs.

- Create: `tests/Feature/BusinessFlow/BusinessFlowHttpContractTest.php`
  - Real HTTP regression for asesor dynamic actions: confirm visitasi + finalize scoring.
- Create: `tests/Feature/AdminNvUiContractTest.php`
  - Blade/UI contract regression for admin NV inputs + reason fields.
- Modify: `resources/views/asesor/akreditasi-detail.blade.php`
  - Add hidden `akreditasi_id` to dynamic POST form helper.
- Modify: `resources/views/admin/akreditasi/detail/tabs/instrumen.blade.php`
  - Wrap score table + NV actions in one form only when admin can edit NV.
- Modify: `resources/views/admin/akreditasi/detail/tabs/instrumen/score-table.blade.php`
  - Render NV select inputs and per-butir reason textareas in Validasi Admin.
- Modify: `resources/views/admin/akreditasi/detail/tabs/instrumen/nv-actions.blade.php`
  - Replace nested form with submit buttons using `formaction`.
- Modify: `app/Http/Controllers/Admin/AkreditasiDetailController.php`
  - Align draft save validation with `nvReasons` array emitted by Blade.
- Modify: `docs/mvp-98-audit-scorecard.md`
  - Update statuses after tests/browser audit pass.
- Modify: `docs/next-execution-plan-ui-audit-2026-06-29.md`
  - Mark completed audit items and link evidence.
- Create: `docs/ui-role-audit-2026-07-07.md`
  - Evidence table for Super Admin/Admin/Asesor/Pesantren smoke audit.

---

### Task 1: Fix Asesor dynamic POST contract

**Files:**
- Create: `tests/Feature/BusinessFlow/BusinessFlowHttpContractTest.php`
- Modify: `resources/views/asesor/akreditasi-detail.blade.php:469-473`

- [ ] **Step 1: Write failing HTTP/Blade contract tests**

Create `tests/Feature/BusinessFlow/BusinessFlowHttpContractTest.php`:

```php
<?php

namespace Tests\Feature\BusinessFlow;

use App\Models\Akreditasi;
use App\Models\Assessment;
use App\Services\AkreditasiWorkflowService;
use App\StateMachine\AkreditasiStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessFlowHttpContractTest extends TestCase
{
    use RefreshDatabase;
    use BusinessFlowTestHelpers;

    private AkreditasiWorkflowService $workflow;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBusinessFlowBase();
        $this->workflow = app(AkreditasiWorkflowService::class);
    }

    public function test_asesor_detail_dynamic_post_helper_includes_akreditasi_id(): void
    {
        $pesantren = $this->createCompletePesantrenUser('bf.http.detail.pesantren@test.local');
        $asesor1 = $this->createAsesorUser('bf.http.detail.asesor1@test.local', 'BF HTTP Detail Asesor 1');
        $asesor2 = $this->createAsesorUser('bf.http.detail.asesor2@test.local', 'BF HTTP Detail Asesor 2');
        $akreditasi = $this->createAkreditasi($pesantren, AkreditasiStateMachine::STATUS_VISITASI, 'BF-HTTP-DETAIL');
        $this->assignAsesors($akreditasi, $asesor1, $asesor2);

        $this->actingAs($asesor1)
            ->get(route('asesor.akreditasi-detail', $akreditasi->uuid))
            ->assertOk()
            ->assertSee('name="akreditasi_id"', false)
            ->assertSee('value="'.$akreditasi->id.'"', false);
    }

    public function test_asesor_can_confirm_visitasi_selesai_through_http_route(): void
    {
        $pesantren = $this->createCompletePesantrenUser('bf.http.confirm.pesantren@test.local');
        $asesor1 = $this->createAsesorUser('bf.http.confirm.asesor1@test.local', 'BF HTTP Confirm Asesor 1');
        $asesor2 = $this->createAsesorUser('bf.http.confirm.asesor2@test.local', 'BF HTTP Confirm Asesor 2');
        $akreditasi = $this->createAkreditasi($pesantren, AkreditasiStateMachine::STATUS_VISITASI, 'BF-HTTP-CONFIRM');
        $this->assignAsesors($akreditasi, $asesor1, $asesor2);
        $akreditasi->update([
            'tgl_visitasi' => now()->subDay()->toDateString(),
            'tgl_visitasi_akhir' => now()->toDateString(),
        ]);

        $this->actingAs($asesor1)
            ->post(route('asesor.akreditasi.confirm-visitasi-selesai'), [
                'akreditasi_id' => $akreditasi->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Visitasi dikonfirmasi selesai. Tahap penilaian pasca visitasi dimulai.');

        $this->assertSame(AkreditasiStateMachine::STATUS_PASCA_VISITASI, (int) $akreditasi->fresh()->status);
        $this->assertNotNull($akreditasi->fresh()->visitasi_confirmed_at);
    }

    public function test_asesor_can_finalize_scoring_through_http_route(): void
    {
        $pesantren = $this->createCompletePesantrenUser('bf.http.finalize.pesantren@test.local');
        $asesor1 = $this->createAsesorUser('bf.http.finalize.asesor1@test.local', 'BF HTTP Finalize Asesor 1');
        $asesor2 = $this->createAsesorUser('bf.http.finalize.asesor2@test.local', 'BF HTTP Finalize Asesor 2');
        $akreditasi = $this->createAkreditasi($pesantren, AkreditasiStateMachine::STATUS_PASCA_VISITASI, 'BF-HTTP-FINALIZE');
        $this->assignAsesors($akreditasi, $asesor1, $asesor2);
        $akreditasi->update([
            'laporan_visitasi_asesor1' => 'bf/laporan/asesor1.pdf',
            'laporan_visitasi_asesor2' => 'bf/laporan/asesor2.pdf',
            'laporan_visitasi_kelompok' => 'bf/laporan/kelompok.pdf',
            'kartu_kendali' => 'bf/kartu/kendali.pdf',
        ]);
        $this->seedCompleteScoring($akreditasi->fresh(), $asesor1, $asesor2);

        $this->actingAs($asesor1)
            ->post(route('asesor.akreditasi.finalize-scoring'), [
                'akreditasi_id' => $akreditasi->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Penilaian difinalisasi. Akreditasi masuk tahap Validasi Admin.');

        $final = $akreditasi->fresh();
        $this->assertSame(AkreditasiStateMachine::STATUS_VALIDASI_ADMIN, (int) $final->status);
        $this->assertTrue((bool) $final->is_nilai_asesor_final);
        $this->assertTrue((bool) $final->is_nilai_asesor2_final);
    }
}
```

- [ ] **Step 2: Run test to verify failure**

Run:

```bash
php artisan test tests/Feature/BusinessFlow/BusinessFlowHttpContractTest.php --stop-on-failure
```

Expected before fix:

```txt
FAIL Tests\Feature\BusinessFlow\BusinessFlowHttpContractTest
Failed asserting that response contains "name=\"akreditasi_id\"".
```

- [ ] **Step 3: Add `akreditasi_id` to dynamic form helper**

In `resources/views/asesor/akreditasi-detail.blade.php`, replace lines 469-473:

```js
submitForm(url) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    form.innerHTML = `<input type="hidden" name="_token" value="{{ csrf_token() }}">`;
```

with:

```js
submitForm(url) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    form.innerHTML = `
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <input type="hidden" name="akreditasi_id" value="{{ $akreditasi->id }}">
    `;
```

- [ ] **Step 4: Run test to verify pass**

Run:

```bash
php artisan test tests/Feature/BusinessFlow/BusinessFlowHttpContractTest.php
```

Expected:

```txt
PASS  Tests\Feature\BusinessFlow\BusinessFlowHttpContractTest
Tests: 3 passed
```

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/BusinessFlow/BusinessFlowHttpContractTest.php resources/views/asesor/akreditasi-detail.blade.php
git commit -m "Fix asesor workflow HTTP form contract"
```

---

### Task 2: Fix Admin NV UI contract

**Files:**
- Create: `tests/Feature/AdminNvUiContractTest.php`
- Modify: `resources/views/admin/akreditasi/detail/tabs/instrumen.blade.php`
- Modify: `resources/views/admin/akreditasi/detail/tabs/instrumen/score-table.blade.php`
- Modify: `resources/views/admin/akreditasi/detail/tabs/instrumen/nv-actions.blade.php`
- Modify: `app/Http/Controllers/Admin/AkreditasiDetailController.php:381-384`

- [ ] **Step 1: Write failing UI contract tests**

Create `tests/Feature/AdminNvUiContractTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\AkreditasiEdpm;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\MasterEdpmButir;
use App\Models\MasterEdpmKomponen;
use App\Models\Pesantren;
use App\Models\User;
use App\StateMachine\AkreditasiStateMachine;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNvUiContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_validasi_page_renders_nv_inputs_and_reason_fields(): void
    {
        $setup = $this->createValidasiSetup(nk: 3, nv: null);

        $this->actingAs($setup['admin'])
            ->get(route('admin.akreditasi-detail', ['uuid' => $setup['akreditasi']->uuid, 'tab' => 'instrumen']))
            ->assertOk()
            ->assertSee('name="adminNvs['.$setup['butir']->id.']"', false)
            ->assertSee('name="nvReasons['.$setup['butir']->id.']"', false)
            ->assertSee(route('admin.akreditasi-detail.save-nv', $setup['akreditasi']->uuid), false)
            ->assertSee(route('admin.akreditasi-detail.finalize-nv', $setup['akreditasi']->uuid), false);
    }

    public function test_admin_can_save_nv_draft_from_ui_field_names(): void
    {
        $setup = $this->createValidasiSetup(nk: 3, nv: null);

        $this->actingAs($setup['admin'])
            ->post(route('admin.akreditasi-detail.save-nv', $setup['akreditasi']->uuid), [
                'adminNvs' => [$setup['butir']->id => 4],
                'nvReasons' => [$setup['butir']->id => 'Draft koreksi admin'],
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Nilai Verifikasi berhasil disimpan.');

        $this->assertDatabaseHas('akreditasi_edpms', [
            'akreditasi_id' => $setup['akreditasi']->id,
            'butir_id' => $setup['butir']->id,
            'nk' => 3,
            'nv' => 4,
            'is_final' => false,
        ]);
    }

    private function createValidasiSetup(int $nk, ?int $nv): array
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create(['role_id' => 1, 'email_verified_at' => now()]);
        $pesantrenUser = User::factory()->create(['role_id' => 3, 'email_verified_at' => now()]);
        Pesantren::create(['user_id' => $pesantrenUser->id, 'nama_pesantren' => 'Pesantren NV UI']);
        $asesorUser = User::factory()->create(['role_id' => 2, 'email_verified_at' => now()]);
        $asesor = Asesor::create([
            'user_id' => $asesorUser->id,
            'nama_dengan_gelar' => 'Asesor NV',
            'nama_tanpa_gelar' => 'Asesor NV',
        ]);

        $komponen = MasterEdpmKomponen::create(['nama' => 'Komponen NV UI', 'ipr' => null]);
        $butir = MasterEdpmButir::create([
            'komponen_id' => $komponen->id,
            'no_sk' => '1',
            'nomor_butir' => '1.1',
            'butir_pernyataan' => 'Butir NV UI',
        ]);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => AkreditasiStateMachine::STATUS_VALIDASI_ADMIN,
            'laporan_visitasi_asesor1' => 'dummy.pdf',
        ]);

        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => now()->subDay(),
            'tanggal_berakhir' => now()->addDay(),
        ]);

        AkreditasiEdpm::create([
            'akreditasi_id' => $akreditasi->id,
            'pesantren_id' => $akreditasi->user_id,
            'asesor_id' => $asesor->id,
            'butir_id' => $butir->id,
            'isian' => 3,
            'nk' => $nk,
            'nv' => $nv,
            'is_final' => false,
        ]);

        return compact('admin', 'akreditasi', 'butir');
    }
}
```

- [ ] **Step 2: Run test to verify failure**

Run:

```bash
php artisan test tests/Feature/AdminNvUiContractTest.php --stop-on-failure
```

Expected before fix:

```txt
FAIL Tests\Feature\AdminNvUiContractTest
Failed asserting that response contains "name=\"adminNvs[...]
```

- [ ] **Step 3: Wrap editable NV area in one form**

Replace `resources/views/admin/akreditasi/detail/tabs/instrumen.blade.php` with:

```blade
@if ($activeTab === 'instrumen')
    <div class="d-flex flex-column gap-6">
        @if(! $canShowAdminScoring)
            @include('admin.akreditasi.detail.tabs.instrumen.gate-status')
        @else
            @php
                $canEditNv = (int) $akreditasi->status === \App\StateMachine\AkreditasiStateMachine::STATUS_VALIDASI_ADMIN
                    && ! (bool) $akreditasi->is_nv_final;
            @endphp

            @include('admin.akreditasi.detail.tabs.instrumen.document-alert')
            @include('admin.akreditasi.detail.tabs.instrumen.progress')

            @if($canEditNv)
                <form method="POST" action="{{ route('admin.akreditasi-detail.save-nv', $akreditasi->uuid) }}">
                    @csrf
                    @include('admin.akreditasi.detail.tabs.instrumen.score-table', ['canEditNv' => true])
                    @include('admin.akreditasi.detail.tabs.instrumen.nv-actions')
                </form>
            @else
                @include('admin.akreditasi.detail.tabs.instrumen.score-table', ['canEditNv' => false])
            @endif

            @include('admin.akreditasi.detail.tabs.instrumen.score-summary')
            @include('admin.akreditasi.detail.tabs.instrumen.final-decision')
            @include('admin.akreditasi.detail.tabs.instrumen.scroll-actions')
        @endif
    </div>
@endif
```

- [ ] **Step 4: Render NV inputs and reason fields**

In `resources/views/admin/akreditasi/detail/tabs/instrumen/score-table.blade.php`, replace the NV `<td class="text-center">...</td>` block with:

```blade
<td class="text-center min-w-200px">
    @php
        $nkValue = $adminNvs[$butir->id]['nk'] ?? null;
        $nvValue = old("adminNvs.$butir->id", $adminNvs[$butir->id]['nv'] ?? $nkValue);
        $reasonValue = old("nvReasons.$butir->id", '');
    @endphp

    @if($canEditNv ?? false)
        <select
            name="adminNvs[{{ $butir->id }}]"
            class="form-select form-select-sm"
            aria-label="Nilai Validasi butir {{ $no }}"
            required
        >
            <option value="">Pilih NV</option>
            @foreach([1, 2, 3, 4] as $option)
                <option value="{{ $option }}" @selected((string) $nvValue === (string) $option)>{{ $option }}</option>
            @endforeach
        </select>
        <textarea
            name="nvReasons[{{ $butir->id }}]"
            class="form-control form-control-sm mt-2"
            rows="2"
            maxlength="2000"
            placeholder="Alasan jika NV berbeda dari NK"
            aria-label="Alasan perubahan NV butir {{ $no }}"
        >{{ $reasonValue }}</textarea>
    @else
        <span class="badge badge-light-success fs-7">
            {{ $adminNvs[$butir->id]['nv'] ?? '-' }}
        </span>
    @endif
</td>
```

- [ ] **Step 5: Replace nested NV form with submit buttons**

Replace `resources/views/admin/akreditasi/detail/tabs/instrumen/nv-actions.blade.php` with:

```blade
<x-ui.section-card title="Simpan Nilai Validasi (NV)" subtitle="Simpan draft atau finalisasi seluruh Nilai Validasi yang telah diinput.">
    <div class="p-6 d-flex flex-wrap gap-3">
        <x-ui.button
            type="submit"
            variant="light-primary"
            size="lg"
            formaction="{{ route('admin.akreditasi-detail.save-nv', $akreditasi->uuid) }}"
        >
            <x-ui.icon name="save-2" class="fs-4 me-2" />
            Simpan Draft NV
        </x-ui.button>

        <x-ui.button
            type="submit"
            variant="success"
            size="lg"
            formaction="{{ route('admin.akreditasi-detail.finalize-nv', $akreditasi->uuid) }}"
            data-spm-confirm="true"
            data-spm-confirm-title="Finalisasi semua NV?"
            data-spm-confirm-text="NV yang sudah final tidak dapat diubah. Alasan wajib diisi untuk setiap NV yang berbeda dari NK."
            data-spm-confirm-button="Ya, finalisasi"
        >
            <x-ui.icon name="lock-2" class="fs-4 me-2" />
            Finalisasi Semua NV
        </x-ui.button>
    </div>
</x-ui.section-card>
```

- [ ] **Step 6: Align controller validation names**

In `app/Http/Controllers/Admin/AkreditasiDetailController.php`, inside `saveAdminNv`, replace validation block:

```php
$request->validate([
    'adminNvs' => 'required|array',
    'nvReason' => 'nullable|string|max:2000',
]);
```

with:

```php
$request->validate([
    'adminNvs' => 'required|array',
    'nvReasons' => 'nullable|array',
    'nvReasons.*' => 'nullable|string|max:2000',
]);
```

Then replace the loop body:

```php
foreach ($request->input('adminNvs', []) as $butirId => $nvValue) {
    if (! empty($nvValue) && is_numeric($nvValue) && $nvValue >= 1 && $nvValue <= 4) {
        try {
            $this->scoringService->saveNV($akreditasi->id, $adminId, (int) $butirId, (int) $nvValue, false);
        } catch (\Throwable $e) {
            $errors[] = "Butir #{$butirId}: " . $e->getMessage();
        }
    }
}
```

with:

```php
$reasons = $request->input('nvReasons', []);

foreach ($request->input('adminNvs', []) as $butirId => $nvValue) {
    if (! empty($nvValue) && is_numeric($nvValue) && $nvValue >= 1 && $nvValue <= 4) {
        $reason = isset($reasons[$butirId]) && is_string($reasons[$butirId]) ? trim($reasons[$butirId]) : null;
        if ($reason === '') {
            $reason = null;
        }

        try {
            $this->scoringService->saveNV($akreditasi->id, $adminId, (int) $butirId, (int) $nvValue, false, $reason);
        } catch (\Throwable $e) {
            $errors[] = "Butir #{$butirId}: " . $e->getMessage();
        }
    }
}
```

- [ ] **Step 7: Run tests**

Run:

```bash
php artisan test tests/Feature/AdminNvUiContractTest.php tests/Feature/AdminFinalizeNvHttpTest.php
```

Expected:

```txt
PASS  Tests\Feature\AdminNvUiContractTest
PASS  Tests\Feature\AdminFinalizeNvHttpTest
```

- [ ] **Step 8: Commit**

```bash
git add tests/Feature/AdminNvUiContractTest.php app/Http/Controllers/Admin/AkreditasiDetailController.php resources/views/admin/akreditasi/detail/tabs/instrumen.blade.php resources/views/admin/akreditasi/detail/tabs/instrumen/score-table.blade.php resources/views/admin/akreditasi/detail/tabs/instrumen/nv-actions.blade.php
git commit -m "Fix admin NV validation UI contract"
```

---

### Task 3: Run focused regression suite

**Files:**
- No source edits unless a test fails.

- [ ] **Step 1: Run focused backend tests**

Run:

```bash
php artisan test tests/Feature/BusinessFlow/BusinessFlowHttpContractTest.php tests/Feature/BusinessFlow/BusinessFlowHappyPathTest.php tests/Feature/BusinessFlow/BusinessFlowNegativeTest.php tests/Feature/AdminNvUiContractTest.php tests/Feature/AdminFinalizeNvHttpTest.php tests/Feature/AsesorSaveEdpmHttpTest.php tests/Feature/AsesorUploadLaporanHttpTest.php
```

Expected:

```txt
PASS
```

If failure is from changed files, fix only the failing contract. Do not refactor services.

- [ ] **Step 2: Run full business-flow suite**

Run:

```bash
php artisan test --filter=BusinessFlow
```

Expected:

```txt
PASS
```

- [ ] **Step 3: Run full test suite**

Run:

```bash
php artisan test
```

Expected:

```txt
PASS
```

- [ ] **Step 4: Run frontend build**

Run:

```bash
npm run build
```

Expected:

```txt
built in
```

- [ ] **Step 5: Commit verification-only doc note if needed**

If no source changed after Task 2, do not commit. If a small compatibility fix was needed, commit that fix only:

```bash
git add <changed-files>
git commit -m "Fix regression from workflow contract cleanup"
```

---

### Task 4: Browser smoke audit per role

**Files:**
- Create: `docs/ui-role-audit-2026-07-07.md`

- [ ] **Step 1: Create audit evidence doc**

Create `docs/ui-role-audit-2026-07-07.md`:

```markdown
# UI Role Audit - 2026-07-07

## Scope

Manual/browser smoke audit for MVP readiness after backend flow tests and route contract fixes.

## Environment

- App: local Laravel app
- Browser: Chromium/Chrome
- Seed data: `php artisan migrate:fresh --seed` plus `php artisan db:seed --class=BusinessFlowTestSeeder`

## Checklist

| Role | Route | Expected | Result | Notes |
|---|---|---|---|---|
| Super Admin | `/dashboard` | Dashboard loads; admin/governance navigation visible; pesantren/asesor-only flows blocked | Pending |  |
| Super Admin | `/admin/master-role-permission` | Permission matrix visible; core role mutation guard understandable | Pending |  |
| Admin | `/dashboard` | Operational counters load; akreditasi CTA clear | Pending |  |
| Admin | `/admin/akreditasi` | Table, filter, action menu, detail link usable | Pending |  |
| Admin | `/admin/akreditasi/{uuid}?tab=instrumen` | NV selects, reason fields, draft/final buttons visible in status 1 | Pending |  |
| Asesor | `/asesor/akreditasi` | Assigned tasks visible; focus filters work | Pending |  |
| Asesor | `/asesor/akreditasi/{uuid}?tab=instrumen` | NA/NK workflow visible; finalize scoring posts `akreditasi_id` | Pending |  |
| Asesor | `/asesor/akreditasi/{uuid}?tab=laporan` | Laporan upload controls visible only at pasca visitasi | Pending |  |
| Pesantren | `/dashboard` | Submission state clear; CTA points to next action | Pending |  |
| Pesantren | `/pesantren/akreditasi` | Active/final/rejected/banding statuses understandable | Pending |  |
| Pesantren | `/pesantren/akreditasi/{uuid}` | Hasil akhir and banding/perbaikan state visible when applicable | Pending |  |

## Findings

| ID | Role | Severity | Route | Finding | Fix |
|---|---|---|---|---|---|

## Final Result

Pending until every checklist row is Pass or has a linked fix.
```

- [ ] **Step 2: Prepare local data**

Run:

```bash
php artisan migrate:fresh --seed
php artisan db:seed --class=BusinessFlowTestSeeder
```

Expected:

```txt
INFO  Database seeded successfully.
```

- [ ] **Step 3: Start app**

Run:

```bash
composer run dev
```

Expected:

```txt
server
vite
```

- [ ] **Step 4: Execute checklist**

For each row in `docs/ui-role-audit-2026-07-07.md`:

1. Login as seeded role from `docs/business-flow-test-plan.md`.
2. Visit the route.
3. Mark `Result` as `Pass` or `Fail`.
4. If `Fail`, add a row under Findings with a route and exact visible issue.

- [ ] **Step 5: Fix only P0/P1 UI blockers**

Definition of blocker:

```txt
P0: role cannot complete required workflow action.
P1: action is available but label/state/CTA can cause wrong operation.
P2: cosmetic spacing/copy inconsistency.
```

Fix P0/P1 only in this batch. Defer P2.

- [ ] **Step 6: Re-run affected browser row**

After each P0/P1 fix, revisit the exact route and mark the row Pass.

- [ ] **Step 7: Commit audit doc and UI fixes**

```bash
git add docs/ui-role-audit-2026-07-07.md resources/views resources/css app tests
git commit -m "Complete role UI smoke audit fixes"
```

---

### Task 5: Finalize scorecard and execution plan docs

**Files:**
- Modify: `docs/mvp-98-audit-scorecard.md`
- Modify: `docs/next-execution-plan-ui-audit-2026-06-29.md`
- Modify: `docs/README.md`

- [ ] **Step 1: Update scorecard open items**

In `docs/mvp-98-audit-scorecard.md`, make these exact status updates after Tasks 1-4 pass:

```markdown
## Yang Perlu Difinalisasi

- [x] Review ulang skor baseline per area dengan evidence terbaru.
- [x] Ubah item `Open` baseline menjadi status yang lebih jujur.
- [x] Tetapkan blocker resmi untuk batch execute pertama.
- [x] Putuskan arah baseline 78% tetap dipakai.
- [x] Finalisasi skor area UI setelah audit dashboard nyata.
```

Replace P0 table rows with:

```markdown
| Item | Status | Catatan |
| --- | --- | --- |
| Kontrak `NV != NK` + reason | Done | UI mengirim `nvReasons[butir_id]`; HTTP regression finalisasi NV tetap pass |
| HTTP regression action inti per role | Done | `BusinessFlowHttpContractTest`, `BusinessFlow*Test`, admin/asesor HTTP tests pass |
| Audit flow per status vs implementasi | Done | Matrix baseline dan business-flow suite pass |
| Jadwal visitasi dan action asesor sinkron | Done | Confirm visitasi + finalize scoring route contract pass |
| Hasil akhir pesantren sesuai policy | Done | Tab hasil baseline pakai field final tanpa promosi raw score |
```

Replace P1 table rows for route/UI docs with:

```markdown
| Frontend-backend route contract sync | Done | Asesor dynamic POST includes `akreditasi_id`; admin NV form emits controller field names |
| UI polish dashboard role | Done | Evidence: `docs/ui-role-audit-2026-07-07.md` |
| legacy reactive audit dan cleanup | Done | Runtime bersih; docs utama sinkron; residue historis ditandai historical |
```

Replace Gate statuses with:

```markdown
### Gate 1 - Stabilkan Flow Bisnis

- [x] Semua status punya audit matrix baseline expected vs actual.
- [x] Semua gap P0 dipastikan tertutup.
- [x] Semua fix P0 punya test atau check yang bisa diulang.

### Gate 2 - Kunci Governance

- [x] Semua permission mutasi penting eksplisit.
- [x] Role inti aman dari salah hapus/salah edit.
- [x] Kontrak admin dan super admin tidak ambigu untuk role mutation.

### Gate 3 - Rapikan UI Role

- [x] Dashboard semua role lolos checklist usability inti.
- [x] Tidak ada CTA membingungkan atau status wording tumpang tindih.
- [x] Empty state, error state, loading state, table state konsisten untuk flow MVP.

### Gate 4 - Bersihkan sisa legacy reactive layer

- [x] Semua referensi runtime reactive layer lama terinventaris.
- [x] Jejak aktif runtime tidak ditemukan.
- [x] Dokumentasi utama sesuai implementasi final.
```

- [ ] **Step 2: Update next execution plan checklist**

In `docs/next-execution-plan-ui-audit-2026-06-29.md`, replace Checklist section with:

```markdown
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
```

- [ ] **Step 3: Add audit doc to docs index**

In `docs/README.md`, under Current docs, add:

```markdown
- [UI role audit 2026-07-07](ui-role-audit-2026-07-07.md)
```

- [ ] **Step 4: Run docs grep sanity check**

Run:

```bash
git diff --check
```

Expected:

```txt
(no output)
```

- [ ] **Step 5: Commit docs**

```bash
git add docs/mvp-98-audit-scorecard.md docs/next-execution-plan-ui-audit-2026-06-29.md docs/README.md docs/ui-role-audit-2026-07-07.md
git commit -m "Finalize MVP scorecard and UI audit docs"
```

---

### Task 6: Final ship gate

**Files:**
- No source edits unless checks fail.

- [ ] **Step 1: Check working tree**

Run:

```bash
git status --short
```

Expected:

```txt
(no output)
```

If output exists, either commit intentional files or revert accidental files.

- [ ] **Step 2: Run final verification**

Run:

```bash
git diff --check
php artisan test
npm run build
```

Expected:

```txt
git diff --check: no output
php artisan test: PASS
npm run build: built in ...
```

- [ ] **Step 3: Produce final handoff summary**

Use this exact summary shape:

```markdown
## Completed

- Fixed asesor dynamic POST contract: confirm visitasi/finalize scoring submit `akreditasi_id`.
- Fixed admin NV UI contract: editable NV inputs + per-butir reasons + draft/final buttons.
- Added HTTP regressions for asesor workflow and admin NV UI.
- Completed role UI audit evidence.
- Updated MVP scorecard and execution docs.

## Verification

- `php artisan test` — PASS
- `npm run build` — PASS
- `git diff --check` — PASS

## Deferred

- P2 cosmetic-only polish not blocking MVP operations.
```

---

## Self-Review

- Spec coverage: all open items found in scorecard/next execution docs map to tasks: route contract (Tasks 1-2), finalize scoring HTTP path (Task 1), UI audit (Task 4), scorecard/docs cleanup (Task 5), verification (Task 6).
- Placeholder scan: no `TBD`, `TODO`, or undefined task references. P2 cosmetic work is explicitly deferred with a definition.
- Type consistency: tests use existing route names: `asesor.akreditasi-detail`, `asesor.akreditasi.confirm-visitasi-selesai`, `asesor.akreditasi.finalize-scoring`, `admin.akreditasi-detail`, `admin.akreditasi-detail.save-nv`, `admin.akreditasi-detail.finalize-nv`.
