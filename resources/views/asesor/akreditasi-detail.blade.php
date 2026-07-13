@extends('layouts.app')

@section('content')
@php
    use App\Models\Akreditasi;
    use App\StateMachine\AkreditasiStateMachine;
    use Illuminate\Support\Facades\Storage;

    $statusVariant = match ((int) $akreditasi->status) {
        0 => 'success',
        -1, -2 => 'danger',
        1 => 'warning',
        2 => 'info',
        3, 4, 5, 6 => 'primary',
        default => 'secondary',
    };

    $ipmItems = [
        'nsp_file' => '1. Izin operasional Kementerian Agama (NSP)',
        'lulus_santri_file' => '2. Pernah meluluskan santri / memiliki santri kelas akhir',
        'kurikulum_file' => '3. Menyelenggarakan kurikulum Dirasah Islamiyah',
        'buku_ajar_file' => '4. Menggunakan buku ajar terbitan LP2 PPM',
    ];

    $dokumenUtama = [
        'status_kepemilikan_tanah' => 'Status Kepemilikan Tanah',
        'sertifikat_nsp' => 'Sertifikat NSP',
        'rk_anggaran' => 'Rencana Kerja Anggaran',
        'silabus_rpp' => 'Silabus dan RPP',
        'peraturan_kepegawaian' => 'Peraturan Kepegawaian',
        'file_lk_iapm' => 'File LK Penilaian IAPM',
        'laporan_tahunan' => 'Laporan Tahunan',
    ];

    $dokumenSekunder = [
        'dok_profil' => 'Dokumen Profil',
        'dok_nsp' => 'Dokumen NSP',
        'dok_renstra' => 'Dokumen Renstra',
        'dok_rk_anggaran' => 'Dokumen RK Anggaran',
        'dok_kurikulum' => 'Dokumen Kurikulum',
        'dok_silabus_rpp' => 'Dokumen Silabus & RPP',
        'dok_kepengasuhan' => 'Dokumen Kepengasuhan',
        'dok_peraturan_kepegawaian' => 'Dokumen Peraturan Kepegawaian',
        'dok_sarpras' => 'Dokumen Sarpras',
        'dok_laporan_tahunan' => 'Dokumen Laporan Tahunan',
        'dok_sop' => 'Dokumen SOP',
    ];

    $tabs = [
        'profil' => 'Profil',
        'ipm' => 'IPM',
        'sdm' => 'SDM',
        'edpm' => 'EDPM',
    ];

    if (in_array((int) $akreditasi->status, [AkreditasiStateMachine::STATUS_PASCA_VISITASI, AkreditasiStateMachine::STATUS_VALIDASI_ADMIN, AkreditasiStateMachine::STATUS_SELESAI, AkreditasiStateMachine::STATUS_DITOLAK], true)) {
        $tabs['instrumen'] = 'Butir Penilaian';
    }

    if (in_array((int) $akreditasi->status, [AkreditasiStateMachine::STATUS_PASCA_VISITASI, AkreditasiStateMachine::STATUS_VALIDASI_ADMIN, AkreditasiStateMachine::STATUS_SELESAI, AkreditasiStateMachine::STATUS_DITOLAK], true)) {
        $tabs['laporan'] = 'Upload Laporan';
    }
@endphp

<x-slot name="header">{{ __('Detail Akreditasi') }}</x-slot>

<x-ui.page
    title="Detail Akreditasi"
    subtitle="{{ $pesantren->nama_pesantren ?? $pesantren->name ?? '-' }}"
    class="spm-detail-page"
    x-data="asesorAkreditasiDetailPage()"
    data-module-page="asesor-akreditasi-detail"
>
    <x-slot:toolbar>
        <x-ui.status-badge :variant="$statusVariant">
            {{ Akreditasi::getStatusLabel($akreditasi->status) }}
        </x-ui.status-badge>

        @if($canConfirmVisitasi)
            <x-ui.button variant="success" x-on:click="confirmVisitasiSelesai()">
                <i class="ki-solid ki-check-circle fs-4 me-1"></i>
                Konfirmasi Visitasi Selesai
            </x-ui.button>
        @endif

        @if((int) $akreditasi->status === AkreditasiStateMachine::STATUS_PASCA_VISITASI && $asesorTipe === 1)
            <x-ui.button variant="primary" x-on:click="confirmFinalizeScoring()">
                <i class="ki-solid ki-verify fs-4 me-1"></i>
                Finalisasi Penilaian
            </x-ui.button>
        @endif

        @if($canSubmitDocumentRejection)
            <x-ui.button variant="warning" x-on:click="$dispatch('open-modal', 'reject-documents-modal')">
                <i class="ki-solid ki-cross-circle fs-4 me-1"></i>
                Tolak Dokumen
            </x-ui.button>
        @endif

        @if($canScheduleVisitasi)
            <x-ui.button variant="info" x-on:click="$dispatch('open-modal', 'schedule-visitasi-modal')">
                <i class="ki-solid ki-calendar-add fs-4 me-1"></i>
                Jadwalkan Visitasi
            </x-ui.button>
        @endif

        <x-ui.button :href="route('asesor.akreditasi')" variant="light">
            <i class="ki-solid ki-arrow-left fs-4 me-1"></i>
            Kembali
        </x-ui.button>
    </x-slot:toolbar>

    {{-- Flash messages --}}
    @if(session('success'))
        <x-ui.alert variant="success" title="Berhasil" class="mb-4">{{ session('success') }}</x-ui.alert>
    @endif
    @if(session('error'))
        <x-ui.alert variant="danger" title="Gagal" class="mb-4">{{ session('error') }}</x-ui.alert>
    @endif

    {{-- Stats Row --}}
    <div class="row g-5 mb-6">
        <div class="col-lg-4">
            <x-ui.stat-card
                label="Status Tugas"
                value="{{ Akreditasi::getStatusLabel($akreditasi->status) }}"
                variant="{{ $statusVariant }}"
                icon="shield-tick"
            />
        </div>
        <div class="col-lg-4">
            <x-ui.stat-card
                label="Jadwal Visitasi"
                value="{{ $akreditasi->tgl_visitasi ? \Carbon\Carbon::parse($akreditasi->tgl_visitasi)->format('d M Y') : 'Belum Dijadwalkan' }}"
                variant="info"
                icon="calendar"
            />
        </div>
        <div class="col-lg-4">
            <x-ui.stat-card
                label="Peran Penilaian"
                value="{{ $asesorTipe === 1 ? 'Ketua (Asesor 1)' : 'Anggota (Asesor 2)' }}"
                variant="success"
                icon="profile-user"
            />
        </div>
    </div>

    {{-- Workflow Stepper --}}
    <x-akreditasi.workflow-stepper
        :status="$akreditasi->status"
        title="Tahapan Akreditasi LP2M"
        subtitle="Pantau posisi pengajuan dari review awal, review asesor, visitasi, penilaian pasca visitasi, validasi admin, sampai hasil akhir."
        class="mb-6"
    />

    {{-- Tabs Navigation --}}
    <div class="spm-detail-tabs-shell">
        <div data-ui-tabs="metronic" class="nav nav-tabs nav-line-tabs mb-6 spm-tabs-nav">
        @foreach($tabs as $key => $label)
            <a class="nav-item nav-link cursor-pointer spm-tab-link"
               :class="{ 'active': activeTab === '{{ $key }}' }"
               x-on:click="activeTab = '{{ $key }}'">
                {{ $label }}
            </a>
        @endforeach
        </div>
    </div>

    {{-- Tab Content --}}
    <div class="spm-detail-tab-content">
    <div x-show="activeTab === 'profil'" x-cloak>
        @include('asesor.akreditasi-detail.tabs.profil')
    </div>
    <div x-show="activeTab === 'ipm'" x-cloak>
        @include('asesor.akreditasi-detail.tabs.ipm')
    </div>
    <div x-show="activeTab === 'sdm'" x-cloak>
        @include('asesor.akreditasi-detail.tabs.sdm')
    </div>
    <div x-show="activeTab === 'edpm'" x-cloak>
        @include('asesor.akreditasi-detail.tabs.edpm')
    </div>
    @if(in_array((int) $akreditasi->status, [AkreditasiStateMachine::STATUS_PASCA_VISITASI, AkreditasiStateMachine::STATUS_VALIDASI_ADMIN, AkreditasiStateMachine::STATUS_SELESAI, AkreditasiStateMachine::STATUS_DITOLAK], true))
        <div x-show="activeTab === 'instrumen'" x-cloak>
            @include('asesor.akreditasi-detail.tabs.instrumen')
        </div>
    @endif
    @if(in_array((int) $akreditasi->status, [AkreditasiStateMachine::STATUS_PASCA_VISITASI, AkreditasiStateMachine::STATUS_VALIDASI_ADMIN, AkreditasiStateMachine::STATUS_SELESAI, AkreditasiStateMachine::STATUS_DITOLAK], true))
    <div x-show="activeTab === 'laporan'" x-cloak>
        @include('asesor.akreditasi-detail.tabs.laporan-visitasi')
    </div>
    @endif
    </div>

    {{-- Rejection Section (Asesor 1 Only) --}}
    @if($asesorTipe === 1 && !empty($rejectionStatus))
        <x-ui.section-card title="Status Penolakan Dokumen" subtitle="Kelola penolakan dan perbaikan dokumen pesantren" class="mt-6">
            <div class="p-6">
                @if($rejectionStatus['status'] === 'pending' || $rejectionStatus['status'] === 'rejected')
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <x-ui.badge variant="warning">
                            Menunggu Perbaikan ({{ $rejectionStatus['rejectionCount'] }}/{{ $rejectionStatus['rejectionLimit'] }})
                        </x-ui.badge>
                        @if($rejectionStatus['updatedAt'])
                            <span class="text-muted fs-8">Terakhir diperbarui: {{ \Carbon\Carbon::parse($rejectionStatus['updatedAt'])->format('d M Y H:i') }}</span>
                        @endif
                    </div>

                    @if($rejectionStatus['status'] === 'corrected')
                        <div class="d-flex gap-3">
                            <form method="POST" action="{{ route('asesor.akreditasi.accept-perbaikan') }}">
                                @csrf
                                <input type="hidden" name="akreditasi_id" value="{{ $akreditasi->id }}">
                                <x-ui.button type="button" variant="success" x-on:click="confirmAcceptPerbaikan($el.closest('form'))">
                                    <i class="ki-solid ki-check fs-4 me-1"></i>
                                    Terima Perbaikan
                                </x-ui.button>
                            </form>
                            @if($rejectionStatus['rejectionCount'] < $rejectionStatus['rejectionLimit'])
                                <x-ui.button variant="danger" x-on:click="$dispatch('open-modal', 'reject-documents-modal')">
                                    <i class="ki-solid ki-cross fs-4 me-1"></i>
                                    Tolak Lagi
                                </x-ui.button>
                            @endif
                        </div>
                    @endif
                @elseif($rejectionStatus['status'] === 'accepted')
                    <x-ui.alert variant="success" title="Perbaikan Diterima">
                        Perbaikan dokumen telah diterima dan disetujui.
                    </x-ui.alert>
                @endif

                {{-- Rejection History --}}
                @if(!empty($rejectionStatus['history']) && count($rejectionStatus['history']) > 0)
                    <div class="mt-5">
                        <h6 class="fw-semibold mb-3">Riwayat Penolakan</h6>
                        <div class="timeline">
                            @foreach($rejectionStatus['history'] as $history)
                                <div class="timeline-item mb-3">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <x-ui.badge :variant="$history->status === 'accepted' ? 'success' : ($history->status === 'corrected' ? 'info' : 'warning')">
                                            {{ ucfirst($history->status) }}
                                        </x-ui.badge>
                                        <span class="text-muted fs-8">{{ $history->created_at->format('d M Y H:i') }}</span>
                                    </div>
                                    @if($history->explanation)
                                        <p class="text-muted fs-7 mb-0">{{ $history->explanation }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </x-ui.section-card>
    @endif

    {{-- Reject Documents Modal --}}
    <x-ui.modal name="reject-documents-modal" title="Tolak Dokumen" maxWidth="lg">
        <form method="POST" action="{{ route('asesor.akreditasi.reject-document') }}" id="rejectDocumentsForm">
            @csrf
            <input type="hidden" name="akreditasi_id" value="{{ $akreditasi->id }}">
            <div class="p-6">
                <p class="text-muted mb-4">Pilih dokumen yang ditolak dan berikan alasan penolakan.</p>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Dokumen yang Ditolak</label>
                    @if(!empty($selectableItems))
                        @foreach($selectableItems as $section)
                            <div class="mb-3">
                                <div class="fw-semibold fs-7 mb-2">{{ $section['label'] ?? '' }}</div>
                                @if(!empty($section['children']))
                                    @foreach($section['children'] as $child)
                                        <div class="form-check mb-1 ms-3">
                                            <input class="form-check-input" type="checkbox" name="perbaikan[]" value="{{ $child['value'] ?? '' }}" id="reject-{{ $loop->parent->index }}-{{ $loop->index }}">
                                            <label class="form-check-label" for="reject-{{ $loop->parent->index }}-{{ $loop->index }}">{{ $child['label'] ?? '' }}</label>
                                        </div>
                                        @if(!empty($child['subChildren']))
                                            @foreach($child['subChildren'] as $sub)
                                                <div class="form-check mb-1 ms-6">
                                                    <input class="form-check-input" type="checkbox" name="perbaikan[]" value="{{ $sub['value'] ?? '' }}" id="reject-{{ $loop->parent->parent->index }}-{{ $loop->parent->index }}-{{ $loop->index }}">
                                                    <label class="form-check-label" for="reject-{{ $loop->parent->parent->index }}-{{ $loop->parent->index }}-{{ $loop->index }}">{{ $sub['label'] ?? '' }}</label>
                                                </div>
                                            @endforeach
                                        @endif
                                    @endforeach
                                @endif
                            </div>
                        @endforeach
                    @endif
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold" for="rejectionExplanation">Catatan Penolakan</label>
                    <textarea class="form-control" name="catatan" id="rejectionExplanation" rows="4" placeholder="Jelaskan alasan penolakan dokumen..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <x-ui.button type="button" variant="light" x-on:click="$dispatch('close-modal', 'reject-documents-modal')">Batal</x-ui.button>
                <x-ui.button type="button" variant="danger" x-on:click="confirmSubmitRejection()">
                    <i class="ki-solid ki-cross-circle fs-4 me-1"></i>
                    Kirim Penolakan
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    {{-- Schedule Visitasi Modal --}}
    <x-ui.modal name="schedule-visitasi-modal" title="Jadwalkan Visitasi" maxWidth="md">
        <form method="POST" action="{{ route('asesor.akreditasi.schedule-visitasi') }}" id="scheduleVisitasiForm">
            @csrf
            <input type="hidden" name="akreditasi_id" value="{{ $akreditasi->id }}">
            <div class="p-6">
                <div class="mb-4">
                    <label class="form-label fw-semibold" for="tanggalMulai">Tanggal Mulai</label>
                    <input type="date" class="form-control" name="tanggal_mulai" id="tanggalMulai" required>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold" for="tanggalAkhir">Tanggal Selesai</label>
                    <input type="date" class="form-control" name="tanggal_akhir" id="tanggalAkhir" required>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold" for="catatanVisitasi">Catatan</label>
                    <textarea class="form-control" name="catatan" id="catatanVisitasi" rows="3" placeholder="Catatan tambahan untuk visitasi..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <x-ui.button type="button" variant="light" x-on:click="$dispatch('close-modal', 'schedule-visitasi-modal')">Batal</x-ui.button>
                <x-ui.button type="submit" variant="info">
                    <i class="ki-solid ki-calendar-add fs-4 me-1"></i>
                    Jadwalkan
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>

</x-ui.page>
@endsection

@push('scripts')
<script>
function asesorAkreditasiDetailPage() {
    return {
        activeTab: '{{ $activeTab }}',
        loading: false,

        confirmVisitasiSelesai() {
            window.SpmSwal.confirm({
                title: 'Konfirmasi Visitasi Selesai?',
                text: 'Pastikan seluruh proses visitasi telah selesai dilaksanakan.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Selesai',
                cancelButtonText: 'Batal',
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submitForm('{{ route("asesor.akreditasi.confirm-visitasi-selesai", $akreditasi->uuid) }}');
                }
            });
        },

        confirmFinalizeScoring() {
            window.SpmSwal.confirm({
                title: 'Finalisasi Penilaian?',
                text: 'Setelah difinalisasi, nilai tidak dapat diubah lagi. Pastikan seluruh penilaian telah lengkap.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Finalisasi',
                cancelButtonText: 'Batal',
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submitForm('{{ route("asesor.akreditasi.finalize-scoring", $akreditasi->uuid) }}');
                }
            });
        },

        confirmAcceptPerbaikan(form) {
            window.SpmSwal.confirm({
                title: 'Terima Perbaikan?',
                text: 'Dokumen perbaikan dari pesantren akan dianggap sudah sesuai.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Terima',
                cancelButtonText: 'Batal',
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        },

        confirmSubmitRejection() {
            const form = document.getElementById('rejectDocumentsForm');
            const checked = form.querySelectorAll('input[name="perbaikan[]"]:checked');
            if (checked.length === 0) {
                window.SpmSwal.warning('Peringatan', 'Pilih minimal satu dokumen yang ditolak.');
                return;
            }
            window.SpmSwal.confirm({
                title: 'Kirim Penolakan?',
                text: `${checked.length} dokumen akan ditolak.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Kirim',
                cancelButtonText: 'Batal',
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        },

        async saveNa(butirId, value, isFinal = false) {
            this.loading = true;
            try {
                const res = await fetch('{{ route("asesor.akreditasi.save-na") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ akreditasi_id: {{ $akreditasi->id }}, butir_id: butirId, value: value, is_final: isFinal })
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Gagal menyimpan');
                if (isFinal) {
                    window.SpmSwal.success('Tersimpan', 'Nilai NA telah dikunci.', { timer: 1500, showConfirmButton: false });
                }
            } catch (e) {
                window.SpmSwal.error('Error', e.message);
            } finally {
                this.loading = false;
            }
        },

        async saveNk(butirId, value, isFinal = false) {
            this.loading = true;
            try {
                const res = await fetch('{{ route("asesor.akreditasi.save-nk") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ akreditasi_id: {{ $akreditasi->id }}, butir_id: butirId, value: value, is_final: isFinal })
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Gagal menyimpan');
                if (isFinal) {
                    window.SpmSwal.success('Tersimpan', 'Nilai NK telah dikunci.', { timer: 1500, showConfirmButton: false });
                }
            } catch (e) {
                window.SpmSwal.error('Error', e.message);
            } finally {
                this.loading = false;
            }
        },

        confirmSaveEdpm(isFinal) {
            const title = isFinal ? 'Finalisasi EDPM?' : 'Simpan EDPM?';
            const text = isFinal ? 'Nilai EDPM akan dikunci dan tidak dapat diubah.' : 'Simpan progress penilaian EDPM saat ini.';
            window.SpmSwal.confirm({
                title: title,
                text: text,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Simpan',
                cancelButtonText: 'Batal',
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById('edpmForm');
                    if (form) {
                        const input = form.querySelector('input[name="is_final"]');
                        if (input) input.value = isFinal ? '1' : '0';
                        form.submit();
                    }
                }
            });
        },

        submitForm(url) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = url;
            form.innerHTML = `
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="akreditasi_id" value="{{ $akreditasi->id }}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    };
}
</script>
@endpush
