<?php

namespace App\Services;

use App\Models\Akreditasi;
use App\Models\AkreditasiEdpm;
use App\Models\AkreditasiEdpmCatatan;
use App\Models\Assessment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class TrashService
{
    public function __construct() {}

    /**
     * Paginate soft-deleted akreditasi records, optionally filtered by pesantren name.
     */
    public function getPaginatedTrashed(?string $search, int $perPage = 10): LengthAwarePaginator
    {
        $query = Akreditasi::onlyTrashed()
            ->with(['user.pesantren']);

        if ($search !== null && trim($search) !== '') {
            $term = '%'.trim($search).'%';
            $query->where(function ($q) use ($term) {
                $q->whereHas('user.pesantren', fn ($qq) => $qq->where('nama_pesantren', 'like', $term))
                    ->orWhereHas('user', fn ($qq) => $qq->where('name', 'like', $term));
            });
        }

        return $query->orderByDesc('deleted_at')->paginate($perPage);
    }

    /**
     * @return array{
     *     akreditasi: Akreditasi,
     *     children: array{
     *         assessment: int,
     *         akreditasi_edpm: int,
     *         akreditasi_edpm_catatan: int,
     *         total: int,
     *     }
     * }
     */
    public function getRestorePreview(int $akreditasiId): array
    {
        $akreditasi = Akreditasi::onlyTrashed()->findOrFail($akreditasiId);

        $assessmentCount = Assessment::onlyTrashed()->where('akreditasi_id', $akreditasiId)->count();
        $edpmCount = AkreditasiEdpm::onlyTrashed()->where('akreditasi_id', $akreditasiId)->count();
        $edpmCatatanCount = AkreditasiEdpmCatatan::onlyTrashed()->where('akreditasi_id', $akreditasiId)->count();

        return [
            'akreditasi' => $akreditasi,
            'children' => [
                'assessment' => $assessmentCount,
                'akreditasi_edpm' => $edpmCount,
                'akreditasi_edpm_catatan' => $edpmCatatanCount,
                'total' => $assessmentCount + $edpmCount + $edpmCatatanCount,
            ],
        ];
    }

    /**
     * Restore the akreditasi parent and all soft-deleted children.
     * Returns the total record count restored (parent + children).
     */
    public function restore(int $akreditasiId): int
    {
        try {
            return DB::transaction(function () use ($akreditasiId): int {
                $akreditasi = Akreditasi::onlyTrashed()->lockForUpdate()->findOrFail($akreditasiId);

                $childAssessment = Assessment::onlyTrashed()->where('akreditasi_id', $akreditasiId)->restore() ?? 0;
                $childEdpm = AkreditasiEdpm::onlyTrashed()->where('akreditasi_id', $akreditasiId)->restore() ?? 0;
                $childCatatan = AkreditasiEdpmCatatan::onlyTrashed()->where('akreditasi_id', $akreditasiId)->restore() ?? 0;

                $akreditasi->restore();

                return 1 + (int) $childAssessment + (int) $childEdpm + (int) $childCatatan;
            });
        } catch (Throwable $e) {
            Log::error('TrashService::restore failed', [
                'akreditasi_id' => $akreditasiId,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Gagal memulihkan akreditasi.', 0, $e);
        }
    }

    /**
     * Permanently delete the akreditasi parent and all children (whether trashed or not).
     * Returns the total record count permanently removed.
     */
    public function forceDelete(int $akreditasiId): int
    {
        try {
            return DB::transaction(function () use ($akreditasiId): int {
                $akreditasi = Akreditasi::withTrashed()->lockForUpdate()->findOrFail($akreditasiId);

                $childAssessment = Assessment::withTrashed()->where('akreditasi_id', $akreditasiId)->forceDelete() ?? 0;
                $childEdpm = AkreditasiEdpm::withTrashed()->where('akreditasi_id', $akreditasiId)->forceDelete() ?? 0;
                $childCatatan = AkreditasiEdpmCatatan::withTrashed()->where('akreditasi_id', $akreditasiId)->forceDelete() ?? 0;

                // Bersihkan jejak FK lain (rejections, akreditasi_catatans, bandings, audit logs) jika tabelnya ada.
                foreach (['akreditasi_rejections', 'akreditasi_catatans', 'bandings', 'akreditasi_audit_logs'] as $table) {
                    if (Schema::hasTable($table)) {
                        DB::table($table)->where('akreditasi_id', $akreditasiId)->delete();
                    }
                }

                // Skip observer agar event "deleting" tidak menulis ulang audit log
                // setelah baris-barisnya baru saja dibersihkan di atas.
                Akreditasi::withoutEvents(function () use ($akreditasi) {
                    $akreditasi->forceDelete();
                });

                return 1 + (int) $childAssessment + (int) $childEdpm + (int) $childCatatan;
            });
        } catch (Throwable $e) {
            Log::error('TrashService::forceDelete failed', [
                'akreditasi_id' => $akreditasiId,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Gagal menghapus permanen akreditasi.', 0, $e);
        }
    }

    public function getTrashCount(): int
    {
        return Akreditasi::onlyTrashed()->count();
    }

    /**
     * Permanently delete trashed records older than the retention window.
     *
     * @return array{purged: int, failed: int, errors: array<int, array{id: int, error: string}>}
     */
    public function purgeExpired(int $retentionDays = 90): array
    {
        $cutoff = Carbon::now()->subDays($retentionDays);

        $expired = Akreditasi::onlyTrashed()
            ->where('deleted_at', '<=', $cutoff)
            ->pluck('id');

        $purged = 0;
        $failed = 0;
        $errors = [];

        foreach ($expired as $id) {
            try {
                $this->forceDelete((int) $id);
                $purged++;
            } catch (Throwable $e) {
                $failed++;
                $errors[] = [
                    'id' => (int) $id,
                    'error' => $e->getMessage(),
                ];

                Log::warning('TrashService::purgeExpired single failure', [
                    'akreditasi_id' => $id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'purged' => $purged,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }
}
