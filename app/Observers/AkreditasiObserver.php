<?php

namespace App\Observers;

use App\Models\Akreditasi;
use App\Services\AuditTrailService;

class AkreditasiObserver
{
    public function __construct(
        protected AuditTrailService $auditTrailService
    ) {}

    /**
     * Handle the Akreditasi "updating" event.
     * Logs status changes to the audit trail.
     */
    public function updating(Akreditasi $akreditasi): void
    {
        if ($akreditasi->isDirty('status')) {
            $oldStatus = $akreditasi->getOriginal('status');
            $newStatus = $akreditasi->status;

            $this->auditTrailService->log(
                akreditasiId: $akreditasi->id,
                actionType: 'status_changed',
                oldValue: Akreditasi::getStatusLabel($oldStatus),
                newValue: Akreditasi::getStatusLabel($newStatus),
                metadata: [
                    'old_status_code' => $oldStatus,
                    'new_status_code' => $newStatus,
                ]
            );
        }
    }

    /**
     * Handle the Akreditasi "deleting" event.
     * Logs deletion with status and pesantren context.
     */
    public function deleting(Akreditasi $akreditasi): void
    {
        $pesantrenName = $akreditasi->user?->pesantren?->nama_pesantren ?? 'Unknown';

        $this->auditTrailService->log(
            akreditasiId: $akreditasi->id,
            actionType: 'deleted',
            metadata: [
                'status_at_deletion' => $akreditasi->status,
                'pesantren_name' => $pesantrenName,
            ]
        );
    }
}
