# Flowchart Proses Bisnis Akreditasi (After Fix)

## State Machine (9 Status)

```
Status 6 ──[openForReview]──▶ Status 5 ──[approveBerkas 🔒]──▶ Status 4 ──[confirmVisitasiSelesai]──▶ Status 3
 Pengajuan                    Verifikasi Berkas               Assessment                       Penilaian Pasca Visitasi
    ▲                              │                              │                                    │
    │                              │                              │                                    │
    │                    [rejectBerkas]                  [rejection ke-3]                      [finalizeScoring]
    │                              │                              │                                    │
    │                              ▼                              ▼                                    ▼
    │                          Status -1 ◀──────────────────────────── Status -1                   Status 2
    │                           Ditolak                                                          Validasi Akhir Asesor
    │                              ▲                                                                   │
    │                              │                                                                   │
    │                              │                     [approveAtValidasiAkhir] / [rejectAtValidasiAkhir]
    │                              │                                                                   │
    │                              │                                          ┌────────────────────────┤
    │                              │                                          │                        │
    │                              │                                          ▼                        ▼
    │                              │                                     Status 1                   Status -1
    │                              │                                 Validasi Akhir Admin             Ditolak
    │                              │                                          │                        ▲
    │                              │                                          │                        │
    │                              │                          [issueSK 🔒]   │   [rejectAtValidasi 🔒] │
    │                              │                                          │                        │
    │                              │                                          ▼                        │
    │                              │                                     Status 0                      │
    │                              │                                      Selesai                       │
    │                              │                                                                   │
    │                              │                    ┌──────────────────────────────────────────────┘
    │                              │                    │
    │                              │                    ▼
    │                              │              [submitBanding]
    │                              │                    │
    │                              │                    ▼
    │                              │              Status -2
    │                              │               Banding
    │                              │                    │
    │                              │         ┌──────────┴──────────┐
    │                              │         │                      │
    │                              │    [decideBanding       [decideBanding
    │                              │     diterima ✅]          ditolak ✅]
    │                              │         │                      │
    │                              │         │    ◀── keputusan     │   ◀── keputusan
    │                              │         │    admin disimpan    │   admin disimpan
    │                              │         │                      │
    │                              └─────────┘                      │
    │                           banding diterima                    │
    │                           → kembali ke                        │
    │                           Validasi Admin                      │
    │                                                               │
    └───────────────────────────────────────────────────────────────┘
                          banding ditolak → ke Ditolak
```

## Detail Per Fix

### 🔒 Optimistic Locking (Baru)

| Transisi | Method | File |
|----------|--------|------|
| 5 → 4 | `approveBerkas(..., $clientUpdatedAt)` | `AkreditasiWorkflowService.php` L175 |
| 1 → 0 | `issueSK(..., $clientUpdatedAt)` | `AkreditasiWorkflowService.php` L788 |
| 1 → -1 | `rejectAtValidasi(..., $clientUpdatedAt)` | `AkreditasiWorkflowService.php` L945 (existing) |

**Client-side (admin blade):**
- `approveBerkas()` → kirim `$this->akreditasiUpdatedAt`, catch `StaleStateException`
- `approve()` → kirim `$this->akreditasiUpdatedAt` ke `issueSK()`, catch `ConflictException` + `StaleStateException`

### ✅ Keputusan Banding Tersimpan (Bug Fix)

| Flow | Sebelum | Sesudah |
|------|---------|---------|
| Banding diterima | `keputusan` = `'Diterima'` (hardcode) | `keputusan` = teks alasan admin |
| Banding ditolak | `keputusan` = `'Ditolak'` (hardcode) | `keputusan` = teks alasan admin |

**Chain:** `banding-detail.blade.php` → `WorkflowService::decideBanding()` → `BandingService::decideBanding()`

Fallback ke hardcode jika parameter kosong (backward compatible).

### ⚠️ Deprecated (Tidak Dihapus)

- `BandingService::acceptBanding()` — legacy entry point
- `BandingService::rejectBanding()` — legacy entry point

Masih dipakai 13 call site di 3 file test. Ditandai `@deprecated`.

## Status Flow Ringkasan

```
Pengajuan (6) → Verifikasi Berkas (5) → Assessment (4) → Penilaian (3) → Validasi Asesor (2) → Validasi Admin (1) → Selesai (0)
                      │                        │                                │                       │
                      ▼                        ▼                                ▼                       ▼
                   Ditolak (-1) ◀──────── rejection ke-3                   Ditolak (-1)           Ditolak (-1)
                      ▲                                                                                │
                      │                                                                                │
                      └────────────────── banding ditolak ◀── Banding (-2) ◀── submitBanding ──────────┘
                                                 │
                                                 └── banding diterima → Validasi Admin (1)
```
