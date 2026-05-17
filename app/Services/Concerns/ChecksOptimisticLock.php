<?php

namespace App\Services\Concerns;

use App\Exceptions\ConflictException;
use App\Models\Akreditasi;

trait ChecksOptimisticLock
{
    /**
     * Verify the client's updated_at matches the DB value.
     * MUST be called inside a DB::transaction.
     * Uses SELECT FOR UPDATE to prevent race conditions.
     *
     * @throws ConflictException
     */
    protected function assertNotStale(int $akreditasiId, string $clientUpdatedAt): Akreditasi
    {
        $akreditasi = Akreditasi::where('id', $akreditasiId)
            ->lockForUpdate()
            ->firstOrFail();

        $dbUpdatedAt = $akreditasi->updated_at->toISOString();

        if ($dbUpdatedAt !== $clientUpdatedAt) {
            throw new ConflictException($akreditasiId, $akreditasi->status);
        }

        return $akreditasi;
    }
}
