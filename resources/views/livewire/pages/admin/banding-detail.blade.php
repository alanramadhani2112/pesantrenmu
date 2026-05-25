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
            $this->dispatch('notification-received', type: 'error', title: 'Gagal', message: 'Pilih peninjau terlebih dahulu.');
            return;
        }

        $bandingService = app(BandingService::class);
        $result = $bandingService->assignReviewer($this->banding->id, (int) $this->selectedReviewerId);

        if ($result) {
            $this->banding->refresh();
            $this->showAssignModal = false;
            $this->selectedReviewerId = '';
            $this->dispatch('notification-received', type: 'success', title: 'Berhasil!', message: 'Peninjau berhasil ditugaskan.');
        } else {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal', message: 'Gagal menugaskan peninjau.');
        }
    }

    public function reassignReviewer()
    {
        Gate::authorize('banding.review');
        Gate::authorize('review', $this->banding);

        if (empty($this->selectedReviewerId)) {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal', message: 'Pilih peninjau terlebih dahulu.');
            return;
        }

        $bandingService = app(BandingService::class);
        $result = $bandingService->reassignReviewer($this->banding->id, (int) $this->selectedReviewerId);

        if ($result) {
            $this->banding->refresh();
            $this->showAssignModal = false;
            $this->selectedReviewerId = '';
            $this->dispatch('notification-received', type: 'success', title: 'Berhasil!', message: 'Peninjau berhasil diganti.');
        } else {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal', message: 'Gagal mengganti peninjau.');
        }
    }

    public function acceptBanding(): void
    {
        Gate::authorize('banding.decide');
        Gate::authorize('review', $this->banding);

        if (mb_strlen(trim($this->keputusan)) < 10) {
            $this->addError('keputusan', 'Penjelasan keputusan minimal 10 karakter.');
            return;
        }

        try {
            $workflowService = app(\App\Services\AkreditasiWorkflowService::class);
            $workflowService->decideBanding($this->banding->id, Auth::id(), 'diterima');
            $this->banding->refresh();
            $this->showDecisionModal = false;
            $this->keputusan = '';
            $this->dispatch('notification-received', type: 'success', title: 'Berhasil!', message: 'Banding diterima. Akreditasi kembali ke tahap Validasi Akhir Admin.');
        } catch (\DomainException $e) {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal', message: $e->getMessage());
        }
    }

    public function rejectBanding(): void
    {
        Gate::authorize('banding.decide');
        Gate::authorize('review', $this->banding);

        if (mb_strlen(trim($this->keputusan)) < 10) {
            $this->addError('keputusan', 'Penjelasan keputusan minimal 10 karakter.');
            return;
        }

        try {
            $workflowService = app(\App\Services\AkreditasiWorkflowService::class);
            $workflowService->decideBanding($this->banding->id, Auth::id(), 'ditolak');
            $this->banding->refresh();
            $this->showDecisionModal = false;
            $this->keputusan = '';
            $this->dispatch('notification-received', type: 'success', title: 'Berhasil!', message: 'Banding ditolak.');
        } catch (\DomainException $e) {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal', message: $e->getMessage());
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

<div x-data="{ ...adminManagement() }">
    <x-slot name="header">{{ __('Detail Banding') }}</x-slot>

    <x-ui.page
        title="Detail Banding #{{ $banding->id }}"
        subtitle="Diajukan: {{ $banding->created_at->format('d/m/Y H:i') }}"
    >
        <x-slot:toolbar>
            @php
                $statusVariant = match ($banding->status) {
                    'pending' => 'warning',
                    'under_review' => 'info',
                    'accepted' => 'success',
                    'rejected' => 'danger',
                    default => 'light',
                };
                $statusLabel = match ($banding->status) {
                    'pending' => 'Tertunda',
                    'under_review' => 'Dalam Peninjauan',
                    'accepted' => 'Diterima',
                    'rejected' => 'Ditolak',
                    default => $banding->status,
                };
            @endphp
            <x-ui.status-badge :variant="$statusVariant">{{ $statusLabel }}</x-ui.status-badge>
            <x-ui.button :href="route('admin.banding')" variant="light">
                <x-ui.icon name="exit-right" class="fs-4 me-1" />
                Kembali
            </x-ui.button>
        </x-slot:toolbar>

        <div class="row g-6">
            {{-- Left column: Banding info + Actions --}}
            <div class="col-xl-4">
                <div class="d-flex flex-column gap-6">

                    {{-- Alasan Banding --}}
                    <x-ui.section-card title="Alasan Banding">
                        <div class="p-6">
                            <p class="text-gray-800 fs-6 mb-0">{{ $banding->alasan }}</p>
                        </div>
                    </x-ui.section-card>

                    {{-- Keputusan (if decided) --}}
                    @if($banding->keputusan)
                        <x-ui.section-card title="Keputusan">
                            <div class="p-6">
                                <p class="text-gray-800 fs-6 mb-2">{{ $banding->keputusan }}</p>
                                @if($banding->decided_at)
                                    <div class="text-muted fs-8">Diputuskan: {{ $banding->decided_at->format('d/m/Y H:i') }}</div>
                                @endif
                            </div>
                        </x-ui.section-card>
                    @endif

                    {{-- Actions --}}
                    <x-ui.section-card title="Tindakan">
                        <div class="p-6">
                            @if($banding->status === 'pending')
                                <x-ui.button type="button" @click="confirmAssignReviewer($wire)" variant="primary" class="w-100 justify-content-center mb-3">
                                    <x-ui.icon name="profile-user" class="fs-4 me-2" />
                                    Assign Reviewer
                                </x-ui.button>
                            @endif

                            @if($banding->status === 'under_review')
                                @if(Auth::id() === $banding->reviewer_id)
                                    <x-ui.button type="button" @click="confirmBandingDecision($wire, 'accept')" variant="success" class="w-100 justify-content-center mb-3">
                                        <x-ui.icon name="check-circle" class="fs-4 me-2" />
                                        Terima Banding
                                    </x-ui.button>
                                    <x-ui.button type="button" @click="confirmBandingDecision($wire, 'reject')" variant="danger" class="w-100 justify-content-center mb-3">
                                        <x-ui.icon name="cross-circle" class="fs-4 me-2" />
                                        Tolak Banding
                                    </x-ui.button>
                                @endif
                                <x-ui.button type="button" @click="confirmReassignReviewer($wire)" variant="warning" class="w-100 justify-content-center">
                                    <x-ui.icon name="arrows-circle" class="fs-4 me-2" />
                                    Ganti Reviewer
                                </x-ui.button>
                            @endif

                            @if($banding->status === 'accepted' || $banding->status === 'rejected')
                                <div class="text-muted fw-semibold fs-7 text-center">Banding ini sudah diputuskan.</div>
                            @endif
                        </div>
                    </x-ui.section-card>

                    {{-- Reviewer info --}}
                    @if($banding->reviewer)
                        <x-ui.section-card title="Reviewer">
                            <div class="p-6">
                                <div class="fw-bold text-gray-900">{{ $banding->reviewer->name }}</div>
                                <div class="text-muted fs-7">{{ $banding->reviewer->email }}</div>
                            </div>
                        </x-ui.section-card>
                    @endif
                </div>
            </div>

            {{-- Right column: Akreditasi info + History --}}
            <div class="col-xl-8">
                <div class="d-flex flex-column gap-6">

                    {{-- Akreditasi Info --}}
                    @if($banding->akreditasi)
                        <x-ui.section-card title="Informasi Akreditasi" subtitle="Data akreditasi yang menjadi dasar pengajuan banding.">
                            <div class="p-6">
                                <div class="row g-5">
                                    <x-ui.detail-item label="Pesantren" value="{{ $banding->akreditasi->user->pesantren->nama_pesantren ?? '-' }}" />
                                    <x-ui.detail-item label="Status Akreditasi" value="{{ \App\Models\Akreditasi::getStatusLabel($banding->akreditasi->status) }}" />
                                    <x-ui.detail-item label="Nilai" value="{{ $banding->akreditasi->nilai ?? '-' }}" />
                                    <x-ui.detail-item label="Peringkat" value="{{ $banding->akreditasi->peringkat ?? '-' }}" />
                                </div>

                                @if($banding->akreditasi->assessments->count() > 0)
                                    <div class="separator my-5"></div>
                                    <div class="spm-detail-label mb-3">Assessment</div>
                                    @foreach($banding->akreditasi->assessments as $assessment)
                                        <div class="d-flex justify-content-between py-2 border-bottom border-dashed">
                                            <span class="text-gray-600 fw-semibold">Tipe {{ $assessment->tipe }}</span>
                                            <span class="fw-bold">{{ $assessment->nilai ?? '-' }}</span>
                                        </div>
                                    @endforeach
                                @endif

                                <div class="mt-4">
                                    <x-ui.button :href="route('admin.akreditasi-detail', $banding->akreditasi->uuid)" variant="light" size="sm">
                                        <x-ui.icon name="eye" class="fs-5 me-1" />
                                        Lihat Detail Akreditasi
                                    </x-ui.button>
                                </div>
                            </div>
                        </x-ui.section-card>
                    @endif

                    {{-- Previous Banding History --}}
                    @if($this->previousBandings->count() > 0)
                        <x-ui.section-card title="Riwayat Banding Sebelumnya" subtitle="Banding lain untuk akreditasi yang sama.">
                            <div class="p-6">
                                <div class="d-flex flex-column gap-4">
                                    @foreach($this->previousBandings as $prevBanding)
                                        @php
                                            $prevVariant = match ($prevBanding->status) {
                                                'pending' => 'warning',
                                                'under_review' => 'info',
                                                'accepted' => 'success',
                                                'rejected' => 'danger',
                                                default => 'light',
                                            };
                                        @endphp
                                        <div class="spm-soft-panel">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <div class="fw-bold">Banding #{{ $prevBanding->id }}</div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <x-ui.badge :variant="$prevVariant">{{ ucfirst($prevBanding->status) }}</x-ui.badge>
                                                    <span class="text-muted fs-8">{{ $prevBanding->created_at->format('d/m/Y') }}</span>
                                                </div>
                                            </div>
                                            @if($prevBanding->keputusan)
                                                <div class="text-gray-700 fs-7">{{ $prevBanding->keputusan }}</div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </x-ui.section-card>
                    @endif
                </div>
            </div>
        </div>
    </x-ui.page>

    {{-- Assign/Reassign Reviewer Modal --}}
    @if($showAssignModal)
    <x-ui.modal name="assign-reviewer-modal" :show="true" focusable>
        <x-ui.modal-header
            :title="$banding->status === 'pending' ? 'Tunjuk Peninjau' : 'Ganti Peninjau'"
            subtitle="Pilih admin yang akan menangani banding ini."
            icon="profile-user"
        />
        <x-ui.modal-body>
            <x-ui.form-field label="Pilih Peninjau" for="selectedReviewerId">
                <x-ui.select model="selectedReviewerId" id="selectedReviewerId" placeholder="-- Pilih Admin --">
                    @foreach($this->adminUsers as $admin)
                        <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                    @endforeach
                </x-ui.select>
            </x-ui.form-field>
        </x-ui.modal-body>
        <x-ui.modal-footer>
            <x-ui.button type="button" variant="light" wire:click="$set('showAssignModal', false)">Batal</x-ui.button>
            @if($banding->status === 'pending')
                <x-ui.button type="button" variant="primary" wire:click="assignReviewer">Tugaskan</x-ui.button>
            @else
                <x-ui.button type="button" variant="warning" wire:click="reassignReviewer">Ganti</x-ui.button>
            @endif
        </x-ui.modal-footer>
    </x-ui.modal>
    @endif

    {{-- Decision Modal --}}
    @if($showDecisionModal)
    <x-ui.modal name="decision-modal" :show="true" focusable>
        <x-ui.modal-header
            :title="$decisionType === 'accept' ? 'Terima Banding' : 'Tolak Banding'"
            subtitle="Berikan penjelasan keputusan Anda."
            :icon="$decisionType === 'accept' ? 'check-circle' : 'cross-circle'"
            :variant="$decisionType === 'accept' ? 'success' : 'danger'"
        />
        <x-ui.modal-body>
            <x-ui.form-field label="Penjelasan Keputusan" for="keputusan" :error="$errors->get('keputusan')" hint="Minimal 10 karakter.">
                <x-ui.textarea model="keputusan" id="keputusan" rows="4" placeholder="Jelaskan alasan keputusan Anda..." />
            </x-ui.form-field>
        </x-ui.modal-body>
        <x-ui.modal-footer>
            <x-ui.button type="button" variant="light" wire:click="$set('showDecisionModal', false)">Batal</x-ui.button>
            <x-ui.button type="button" :variant="$decisionType === 'accept' ? 'success' : 'danger'" wire:click="submitDecision">
                {{ $decisionType === 'accept' ? 'Terima' : 'Tolak' }}
            </x-ui.button>
        </x-ui.modal-footer>
    </x-ui.modal>
    @endif
</div>
