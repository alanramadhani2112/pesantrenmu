@extends('layouts.app')

@section('content')
@php
    use App\Models\Akreditasi;
    use App\Support\AkreditasiStatusPresenter;
    use App\StateMachine\AkreditasiStateMachine;
    use Illuminate\Support\Facades\Storage;

    $status = AkreditasiStatusPresenter::for($akreditasi->status);

    $canShowAdminScoring = in_array((int) $akreditasi->status, [
        AkreditasiStateMachine::STATUS_PASCA_VISITASI,
        AkreditasiStateMachine::STATUS_VALIDASI_ADMIN,
        AkreditasiStateMachine::STATUS_SELESAI,
    ], true);

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

    $ipmItems = [
        'nsp_file' => '1. Izin operasional Kementerian Agama (NSP)',
        'lulus_santri_file' => '2. Pernah meluluskan santri / memiliki santri kelas akhir',
        'kurikulum_file' => '3. Menyelenggarakan kurikulum Dirasah Islamiyah',
        'buku_ajar_file' => '4. Menggunakan buku ajar terbitan LP2 PPM',
    ];
@endphp

<x-ui.page
    title="Detail Akreditasi"
    subtitle="{{ $pesantren?->nama_pesantren ?? $akreditasi->user->name }}"
    class="spm-detail-page"
    x-data="{ activeTab: '{{ $activeTab }}' }"
>
    <x-akreditasi.presence-indicator :akreditasi-id="$akreditasi->id" />
    <x-slot:toolbar>
        <x-ui.status-badge :variant="$status['variant']">
            {{ $status['label'] }}
        </x-ui.status-badge>

        @if($isOverdue)
            <x-ui.badge variant="danger">
                <x-ui.icon name="warning-2" class="fs-6 me-1" />
                Terlambat
            </x-ui.badge>
        @endif

        @if(in_array($akreditasi->status, [AkreditasiStateMachine::STATUS_ASSESSMENT, AkreditasiStateMachine::STATUS_VISITASI]))
            <x-ui.button
                type="button"
                @click="$dispatch('open-modal', 'reassign-asesor-modal')"
                :variant="$isOverdue ? 'danger' : 'light'"
                :disabled="!$isOverdue"
                size="sm"
                data-testid="reassign-asesor-btn"
            >
                <x-ui.icon name="arrows-circle" class="fs-4 me-1" />
                Ganti Asesor
            </x-ui.button>
        @endif

        @if((int)$akreditasi->status === AkreditasiStateMachine::STATUS_PENGAJUAN)
            <form method="POST" action="{{ route('admin.akreditasi-detail.open-for-review', $akreditasi->uuid) }}" class="d-inline">
                @csrf
                <x-ui.button type="submit" variant="primary" size="sm">
                    <x-ui.icon name="eye" class="fs-4 me-1" />
                    Buka untuk Review
                </x-ui.button>
            </form>
        @endif

        @if((int)$akreditasi->status === AkreditasiStateMachine::STATUS_VERIFIKASI_BERKAS)
            <x-ui.button type="button" @click="$dispatch('open-modal', 'approve-berkas-modal')" variant="success" size="sm">
                <x-ui.icon name="check-circle" class="fs-4 me-1" />
                Setujui Berkas
            </x-ui.button>
            <x-ui.button type="button" @click="$dispatch('open-modal', 'reject-berkas-modal')" variant="danger" size="sm">
                <x-ui.icon name="cross-circle" class="fs-4 me-1" />
                Tolak Berkas
            </x-ui.button>
        @endif

        <x-ui.button :href="route('admin.akreditasi')" variant="light">
            <x-ui.icon name="exit-right" class="fs-4 me-1" />
            Kembali
        </x-ui.button>
    </x-slot:toolbar>

    <div class="row g-5 mb-5">
        <div class="col-lg-4">
            <x-ui.stat-card label="Status Pengajuan" value="{{ $status['label'] }}" :variant="$status['variant']" icon="shield-tick" />
        </div>
        <div class="col-lg-4">
            <x-ui.stat-card label="Tim Penilai" value="{{ $akreditasi->assessments->count() }} Asesor" variant="info" icon="profile-user" />
        </div>
        <div class="col-lg-4">
            <x-ui.stat-card label="Jadwal Visitasi" value="{{ $akreditasi->tgl_visitasi ? \Carbon\Carbon::parse($akreditasi->tgl_visitasi)->format('d M Y') : 'Belum Dijadwalkan' }}" variant="success" icon="calendar" />
        </div>
    </div>

    <x-akreditasi.workflow-stepper
        :status="$akreditasi->status"
        title="Tahapan Akreditasi LP2M"
        subtitle="Pantau posisi pengajuan dari review awal, review asesor, visitasi, penilaian pasca visitasi, validasi admin, sampai hasil akhir."
        class="mb-5"
    />

    {{-- Rejection History --}}
    @if(!empty($rejectionStatus) && ($rejectionStatus['count'] > 0 || $rejectionStatus['history']->count() > 0))
        <div class="mb-5">
            @php
                $adminFinalRejection = $rejectionStatus['history']->where('type', 'admin_final')->first();
            @endphp
            @if($adminFinalRejection)
                <x-ui.section-card title="Detail Penolakan Final (Admin)" subtitle="Penolakan terstruktur oleh Admin pada tahap Validasi." class="mb-4">
                    <div class="p-5">
                        <div class="d-flex flex-column gap-4">
                            @foreach($adminFinalRejection->categories ?? [] as $entry)
                                <div class="spm-soft-panel">
                                    <div class="spm-detail-label">
                                        {{ config('akreditasi.final_rejection_categories.' . ($entry['category'] ?? ''), $entry['category'] ?? '-') }}
                                    </div>
                                    <div class="spm-detail-value spm-detail-value-muted">{{ $entry['explanation'] ?? '-' }}</div>
                                </div>
                            @endforeach
                            <div class="text-muted fs-8">
                                Ditolak pada: {{ $adminFinalRejection->created_at->format('d F Y H:i') }}
                            </div>
                        </div>
                    </div>
                </x-ui.section-card>
            @endif

            @if($rejectionStatus['history']->count() > 0)
                <x-ui.section-card title="Riwayat Penolakan" subtitle="Catatan penolakan asesor dan admin untuk pengajuan ini.">
                    <div class="p-5">
                        <div class="d-flex flex-column gap-4">
                            @foreach($rejectionStatus['history'] as $rejection)
                                <div class="spm-soft-panel">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="fw-semibold">
                                            @if($rejection->type === 'admin_final')
                                                Penolakan Final (Admin)
                                            @else
                                                Penolakan Asesor #{{ $rejection->rejection_number }}
                                            @endif
                                        </div>
                                        <x-ui.badge variant="{{ match($rejection->status) {
                                            'pending' => 'warning',
                                            'submitted' => 'info',
                                            'accepted' => 'success',
                                            'expired' => 'danger',
                                            'limit_reached' => 'danger',
                                            'final' => 'danger',
                                            default => 'secondary',
                                        } }}">
                                            {{ match($rejection->status) {
                                                'pending' => 'Menunggu Perbaikan',
                                                'submitted' => 'Perbaikan Dikirim',
                                                'accepted' => 'Diterima',
                                                'expired' => 'Kadaluarsa',
                                                'limit_reached' => 'Batas Tercapai',
                                                'final' => 'Final',
                                                default => $rejection->status,
                                            } }}
                                        </x-ui.badge>
                                    </div>
                                    @if($rejection->type === 'asesor' && $rejection->items)
                                        <div class="d-flex flex-wrap gap-2 mb-3">
                                            @foreach($rejection->items as $item)
                                                <x-ui.badge variant="light-danger">{{ $item['label'] ?? $item['section'] ?? '' }}</x-ui.badge>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if($rejection->alasan)
                                        <div class="spm-detail-value spm-detail-value-muted">{{ $rejection->alasan }}</div>
                                    @endif
                                    <div class="text-muted fs-8 mt-2">
                                        {{ $rejection->created_at->format('d F Y H:i') }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </x-ui.section-card>
            @endif
        </div>
    @endif

    <x-ui.card flush>
        <div class="spm-detail-tabs-shell px-6 pt-5 pb-5">
            <x-ui.tabs>
                <x-ui.tab :href="request()->fullUrlWithQuery(['tab' => 'profil'])" :active="$activeTab === 'profil'">Profil</x-ui.tab>
                <x-ui.tab :href="request()->fullUrlWithQuery(['tab' => 'ipm'])" :active="$activeTab === 'ipm'">IPM</x-ui.tab>
                <x-ui.tab :href="request()->fullUrlWithQuery(['tab' => 'sdm'])" :active="$activeTab === 'sdm'">SDM</x-ui.tab>
                <x-ui.tab :href="request()->fullUrlWithQuery(['tab' => 'edpm_pesantren'])" :active="$activeTab === 'edpm_pesantren'">EDPM</x-ui.tab>
                <x-ui.tab :href="request()->fullUrlWithQuery(['tab' => 'instrumen'])" :active="$activeTab === 'instrumen'">Nilai</x-ui.tab>
                <x-ui.tab :href="request()->fullUrlWithQuery(['tab' => 'laporan_visitasi'])" :active="$activeTab === 'laporan_visitasi'">Laporan Visitasi</x-ui.tab>
                <x-ui.tab :href="request()->fullUrlWithQuery(['tab' => 'audit_trail'])" :active="$activeTab === 'audit_trail'">Audit Trail</x-ui.tab>
            </x-ui.tabs>
        </div>

        <div class="spm-detail-tab-content p-5">
            <div x-show="activeTab === 'profil'" x-cloak>
                @include('admin.akreditasi.detail.tabs.profil')
            </div>
            <div x-show="activeTab === 'ipm'" x-cloak>
                @include('admin.akreditasi.detail.tabs.ipm')
            </div>
            <div x-show="activeTab === 'sdm'" x-cloak>
                @include('admin.akreditasi.detail.tabs.sdm')
            </div>
            <div x-show="activeTab === 'edpm_pesantren'" x-cloak>
                @include('admin.akreditasi.detail.tabs.edpm-pesantren')
            </div>
            <div x-show="activeTab === 'instrumen'" x-cloak>
                @include('admin.akreditasi.detail.tabs.instrumen')
            </div>
            <div x-show="activeTab === 'laporan_visitasi'" x-cloak>
                @include('admin.akreditasi.detail.tabs.laporan-visitasi')
            </div>
            <div x-show="activeTab === 'audit_trail'" x-cloak>
                @include('admin.akreditasi.detail.tabs.audit-trail')
            </div>
        </div>

        {{-- Visitasi Edit Modal --}}
        <x-ui.modal name="visitasi-edit-modal" focusable>
            <form method="POST" action="{{ route('admin.akreditasi-detail.reschedule-visitasi', $akreditasi->uuid) }}">
                @csrf
                <x-ui.modal-header
                    title="Reschedule Jadwal Visitasi"
                    subtitle="Perbarui jadwal visitasi dalam rentang penilaian."
                    icon="timer"
                />
                <x-ui.modal-body>
                    <div class="row g-5">
                        <div class="col-md-6">
                            <x-ui.form-field label="Tanggal Mulai Visitasi" for="tgl_visitasi" :error="$errors->get('tgl_visitasi')">
                                <input type="date" name="tgl_visitasi" id="tgl_visitasi" class="form-control" value="{{ old('tgl_visitasi', $akreditasi->tgl_visitasi) }}" required />
                            </x-ui.form-field>
                        </div>
                        <div class="col-md-6">
                            <x-ui.form-field label="Tanggal Akhir Visitasi" for="tgl_visitasi_akhir" :error="$errors->get('tgl_visitasi_akhir')">
                                <input type="date" name="tgl_visitasi_akhir" id="tgl_visitasi_akhir" class="form-control" value="{{ old('tgl_visitasi_akhir', $akreditasi->tgl_visitasi_akhir ?? $akreditasi->tgl_visitasi) }}" required />
                            </x-ui.form-field>
                        </div>
                    </div>
                </x-ui.modal-body>
                <x-ui.modal-footer>
                    <x-ui.button type="button" variant="light" x-on:click="$dispatch('close')">Batal</x-ui.button>
                    <x-ui.button type="submit" variant="primary">Simpan Perubahan</x-ui.button>
                </x-ui.modal-footer>
            </form>
        </x-ui.modal>
    </x-ui.card>

    {{-- Reassign Asesor Modal --}}
    <x-ui.modal name="reassign-asesor-modal" focusable>
        <form method="POST" action="{{ route('admin.akreditasi-detail.reassign-asesor', $akreditasi->uuid) }}">
            @csrf
            <x-ui.modal-header
                title="Ganti Asesor"
                subtitle="Pilih asesor pengganti untuk akreditasi yang telah melewati deadline."
                icon="arrows-circle"
            />
            <x-ui.modal-body>
                <div class="notice d-flex bg-body rounded border border-dashed border-gray-300 p-4 mb-5">
                    <x-ui.icon name="warning-2" class="fs-2 text-danger me-4" />
                    <div class="d-flex flex-column">
                        <h4 class="mb-1 text-danger">Akreditasi Terlambat</h4>
                        <span class="fs-7 text-gray-700">Asesor saat ini belum menyelesaikan tugasnya setelah melewati deadline. Pilih asesor pengganti untuk melanjutkan proses akreditasi.</span>
                    </div>
                </div>
                <x-ui.form-field label="Asesor Pengganti" for="reassignAsesorId" :error="$errors->get('reassignAsesorId')">
                    <select name="reassignAsesorId" id="reassignAsesorId" class="form-select" required>
                        <option value="">Pilih Asesor Pengganti</option>
                        @foreach ($availableAsesorsForReassignment as $asesor)
                            <option value="{{ $asesor['id'] }}">
                                {{ $asesor['nama_dengan_gelar'] ?? ($asesor['user']['name'] ?? 'Asesor #' . $asesor['id']) }}
                            </option>
                        @endforeach
                    </select>
                </x-ui.form-field>
                <div class="text-muted fs-7 mt-3">
                    <x-ui.icon name="information-5" class="fs-6 me-1" />
                    Setelah penggantian, deadline baru akan ditetapkan berdasarkan konfigurasi sistem.
                </div>
            </x-ui.modal-body>
            <x-ui.modal-footer>
                <x-ui.button type="button" variant="light" x-on:click="$dispatch('close')">Batal</x-ui.button>
                <x-ui.button type="submit" variant="danger">Ganti Asesor</x-ui.button>
            </x-ui.modal-footer>
        </form>
    </x-ui.modal>

    {{-- Approve Berkas Modal --}}
    <x-ui.modal name="approve-berkas-modal" focusable>
        <form method="POST" action="{{ route('admin.akreditasi-detail.approve-berkas', $akreditasi->uuid) }}">
            @csrf
            <x-ui.modal-header title="Setujui Berkas" subtitle="Tugaskan Ketua Kelompok dan Anggota Kelompok untuk melanjutkan ke tahap Review Asesor." icon="check-circle" variant="success" />
            <x-ui.modal-body>
                <x-ui.form-field label="Ketua Kelompok" for="asesor1Id" :error="$errors->first('asesor1Id')">
                    <select name="asesor1Id" id="asesor1Id" class="form-select" required>
                        <option value="">-- Pilih Ketua Kelompok --</option>
                        @foreach($asesorsForAssignment as $asesor)
                            <option value="{{ $asesor['user_id'] }}" {{ old('asesor1Id') == $asesor['user_id'] ? 'selected' : '' }}>{{ $asesor['name'] }}</option>
                        @endforeach
                    </select>
                </x-ui.form-field>
                <x-ui.form-field label="Anggota Kelompok" for="asesor2Id" :error="$errors->first('asesor2Id')">
                    <select name="asesor2Id" id="asesor2Id" class="form-select" required>
                        <option value="">-- Pilih Anggota Kelompok --</option>
                        @foreach($asesorsForAssignment as $asesor)
                            <option value="{{ $asesor['user_id'] }}" {{ old('asesor2Id') == $asesor['user_id'] ? 'selected' : '' }}>{{ $asesor['name'] }}</option>
                        @endforeach
                    </select>
                </x-ui.form-field>
            </x-ui.modal-body>
            <x-ui.modal-footer>
                <x-ui.button type="button" variant="light" x-on:click="$dispatch('close-modal', 'approve-berkas-modal')">Batal</x-ui.button>
                <x-ui.button type="submit" variant="success">Setujui & Tugaskan Tim Asesor</x-ui.button>
            </x-ui.modal-footer>
        </form>
    </x-ui.modal>

    {{-- Reject Berkas Modal --}}
    <x-ui.modal name="reject-berkas-modal" focusable>
        <form method="POST" action="{{ route('admin.akreditasi-detail.reject-berkas', $akreditasi->uuid) }}">
            @csrf
            <x-ui.modal-header title="Tolak Berkas" subtitle="Pilih bagian yang bermasalah dan berikan catatan penolakan." icon="cross-circle" variant="danger" />
            <x-ui.modal-body>
                <x-ui.form-field label="Bagian yang Ditolak" :error="$errors->first('berkasRejectionSections')">
                    <div class="d-flex flex-column gap-3">
                        @foreach(['profil' => 'Profil', 'ipm.nsp' => 'IPM - NSP', 'ipm.kurikulum' => 'IPM - Kurikulum', 'ipm.buku_ajar' => 'IPM - Buku Ajar', 'ipm.lulus_santri' => 'IPM - Lulus Santri', 'sdm' => 'SDM', 'edpm' => 'EDPM'] as $value => $label)
                            <label class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="berkasRejectionSections[]" value="{{ $value }}" {{ in_array($value, old('berkasRejectionSections', [])) ? 'checked' : '' }} />
                                <span class="form-check-label">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </x-ui.form-field>
                <x-ui.form-field label="Catatan Penolakan" for="berkasRejectionCatatan" :error="$errors->first('berkasRejectionCatatan')" hint="Minimal 10 karakter, maksimal 2000 karakter.">
                    <textarea name="berkasRejectionCatatan" id="berkasRejectionCatatan" rows="4" class="form-control" placeholder="Jelaskan alasan penolakan berkas..." required minlength="10" maxlength="2000">{{ old('berkasRejectionCatatan') }}</textarea>
                </x-ui.form-field>
            </x-ui.modal-body>
            <x-ui.modal-footer>
                <x-ui.button type="button" variant="light" x-on:click="$dispatch('close-modal', 'reject-berkas-modal')">Batal</x-ui.button>
                <x-ui.button type="submit" variant="danger">Tolak Berkas</x-ui.button>
            </x-ui.modal-footer>
        </form>
    </x-ui.modal>
</x-ui.page>
@endsection
