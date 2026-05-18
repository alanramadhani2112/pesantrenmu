<?php

use App\Models\Banding;
use App\Models\User;
use App\Services\BandingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public Banding $banding;

    public $selectedReviewerId = '';

    public $keputusan = '';

    public $showAssignModal = false;

    public $showDecisionModal = false;

    public $decisionType = '';

    public function mount(int $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (! $user->canAccessAdminArea()) {
            abort(403);
        }

        $this->banding = Banding::with([
            'akreditasi.assessments',
            'akreditasi.catatans',
            'akreditasi.user.pesantren',
            'reviewer',
        ])->findOrFail($id);

        // Tenant boundary: only admin (and super admin via Gate::before) may review banding
        Gate::authorize('review', $this->banding);
    }

    public function getAdminUsersProperty()
    {
        return User::where('role_id', 1)->get();
    }

    public function getPreviousBandingsProperty()
    {
        return Banding::where('akreditasi_id', $this->banding->akreditasi_id)
            ->where('id', '!=', $this->banding->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function openAssignModal()
    {
        $this->selectedReviewerId = '';
        $this->showAssignModal = true;
    }

    public function openDecisionModal(string $type)
    {
        $this->decisionType = $type;
        $this->keputusan = '';
        $this->showDecisionModal = true;
    }

    public function assignReviewer()
    {
        Gate::authorize('banding.review');
        Gate::authorize('review', $this->banding);

        if (empty($this->selectedReviewerId)) {
            return;
        }

        $bandingService = app(BandingService::class);
        $result = $bandingService->assignReviewer($this->banding->id, (int) $this->selectedReviewerId);

        if ($result) {
            $this->banding->refresh();
            $this->showAssignModal = false;
            session()->flash('message', 'Reviewer berhasil ditugaskan.');
        }
    }

    public function reassignReviewer()
    {
        Gate::authorize('banding.review');
        Gate::authorize('review', $this->banding);

        if (empty($this->selectedReviewerId)) {
            return;
        }

        $bandingService = app(BandingService::class);
        $result = $bandingService->reassignReviewer($this->banding->id, (int) $this->selectedReviewerId);

        if ($result) {
            $this->banding->refresh();
            $this->showAssignModal = false;
            session()->flash('message', 'Reviewer berhasil diganti.');
        }
    }

    public function acceptBanding()
    {
        Gate::authorize('banding.decide');
        Gate::authorize('review', $this->banding);

        if (mb_strlen($this->keputusan) < 10) {
            $this->addError('keputusan', 'Penjelasan keputusan minimal 10 karakter.');

            return;
        }

        $bandingService = app(BandingService::class);
        $result = $bandingService->acceptBanding($this->banding->id, $this->keputusan);

        if ($result) {
            $this->banding->refresh();
            $this->showDecisionModal = false;
            session()->flash('message', 'Banding diterima. Pengajuan ulang telah dibuat.');
        }
    }

    public function rejectBanding()
    {
        Gate::authorize('banding.decide');
        Gate::authorize('review', $this->banding);

        if (mb_strlen($this->keputusan) < 10) {
            $this->addError('keputusan', 'Penjelasan keputusan minimal 10 karakter.');

            return;
        }

        $bandingService = app(BandingService::class);
        $result = $bandingService->rejectBanding($this->banding->id, $this->keputusan);

        if ($result) {
            $this->banding->refresh();
            $this->showDecisionModal = false;
            session()->flash('message', 'Banding ditolak.');
        }
    }

    public function submitDecision()
    {
        if ($this->decisionType === 'accept') {
            $this->acceptBanding();
        } else {
            $this->rejectBanding();
        }
    }
}; ?>

<div>
    <x-slot name="header">{{ __('Detail Banding') }}</x-slot>

    <div class="container-fluid py-4">
        {{-- Flash message --}}
        @if (session()->has('message'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                {{ session('message') }}
                <x-ui.button unstyled type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></x-ui.button>
            </div>
        @endif

        {{-- Header Section --}}
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="fw-bold mb-1">Banding #{{ $banding->id }}</h3>
                        <span class="text-gray-600">Diajukan: {{ $banding->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div>
                        @php
                            $statusVariant = match ($banding->status) {
                                'pending' => 'warning',
                                'under_review' => 'info',
                                'accepted' => 'success',
                                'rejected' => 'danger',
                                default => 'light',
                            };
                            $statusLabel = match ($banding->status) {
                                'pending' => 'Pending',
                                'under_review' => 'Under Review',
                                'accepted' => 'Accepted',
                                'rejected' => 'Rejected',
                                default => $banding->status,
                            };
                        @endphp
                        <span class="badge badge-light-{{ $statusVariant }} fs-6 px-4 py-2">{{ $statusLabel }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Banding Reason --}}
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-title mb-0">Alasan Banding</h4>
            </div>
            <div class="card-body">
                <p class="text-gray-800 fs-6">{{ $banding->alasan }}</p>
            </div>
        </div>

        {{-- Akreditasi Info --}}
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-title mb-0">Informasi Akreditasi</h4>
            </div>
            <div class="card-body">
                @if($banding->akreditasi)
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="text-gray-600 fw-semibold w-50">Pesantren</td>
                                    <td>{{ $banding->akreditasi->user->pesantren->nama_pesantren ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-gray-600 fw-semibold">Status Akreditasi</td>
                                    <td>{{ \App\Models\Akreditasi::getStatusLabel($banding->akreditasi->status) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-gray-600 fw-semibold">Nilai</td>
                                    <td>{{ $banding->akreditasi->nilai ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-gray-600 fw-semibold">Peringkat</td>
                                    <td>{{ $banding->akreditasi->peringkat ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            @if($banding->akreditasi->assessments->count() > 0)
                                <h6 class="fw-bold mb-2">Assessment Scores</h6>
                                @foreach($banding->akreditasi->assessments as $assessment)
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-gray-600">Assessment Tipe {{ $assessment->tipe }}</span>
                                        <span class="fw-bold">{{ $assessment->nilai ?? '-' }}</span>
                                    </div>
                                @endforeach
                            @else
                                <p class="text-gray-500">Belum ada data assessment.</p>
                            @endif
                        </div>
                    </div>
                @else
                    <p class="text-gray-500">Data akreditasi tidak ditemukan.</p>
                @endif
            </div>
        </div>

        {{-- Previous Banding Decisions (Timeline) --}}
        @if($this->previousBandings->count() > 0)
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-title mb-0">Riwayat Banding Sebelumnya</h4>
            </div>
            <div class="card-body">
                <div class="timeline">
                    @foreach($this->previousBandings as $prevBanding)
                        <div class="timeline-item mb-3 pb-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="fw-bold">Banding #{{ $prevBanding->id }}</span>
                                    <span class="text-gray-500 ms-2">{{ $prevBanding->created_at->format('d/m/Y') }}</span>
                                </div>
                                @php
                                    $prevStatusVariant = match ($prevBanding->status) {
                                        'pending' => 'warning',
                                        'under_review' => 'info',
                                        'accepted' => 'success',
                                        'rejected' => 'danger',
                                        default => 'light',
                                    };
                                @endphp
                                <span class="badge badge-light-{{ $prevStatusVariant }}">{{ ucfirst($prevBanding->status) }}</span>
                            </div>
                            @if($prevBanding->keputusan)
                                <p class="text-gray-700 mt-2 mb-0">{{ $prevBanding->keputusan }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- Keputusan (if decided) --}}
        @if($banding->keputusan)
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-title mb-0">Keputusan</h4>
            </div>
            <div class="card-body">
                <p class="text-gray-800 fs-6">{{ $banding->keputusan }}</p>
                @if($banding->decided_at)
                    <small class="text-gray-500">Diputuskan pada: {{ $banding->decided_at->format('d/m/Y H:i') }}</small>
                @endif
            </div>
        </div>
        @endif

        {{-- Actions Section --}}
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-title mb-0">Tindakan</h4>
            </div>
            <div class="card-body">
                {{-- Assign Reviewer (when status=pending) --}}
                @if($banding->status === 'pending')
                    <button wire:click="openAssignModal" class="btn btn-primary">
                        <i class="bi bi-person-plus me-1"></i> Assign Reviewer
                    </button>
                @endif

                {{-- Accept/Reject + Reassign (when status=under_review) --}}
                @if($banding->status === 'under_review')
                    @if(Auth::id() === $banding->reviewer_id)
                        <button wire:click="openDecisionModal('accept')" class="btn btn-success me-2">
                            <i class="bi bi-check-circle me-1"></i> Accept
                        </button>
                        <button wire:click="openDecisionModal('reject')" class="btn btn-danger me-2">
                            <i class="bi bi-x-circle me-1"></i> Reject
                        </button>
                    @endif

                    <button wire:click="openAssignModal" class="btn btn-warning">
                        <i class="bi bi-arrow-repeat me-1"></i> Reassign Reviewer
                    </button>
                @endif

                @if($banding->status === 'accepted' || $banding->status === 'rejected')
                    <p class="text-gray-500 mb-0">Banding ini sudah diputuskan.</p>
                @endif
            </div>
        </div>

        {{-- Assign Reviewer Modal --}}
        @if($showAssignModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            {{ $banding->status === 'pending' ? 'Assign Reviewer' : 'Reassign Reviewer' }}
                        </h5>
                        <x-ui.button unstyled type="button" class="btn-close" wire:click="$set('showAssignModal', false)" aria-label="Tutup"></x-ui.button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Pilih Reviewer</label>
                            <select wire:model="selectedReviewerId" class="form-select">
                                <option value="">-- Pilih Admin --</option>
                                @foreach($this->adminUsers as $admin)
                                    <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <x-ui.button type="button" variant="light" wire:click="$set('showAssignModal', false)">Batal</x-ui.button>
                        @if($banding->status === 'pending')
                            <x-ui.button type="button" variant="primary" wire:click="assignReviewer">Assign</x-ui.button>
                        @else
                            <x-ui.button type="button" variant="warning" wire:click="reassignReviewer">Reassign</x-ui.button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Decision Modal --}}
        @if($showDecisionModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            {{ $decisionType === 'accept' ? 'Terima Banding' : 'Tolak Banding' }}
                        </h5>
                        <x-ui.button unstyled type="button" class="btn-close" wire:click="$set('showDecisionModal', false)" aria-label="Tutup"></x-ui.button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Penjelasan Keputusan</label>
                            <textarea wire:model="keputusan" class="form-control" rows="4" placeholder="Minimal 10 karakter..."></textarea>
                            @error('keputusan')
                                <span class="text-danger fs-7">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <x-ui.button type="button" variant="light" wire:click="$set('showDecisionModal', false)">Batal</x-ui.button>
                        <x-ui.button type="button" :variant="$decisionType === 'accept' ? 'success' : 'danger'" wire:click="submitDecision">
                            {{ $decisionType === 'accept' ? 'Terima' : 'Tolak' }}
                        </x-ui.button>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
