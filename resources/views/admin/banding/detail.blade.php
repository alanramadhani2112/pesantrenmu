@extends('layouts.app')

@section('content')
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

<div x-data="{ showAssignModal: false, showDecisionModal: false, decisionType: '', assignAction: '' }">
    <x-ui.page
        title="Detail Banding #{{ $banding->id }}"
        subtitle="Diajukan: {{ $banding->created_at->format('d/m/Y H:i') }}"
    >
        <x-slot:toolbar>
            <x-ui.status-badge :variant="$statusVariant">{{ $statusLabel }}</x-ui.status-badge>
            <x-ui.button :href="route('admin.banding')" variant="light">
                <x-ui.icon name="exit-right" class="fs-4 me-1" />
                Kembali
            </x-ui.button>
        </x-slot:toolbar>

        <div class="row g-5">
            {{-- Left column: Banding info + Actions --}}
            <div class="col-xl-4">
                <div class="d-flex flex-column gap-5">

                    {{-- Alasan Banding --}}
                    <x-ui.section-card title="Alasan Banding">
                        <div class="p-5">
                            <p class="text-gray-800 fs-6 mb-0">{{ $banding->alasan }}</p>
                        </div>
                    </x-ui.section-card>

                    {{-- Keputusan (if decided) --}}
                    @if($banding->keputusan)
                        <x-ui.section-card title="Keputusan">
                            <div class="p-5">
                                <p class="text-gray-800 fs-6 mb-2">{{ $banding->keputusan }}</p>
                                @if($banding->decided_at)
                                    <div class="text-muted fs-8">Diputuskan: {{ $banding->decided_at->format('d/m/Y H:i') }}</div>
                                @endif
                            </div>
                        </x-ui.section-card>
                    @endif

                    {{-- Actions --}}
                    <x-ui.section-card title="Tindakan">
                        <div class="p-5">
                            @if($banding->status === 'pending')
                                <x-ui.button type="button" @click="assignAction = 'assign'; showAssignModal = true" variant="primary" class="w-100 justify-content-center mb-3">
                                    <x-ui.icon name="profile-user" class="fs-4 me-2" />
                                    Assign Reviewer
                                </x-ui.button>
                            @endif

                            @if($banding->status === 'under_review')
                                @if(Auth::id() === $banding->reviewer_id)
                                    <x-ui.button type="button" @click="decisionType = 'accept'; showDecisionModal = true" variant="success" class="w-100 justify-content-center mb-3">
                                        <x-ui.icon name="check-circle" class="fs-4 me-2" />
                                        Terima Banding
                                    </x-ui.button>
                                    <x-ui.button type="button" @click="decisionType = 'reject'; showDecisionModal = true" variant="danger" class="w-100 justify-content-center mb-3">
                                        <x-ui.icon name="cross-circle" class="fs-4 me-2" />
                                        Tolak Banding
                                    </x-ui.button>
                                @endif
                                <x-ui.button type="button" @click="assignAction = 'reassign'; showAssignModal = true" variant="warning" class="w-100 justify-content-center">
                                    <x-ui.icon name="arrows-circle" class="fs-4 me-2" />
                                    Ganti Reviewer
                                </x-ui.button>
                            @endif

                            @if($banding->status === 'accepted' || $banding->status === 'rejected')
                                <div class="text-muted fw-semibold fs-7 text-center">Banding ini sudah diputuskan.</div>
                            @endif
                        </div>
                    </x-ui.section-card>

                    {{-- Assign/Reassign Reviewer Modal --}}
                    <div x-show="showAssignModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
                        <div class="min-h-screen flex items-center justify-center p-4">
                            <div class="fixed inset-0 bg-black/50" @click="showAssignModal = false"></div>
                            <div class="modal-content bg-white rounded p-6 position-relative w-100 mw-450px">
                                <h3 class="mb-4" x-text="assignAction === 'assign' ? 'Pilih Reviewer' : 'Pilih Reviewer Baru'"></h3>
                                <form method="POST" :action="assignAction === 'assign' ? '{{ route('banding.assign-reviewer', $banding->id) }}' : '{{ route('banding.reassign-reviewer', $banding->id) }}'">
                                    @csrf
                                    <select name="selectedReviewerId" class="form-select mb-4" required>
                                        <option value="">-- Pilih Reviewer --</option>
                                        @foreach($adminUsers as $admin)
                                            <option value="{{ $admin->id }}">{{ $admin->name }} ({{ $admin->email }})</option>
                                        @endforeach
                                    </select>
                                    <div class="d-flex gap-2 justify-content-end">
                                        <button type="button" class="btn btn-light" @click="showAssignModal = false">Batal</button>
                                        <button type="submit" class="btn btn-primary" x-text="assignAction === 'assign' ? 'Assign' : 'Ganti'"></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- Decision Modal --}}
                    <div x-show="showDecisionModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
                        <div class="min-h-screen flex items-center justify-center p-4">
                            <div class="fixed inset-0 bg-black/50" @click="showDecisionModal = false"></div>
                            <div class="modal-content bg-white rounded p-6 position-relative w-100 mw-450px">
                                <h3 class="mb-4" x-text="decisionType === 'accept' ? 'Terima Banding' : 'Tolak Banding'"></h3>
                                <form method="POST" action="{{ route('banding.submit-decision', $banding->id) }}">
                                    @csrf
                                    <input type="hidden" name="decisionType" x-bind:value="decisionType">
                                    <textarea name="keputusan" class="form-control mb-4" rows="4" required minlength="10"
                                        placeholder="Tulis alasan keputusan..."></textarea>
                                    <div class="d-flex gap-2 justify-content-end">
                                        <button type="button" class="btn btn-light" @click="showDecisionModal = false">Batal</button>
                                        <button type="submit" class="btn" :class="decisionType === 'accept' ? 'btn-success' : 'btn-danger'"
                                            x-text="decisionType === 'accept' ? 'Terima' : 'Tolak'"></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- Reviewer info --}}
                    @if($banding->reviewer)
                        <x-ui.section-card title="Reviewer">
                    <div class="p-5">
                                <div class="fw-semibold text-gray-900">{{ $banding->reviewer->name }}</div>
                                <div class="text-muted fs-7">{{ $banding->reviewer->email }}</div>
                            </div>
                        </x-ui.section-card>
                    @endif
                </div>
            </div>

            {{-- Right column: Akreditasi info + History --}}
            <div class="col-xl-8">
                <div class="d-flex flex-column gap-5">

                    {{-- Akreditasi Info --}}
                    @if($banding->akreditasi)
                        <x-ui.section-card title="Informasi Akreditasi" subtitle="Data akreditasi yang menjadi dasar pengajuan banding.">
                        <div class="p-5">
                                <div class="row g-5">
                                    <x-ui.detail-item label="Pesantren" value="{{ $banding->akreditasi->user->pesantren->nama_pesantren ?? '-' }}" />
                                    <x-ui.detail-item label="Status Akreditasi" value="{{ \App\Models\Akreditasi::getStatusLabel($banding->akreditasi->status) }}" />
                                    <x-ui.detail-item label="Nilai" value="{{ $banding->akreditasi->nilai ?? '-' }}" />
                                    <x-ui.detail-item label="Peringkat" value="{{ $banding->akreditasi->peringkat ?? '-' }}" />
                                </div>

                                @if($banding->akreditasi->assessments->count() > 0)
                                    <div class="separator my-5"></div>
                                    <div class="spm-detail-label mb-3">Penugasan Asesor</div>
                                    @foreach($banding->akreditasi->assessments as $assessment)
                                        <div class="d-flex justify-content-between py-2 border-bottom border-dashed">
                                            <span class="text-gray-600 fw-semibold">Tipe {{ $assessment->tipe }}</span>
                                            <span class="fw-semibold">{{ $assessment->asesor?->nama_dengan_gelar ?? '-' }}</span>
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
                    @if($previousBandings->count() > 0)
                        <x-ui.section-card title="Riwayat Banding Sebelumnya" subtitle="Banding lain untuk akreditasi yang sama.">
                        <div class="p-5">
                                <div class="d-flex flex-column gap-4">
                                    @foreach($previousBandings as $prevBanding)
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
                                                <div class="fw-semibold">Banding #{{ $prevBanding->id }}</div>
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
    <x-ui.modal name="assign-reviewer-modal" :show="false" focusable>
        <form method="POST" action="{{ $banding->status === 'pending' ? route('admin.banding.assign-reviewer', $banding->id) : route('admin.banding.reassign-reviewer', $banding->id) }}">
            @csrf
            <x-ui.modal-header
                :title="$banding->status === 'pending' ? 'Tunjuk Peninjau' : 'Ganti Peninjau'"
                subtitle="Pilih admin yang akan menangani banding ini."
                icon="profile-user"
            />
            <x-ui.modal-body>
                <x-ui.form-field label="Pilih Peninjau" for="selectedReviewerId">
                    <select name="selectedReviewerId" id="selectedReviewerId" class="form-select form-select-solid" >
                        <option value="">-- Pilih Admin --</option>
                        @foreach($adminUsers as $admin)
                            <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                        @endforeach
                    </select>
                </x-ui.form-field>
            </x-ui.modal-body>
            <x-ui.modal-footer>
                <x-ui.button type="button" variant="light" x-on:click="$dispatch('close')">Batal</x-ui.button>
                @if($banding->status === 'pending')
                    <x-ui.button type="submit" variant="primary">Tugaskan</x-ui.button>
                @else
                    <x-ui.button type="submit" variant="warning">Ganti</x-ui.button>
                @endif
            </x-ui.modal-footer>
        </form>
    </x-ui.modal>

    {{-- Decision Modal --}}
    <x-ui.modal name="decision-modal" :show="false" focusable>
        <form method="POST" action="{{ route('admin.banding.submit-decision', $banding->id) }}">
            @csrf
            <input type="hidden" name="decisionType" id="decisionTypeHidden" value="">
            <x-ui.modal-header
                :title="'Terima Banding'"
                subtitle="Berikan penjelasan keputusan Anda."
                icon="check-circle"
                variant="success"
            />
            <x-ui.modal-body>
                <x-ui.form-field label="Penjelasan Keputusan" for="keputusan" hint="Minimal 10 karakter.">
                    <x-ui.textarea id="keputusan" name="keputusan" rows="4" placeholder="Jelaskan alasan keputusan Anda..." />
                </x-ui.form-field>
            </x-ui.modal-body>
            <x-ui.modal-footer>
                <x-ui.button type="button" variant="light" x-on:click="$dispatch('close')">Batal</x-ui.button>
                <x-ui.button type="submit" variant="success" id="decisionSubmitBtn">Terima</x-ui.button>
            </x-ui.modal-footer>
        </form>
    </x-ui.modal>
</div>
@endsection
