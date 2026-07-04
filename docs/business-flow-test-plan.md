# Business Flow Test Plan

Dokumen ini menyusun rencana pengujian end-to-end flow bisnis sistem akreditasi pesantren, termasuk happy path, negative path, role access matrix, dan penggunaan seeder sebagai fixture deterministic.

## Tujuan

Memastikan sistem berjalan sesuai flow bisnis dan batasan role:

- Pesantren hanya dapat mengelola data dan pengajuan miliknya.
- Admin/super admin hanya menjalankan aksi governance, verifikasi, validasi, dan banding.
- Asesor hanya mengerjakan akreditasi yang ditugaskan.
- Status akreditasi hanya berubah melalui transisi yang valid.
- Negative case tidak membuat partial write, orphan file, atau status korup.

## Prinsip Pengujian

- Seeder digunakan untuk membuat fixture deterministic, bukan menyimpan hasil test.
- Setiap scenario diberi kode stabil: `BF-HAPPY-*`, `BF-NEG-*`, `BF-BANDING-*`, `BF-ROLE-*`.
- Tracking scenario dilakukan lewat prefix field text, misalnya `akreditasis.catatan = "[BF-HAPPY-001] ..."`.
- Hasil test dilacak lewat PHPUnit/JUnit report.
- Test harus memvalidasi role, ownership, status, DB side effect, dan audit trail.

## Command Utama

```bash
php artisan migrate:fresh --seed
php artisan db:seed --class=BusinessFlowTestSeeder
php artisan test --filter=BusinessFlow
```

Optional JUnit report:

```bash
php artisan test --filter=BusinessFlow --log-junit storage/app/business-flow-junit.xml
```

## Seeder Plan

### File Seeder

```txt
database/seeders/BusinessFlowTestSeeder.php
```

### Seeder Dependencies

Seeder ini harus memastikan master data tersedia dengan memanggil atau mengandalkan:

- `RoleSeeder`
- `PermissionSeeder`
- `RolePermissionSeeder`
- `MasterEdpmSeeder`
- `DocumentCategorySeeder`

### Seeded Users

| Code | Role | Email |
|---|---|---|
| `BF-SA` | super admin | `bf.superadmin@test.local` |
| `BF-ADMIN` | admin | `bf.admin@test.local` |
| `BF-PESANTREN` | pesantren | `bf.pesantren@test.local` |
| `BF-PESANTREN-OTHER` | pesantren | `bf.pesantren.other@test.local` |
| `BF-ASESOR-1` | asesor | `bf.asesor1@test.local` |
| `BF-ASESOR-2` | asesor | `bf.asesor2@test.local` |
| `BF-ASESOR-UNASSIGNED` | asesor | `bf.asesor.unassigned@test.local` |
| `BF-ASESOR-INACTIVE` | asesor | `bf.asesor.inactive@test.local` |

### Seeded Master Data

- Role + permission lengkap.
- Master EDPM lengkap.
- Kategori dokumen.
- Dokumen wajib.
- Pesantren lengkap.
- Pesantren belum lengkap.
- Asesor aktif dan nonaktif.

### Seeded Akreditasi Scenarios

| Code | Status | Tujuan |
|---|---:|---|
| `BF-HAPPY-001` | `6 Pengajuan` | pengajuan baru |
| `BF-HAPPY-002` | `5 Verifikasi Berkas` | admin review berkas |
| `BF-HAPPY-003` | `4 Review Asesor` | asesor assigned |
| `BF-HAPPY-004` | `3 Visitasi` | visitasi scheduled |
| `BF-HAPPY-005` | `2 Pasca Visitasi` | scoring/laporan |
| `BF-HAPPY-006` | `1 Validasi Admin` | final admin |
| `BF-HAPPY-007` | `0 Selesai` | sertifikat terbit |
| `BF-NEG-001` | none | pesantren data belum lengkap |
| `BF-NEG-002` | active | pengajuan milik pesantren lain |
| `BF-NEG-003` | `4 Review Asesor` | asesor tidak assigned |
| `BF-NEG-004` | `0 Selesai` | terminal selesai |
| `BF-NEG-005` | `-1 Ditolak` | terminal ditolak |
| `BF-NEG-006` | `-1 Ditolak` | banding sudah pernah diajukan |
| `BF-NEG-007` | `4 Review Asesor` | visitasi invalid > 14 hari |
| `BF-NEG-008` | `5 Verifikasi Berkas` | asesor1 = asesor2 |
| `BF-NEG-009` | `1 Validasi Admin` | upload file invalid |
| `BF-NEG-010` | active | stale/concurrent update |
| `BF-BANDING-001` | `-1 Ditolak` | eligible banding |
| `BF-BANDING-002` | `-2 Banding` | admin review banding |

### Tracking Format

Untuk table tanpa metadata JSON:

```php
'catatan' => '[BF-HAPPY-001] submitted by seeded pesantren',
```

Untuk audit atau table dengan metadata:

```php
'metadata' => [
    'test_code' => 'BF-HAPPY-003',
    'actor' => 'BF-ASESOR-1',
]
```

## Test Suite Plan

### Files

```txt
tests/Feature/BusinessFlow/BusinessFlowRoleTest.php
tests/Feature/BusinessFlow/BusinessFlowHappyPathTest.php
tests/Feature/BusinessFlow/BusinessFlowNegativeTest.php
tests/Feature/BusinessFlow/BusinessFlowBandingTest.php
```

## Business Status Flow

Canonical happy path:

```txt
Pesantren isi Profil/IPM/SDM/EDPM
→ submit pengajuan
→ 6 Pengajuan
→ admin buka review
→ 5 Verifikasi Berkas
→ admin approve berkas + assign asesor
→ 4 Review Asesor
→ asesor jadwalkan visitasi
→ 3 Visitasi
→ asesor konfirmasi visitasi selesai
→ 2 Penilaian Pasca Visitasi
→ asesor finalisasi NA1/NA2/NK + laporan
→ pesantren upload kartu kendali
→ 1 Validasi Admin
→ admin finalisasi NV
→ admin terbitkan SK/sertifikat
→ 0 Selesai
```

Negative/banding path:

```txt
5/4/1 → -1 Ditolak
-1 → pesantren ajukan banding
-2 Banding
→ admin terima → 1 Validasi Admin
→ admin tolak → -1 Ditolak
```

## Role Access Matrix Tests

### Super Admin

| Test | Expected |
|---|---|
| GET `/dashboard` | `200` |
| GET `/admin/master-role-permission` | `200` |
| GET `/accounts` | `200` |
| GET `/admin/akreditasi` | `200` |
| GET `/admin/banding` | `200` |
| GET `/pesantren/akreditasi` | `403` unless bypass intentionally allowed |
| GET `/asesor/akreditasi` | `403` unless bypass intentionally allowed |

### Admin

| Test | Expected |
|---|---|
| GET `/dashboard` | `200` |
| GET `/admin/akreditasi` | `200` |
| GET `/admin/master-edpm` | `200` if permission exists |
| GET `/admin/banding` | `200` |
| GET `/pesantren/akreditasi` | `403` |
| GET `/asesor/akreditasi` | `403` |

### Pesantren

| Test | Expected |
|---|---|
| GET `/dashboard` | `200` |
| GET `/pesantren/profile` | `200` |
| GET `/pesantren/ipm` | `200` |
| GET `/pesantren/sdm` | `200` |
| GET `/pesantren/edpm` | `200` |
| GET `/pesantren/akreditasi` | `200` |
| GET `/admin/akreditasi` | `403` |
| GET `/asesor/akreditasi` | `403` |

### Asesor

| Test | Expected |
|---|---|
| GET `/dashboard` | `200` |
| GET `/asesor/profile` | `200` |
| GET `/asesor/akreditasi` | `200` |
| GET `/admin/akreditasi` | `403` |
| GET `/pesantren/akreditasi` | `403` |

## Cross-role Action Matrix

| Action | Allowed | Forbidden |
|---|---|---|
| Submit akreditasi | owner pesantren | admin, asesor, other pesantren |
| Approve berkas | admin/super admin | pesantren, asesor |
| Assign asesor | admin/super admin | pesantren, asesor |
| Schedule visitasi | assigned asesor | admin, pesantren, unassigned asesor |
| Save NA/NK | assigned asesor | admin, pesantren, unassigned asesor |
| Upload laporan visitasi | assigned asesor | admin, pesantren, unassigned asesor |
| Upload kartu kendali | owner pesantren | admin, asesor, other pesantren |
| Finalize NV | admin/super admin | pesantren, asesor |
| Issue SK | admin/super admin | pesantren, asesor |
| Submit banding | owner pesantren | admin, asesor, other pesantren |
| Decide banding | admin/super admin | pesantren, asesor |

Expected unauthorized response:

```txt
403
```

For tenant isolation, `404` is acceptable if used intentionally to hide resource existence.

## Happy Path Tests

### BF-HAPPY-001 — Pesantren Submit Pengajuan

Actor: `BF-PESANTREN`

Action:

```txt
POST /pesantren/akreditasi/create
```

Assert:

- Akreditasi created.
- `user_id` equals seeded pesantren user.
- Status equals expected initial business state.
- Pesantren profile locked.
- Audit log exists.
- Other pesantren cannot see this akreditasi.

### BF-HAPPY-002 — Admin Verifikasi Berkas

Actor: `BF-ADMIN`

Action:

```txt
POST /admin/akreditasi/{uuid}/approve-berkas
```

Assert:

- Status moves to `4 Review Asesor`.
- Asesor 1 assessment created.
- Asesor 2 assessment created.
- Asesor 1 and asesor 2 are different.
- Audit log exists.

### BF-HAPPY-003 — Asesor Schedule Visitasi

Actor: `BF-ASESOR-1`

Action:

```txt
POST /asesor/akreditasi/schedule-visitasi
```

Assert:

- Status moves to `3 Visitasi`.
- `tgl_visitasi` saved.
- `tgl_visitasi_akhir` saved.
- Date range max 14 days.
- Unassigned asesor cannot schedule.

### BF-HAPPY-004 — Asesor Confirm Visitasi Selesai

Actor: `BF-ASESOR-1`

Action:

```txt
POST /asesor/akreditasi/confirm-visitasi-selesai
```

Assert:

- Status moves to `2 Penilaian Pasca Visitasi`.
- `visitasi_confirmed_at` set.
- Audit log exists.

### BF-HAPPY-005 — Asesor Scoring Dan Laporan

Actors:

- `BF-ASESOR-1`
- `BF-ASESOR-2`

Actions:

```txt
POST /asesor/akreditasi/save-na
POST /asesor/akreditasi/save-nk
POST /asesor/akreditasi/upload-laporan-individu
POST /asesor/akreditasi/upload-laporan-kelompok
POST /asesor/akreditasi/finalize-scoring
```

Assert:

- NA1 saved by asesor 1.
- NA2 saved by asesor 2.
- NK saved.
- Laporan individu asesor 1 saved.
- Laporan individu asesor 2 saved.
- Laporan kelompok saved.
- Status moves to `1 Validasi Admin` after finalization.
- Unassigned asesor cannot score.

### BF-HAPPY-006 — Pesantren Upload Kartu Kendali

Actor: `BF-PESANTREN`

Action:

```txt
POST /pesantren/akreditasi/upload-kartu-kendali
```

Assert:

- File accepted when type/size valid.
- DB path saved.
- Other pesantren cannot upload for this akreditasi.

### BF-HAPPY-007 — Admin Finalisasi NV

Actor: `BF-ADMIN`

Action:

```txt
POST /admin/akreditasi/{uuid}/finalize-nv
```

Assert:

- NV saved.
- `is_nv_final = true`.
- Status remains `1 Validasi Admin` until SK is issued.

### BF-HAPPY-008 — Admin Terbitkan SK/Sertifikat

Actor: `BF-ADMIN`

Action:

```txt
POST /admin/akreditasi/{uuid}/approve
```

Assert:

- `nomor_sk` saved.
- `sertifikat_path` saved.
- `masa_berlaku` saved.
- `masa_berlaku_akhir` saved.
- Status moves to `0 Selesai`.
- Terminal state cannot mutate.

## Negative Tests

### BF-NEG-001 — Guest Blocked

Actions:

```txt
GET /dashboard
GET /admin/akreditasi
GET /pesantren/akreditasi
GET /asesor/akreditasi
```

Expected:

```txt
302 redirect login
```

Assert:

- No DB writes.

### BF-NEG-002 — Wrong Role Blocked

| Actor | Action | Expected |
|---|---|---|
| pesantren | GET `/admin/akreditasi` | `403` |
| pesantren | GET `/asesor/akreditasi` | `403` |
| admin | GET `/pesantren/akreditasi` | `403` |
| admin | GET `/asesor/akreditasi` | `403` |
| asesor | GET `/admin/akreditasi` | `403` |
| asesor | GET `/pesantren/akreditasi` | `403` |

### BF-NEG-003 — Submit Pengajuan Tanpa Data Lengkap

Actor: incomplete pesantren

Action:

```txt
POST /pesantren/akreditasi/create
```

Expected:

- Session error.
- Akreditasi not created.
- Pesantren not locked.

### BF-NEG-004 — Pesantren Akses Data Pesantren Lain

Actor: `BF-PESANTREN`

Actions:

```txt
GET /pesantren/akreditasi/{uuid milik BF-PESANTREN-OTHER}
POST /pesantren/akreditasi/banding id milik BF-PESANTREN-OTHER
POST /pesantren/akreditasi/upload-kartu-kendali id milik BF-PESANTREN-OTHER
```

Expected:

- `403` or `404`.
- Banding not created.
- File not uploaded.
- Akreditasi unchanged.

### BF-NEG-005 — Admin Assign Asesor Invalid

Actor: `BF-ADMIN`

Action:

```txt
POST /admin/akreditasi/{uuid}/approve-berkas
```

Cases:

| Payload | Expected |
|---|---|
| `asesor1Id` empty | validation error |
| `asesor2Id` empty | validation error |
| `asesor1Id == asesor2Id` | validation error |
| user bukan asesor | validation/domain error |
| asesor inactive | validation/domain error |

Assert:

- Status remains `5`.
- Assessment not created.
- Audit transition not written.

### BF-NEG-006 — Invalid Status Transition Blocked

Direct state machine/service tests:

```txt
6 → 4
6 → 0
5 → 3
4 → 1
3 → 0
0 → 1
-1 → 0
```

Expected:

- `InvalidTransitionException` or domain error.
- Status unchanged.
- Audit log not written.

### BF-NEG-007 — Unassigned Asesor Blocked

Actor: `BF-ASESOR-UNASSIGNED`

Actions:

```txt
GET /asesor/akreditasi/{uuid}
POST /asesor/akreditasi/schedule-visitasi
POST /asesor/akreditasi/save-na
POST /asesor/akreditasi/save-nk
POST /asesor/akreditasi/finalize-scoring
POST /asesor/akreditasi/upload-laporan-individu
POST /asesor/akreditasi/upload-laporan-kelompok
```

Expected:

- `403` or `404`.
- Nilai unchanged.
- File not uploaded.
- Status unchanged.

### BF-NEG-008 — Visitasi Invalid

Actor: assigned asesor

Cases:

| Payload | Expected |
|---|---|
| `tanggal_akhir < tanggal_mulai` | validation error |
| range > 14 days | session error |
| tanggal kosong | validation error |
| akreditasi bukan status `4` | domain error |

Assert:

- `tgl_visitasi` unchanged.
- `tgl_visitasi_akhir` unchanged.
- Status not changed to `3`.

### BF-NEG-009 — Scoring Belum Lengkap

Actor: assigned asesor

Action:

```txt
POST /asesor/akreditasi/finalize-scoring
```

Cases:

- NA1 empty.
- NA2 empty.
- NK empty.
- Laporan individu missing.
- Laporan kelompok missing.
- Kartu kendali missing if required.

Expected:

- Error response/session error.
- Status remains `2`.
- Final flags remain false.

### BF-NEG-010 — Admin Final SK Invalid

Actor: `BF-ADMIN`

Action:

```txt
POST /admin/akreditasi/{uuid}/approve
```

Cases:

| Payload | Expected |
|---|---|
| nomor SK empty | validation error |
| sertifikat not PDF | validation error |
| sertifikat too large | validation error |
| `masa_berlaku_akhir <= masa_berlaku` | validation error |
| status not `1` | warning/domain error |

Assert:

- Status not `0`.
- Sertifikat not saved.
- `nomor_sk` unchanged.
- No orphan stored file.

### BF-NEG-011 — Terminal State Immutable

Preconditions:

- `BF-NEG-004`: status `0 Selesai`.
- `BF-NEG-005`: status `-1 Ditolak`.

Try:

```txt
approve berkas
schedule visitasi
save nilai
finalize scoring
issue SK again
submit perbaikan
```

Expected:

- Blocked.
- Status unchanged.
- No duplicate audit transition.

### BF-NEG-012 — Upload Invalid

Actions:

```txt
POST /pesantren/akreditasi/upload-kartu-kendali
POST /asesor/akreditasi/upload-laporan-individu
POST /asesor/akreditasi/upload-laporan-kelompok
POST /admin/akreditasi/{uuid}/approve
```

Cases:

- Wrong extension.
- File too large.
- Empty file.
- Missing required file.

Expected:

- Validation error.
- Storage file not created.
- DB path unchanged.

### BF-NEG-013 — Concurrent/Stale Update

Precondition:

- Two requests use stale `updated_at` for the same akreditasi.

Action:

```txt
admin A approve
admin B reject/approve same akreditasi with stale timestamp
```

Expected:

- First request succeeds.
- Second request fails with conflict/stale message.
- Only one audit transition.
- Final status deterministic.

## Banding Tests

### BF-BANDING-001 — Pesantren Submit Banding Valid

Precondition:

- Akreditasi status `-1 Ditolak`.

Actor: owner pesantren

Action:

```txt
POST /pesantren/akreditasi/banding
```

Assert:

- Banding created.
- Akreditasi status moves to `-2 Banding`.
- Alasan min 50 chars.
- Audit log exists.

### BF-BANDING-002 — Admin Accept Banding

Actor: `BF-ADMIN`

Actions:

```txt
POST /admin/banding/{id}/assign-reviewer
POST /admin/banding/{id}/submit-decision
```

Assert:

- Reviewer assigned.
- Banding status accepted/diterima.
- Akreditasi status moves from `-2` to `1`.
- Audit log exists.

### BF-BANDING-003 — Admin Reject Banding

Actor: `BF-ADMIN`

Action:

```txt
POST /admin/banding/{id}/submit-decision
```

Assert:

- Banding status rejected/ditolak.
- Akreditasi status moves from `-2` to `-1`.
- Cannot mutate banding after decision.

### BF-BANDING-004 — Banding Invalid

Cases:

| Case | Expected |
|---|---|
| banding when akreditasi not `-1` | error |
| alasan < 50 chars | validation error |
| banding milik pesantren lain | `403` or `404` |
| second banding over limit | error |
| admin decision without reviewer | error |
| reviewer bukan admin | validation/domain error |
| decision invalid | validation error |

Assert:

- Banding not created or unchanged.
- Akreditasi status unchanged.

## Assertion Rules

Every negative test must assert at least three things:

```php
$response->assertStatus(403); // or assertSessionHasErrors/assertRedirect
$this->assertSame($oldStatus, (int) $akreditasi->fresh()->status);
$this->assertDatabaseMissing('...', [...]);
```

For uploads:

```php
Storage::disk('public')->assertMissing($unexpectedPath);
$this->assertSame($oldPath, $akreditasi->fresh()->sertifikat_path);
```

For transition tests:

```php
$this->assertSame($oldStatus, (int) $akreditasi->fresh()->status);
$this->assertDatabaseMissing('akreditasi_audit_logs', [
    'akreditasi_id' => $akreditasi->id,
    'action_type' => 'status_changed',
    // expected invalid to_status metadata should not exist
]);
```

## Pass Criteria

The system passes the business-flow test plan if:

- All role routes match the access matrix.
- All valid transitions pass.
- All invalid transitions are blocked.
- Every status step writes expected DB state.
- Every role only sees/updates allowed data.
- Terminal states are immutable.
- Banding returns to the correct status.
- Invalid upload creates no orphan file.
- Concurrent write conflict is detected.
- Seeder fixtures are reproducible.

## Deliberate Non-goals

- Browser click automation is not included here.
- Visual regression is not included here.
- Email/notification delivery is not required for business-flow pass, only notification records/events if already part of the transaction.

Add browser E2E only after backend flow is green.
