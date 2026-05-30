<?php

namespace App\Livewire\Pages\Admin;

use App\Services\AuditTrailService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class AuditTimeline extends Component
{
    use WithPagination;

    public int $akreditasiId;

    public string $filterActionType = '';

    public string $filterUserId = '';

    public string $filterDateFrom = '';

    public string $filterDateTo = '';

    /**
     * Reset pagination when any filter changes.
     */
    public function updatedFilterActionType(): void
    {
        $this->resetPage();
    }

    public function updatedFilterUserId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedFilterDateTo(): void
    {
        $this->resetPage();
    }

    /**
     * Reset all filters to their defaults.
     */
    public function resetFilters(): void
    {
        $this->filterActionType = '';
        $this->filterUserId = '';
        $this->filterDateFrom = '';
        $this->filterDateTo = '';
        $this->resetPage();
    }

    /**
     * Get the list of actors (users) who have audit logs for this akreditasi.
     */
    public function getActorsProperty(): Collection
    {
        return DB::table('akreditasi_audit_logs')
            ->join('users', 'akreditasi_audit_logs.user_id', '=', 'users.id')
            ->where('akreditasi_audit_logs.akreditasi_id', $this->akreditasiId)
            ->select('users.id', 'users.name')
            ->distinct()
            ->orderBy('users.name')
            ->get();
    }

    public function render()
    {
        $service = app(AuditTrailService::class);

        $filters = [];

        if ($this->filterActionType !== '') {
            $filters['action_type'] = $this->filterActionType;
        }

        if ($this->filterUserId !== '') {
            $filters['user_id'] = (int) $this->filterUserId;
        }

        if ($this->filterDateFrom !== '') {
            $filters['date_from'] = $this->filterDateFrom;
        }

        if ($this->filterDateTo !== '') {
            $filters['date_to'] = $this->filterDateTo;
        }

        $logs = $service->getTimeline($this->akreditasiId, $filters, 15);

        return view('livewire.pages.admin.audit-timeline', [
            'logs' => $logs,
            'actionTypes' => AuditTrailService::ALLOWED_ACTION_TYPES,
            'actors' => $this->actors,
        ]);
    }
}
