@use('App\Models\Akreditasi')
@use('Illuminate\Support\Facades\Storage')
@php
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

    $canSubmitDocumentRejection = (int) $asesorTipe === 1
        && (int) $akreditasi->status === \App\StateMachine\AkreditasiStateMachine::STATUS_ASSESSMENT
        && ! empty($rejectionStatus)
        && (! $rejectionStatus['active'] || ! in_array($rejectionStatus['active']->status, ['pending', 'submitted'], true))
        && $rejectionStatus['count'] < $rejectionStatus['limit'];
@endphp

<x-slot name="header">{{ __('Detail Akreditasi') }}</x-slot>

<x-ui.page
    title="Visitasi Akreditasi"
    subtitle="{{ $pesantren?->nama_pesantren ?? $akreditasi->user->name }}"
    class="spm-detail-page"
    x-data="{ ...akreditasiManagement(), ...asesorManagement() }"
    wire:poll.visible.30s="checkForUpdates"
>
    <x-akreditasi.presence-indicator :akreditasi-id="$akreditasi->id" />
    <x-slot:toolbar>
        <x-ui.status-badge :variant="$statusVariant">
            {{ Akreditasi::getStatusLabel($akreditasi->status) }}
        </x-ui.status-badge>

        @if((int) $akreditasi->status === 3 && $asesorTipe === 1)
            <x-ui.button type="button" wire:click="confirmVisitasiSelesai" wire:loading.attr="disabled" variant="success" size="sm">
                <span wire:loading.remove wire:target="confirmVisitasiSelesai">
                    <x-ui.icon name="check-circle" class="fs-4 me-1" />
                    Konfirmasi Visitasi Selesai
                </span>
                <span wire:loading wire:target="confirmVisitasiSelesai">Memproses...</span>
            </x-ui.button>
        @endif

        @if((int) $akreditasi->status === 2 && $asesorTipe === 1)
            <x-ui.button type="button" wire:click="finalizeScoring" wire:loading.attr="disabled" variant="primary" size="sm">
                <span wire:loading.remove wire:target="finalizeScoring">
                    <x-ui.icon name="shield-tick" class="fs-4 me-1" />
                    Finalisasi Penilaian
                </span>
                <span wire:loading wire:target="finalizeScoring">Memproses...</span>
            </x-ui.button>
        @endif

        @if($canSubmitDocumentRejection)
            <x-ui.button
                type="button"
                variant="light-danger"
                size="sm"
                x-on:click="$dispatch('open-modal', 'asesor-reject-documents-modal')"
            >
                <x-ui.icon name="cross-circle" class="fs-4 me-1" />
                Tolak Dokumen
            </x-ui.button>
        @endif

        <x-ui.button :href="route('asesor.akreditasi')" variant="light">
            <x-ui.icon name="exit-right" class="fs-4 me-1" />
            Kembali
        </x-ui.button>
    </x-slot:toolbar>

    <div class="row g-5 mb-6">
        <div class="col-lg-4">
            <x-ui.stat-card label="Status Tugas" value="{{ Akreditasi::getStatusLabel($akreditasi->status) }}" variant="{{ $statusVariant }}" icon="shield-tick" />
        </div>

        <div class="col-lg-4">
            <x-ui.stat-card label="Jadwal Visitasi" value="{{ $akreditasi->tgl_visitasi ? \Carbon\Carbon::parse($akreditasi->tgl_visitasi)->format('d M Y') : 'Belum Dijadwalkan' }}" variant="info" icon="calendar" />
        </div>

        <div class="col-lg-4">
            <x-ui.stat-card label="Peran Penilaian" value="{{ (int) $asesorTipe === 1 ? 'Ketua Kelompok' : 'Anggota Kelompok' }}" variant="success" icon="security-user" />
        </div>
    </div>

    <x-akreditasi.workflow-stepper
        :status="$akreditasi->status"
        title="Tahapan Akreditasi LP2M"
        subtitle="Gunakan alur ini untuk membedakan review dokumen, visitasi, dan penilaian pasca visitasi."
        class="mb-6"
    />

    @if (session('status'))
        <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
    @endif

    <x-ui.card flush>
        <div class="spm-detail-tabs-shell px-6 pt-5 pb-5">
            <x-ui.tabs>
                <x-ui.tab wire:click="setTab('profil')" :active="$activeTab === 'profil'">Profil</x-ui.tab>
                <x-ui.tab wire:click="setTab('ipm')" :active="$activeTab === 'ipm'">IPM</x-ui.tab>
                <x-ui.tab wire:click="setTab('sdm')" :active="$activeTab === 'sdm'">SDM</x-ui.tab>
                <x-ui.tab wire:click="setTab('edpm_pesantren')" :active="$activeTab === 'edpm_pesantren'">EDPM</x-ui.tab>
                @if(in_array((int) $akreditasi->status, [
                    \App\StateMachine\AkreditasiStateMachine::STATUS_PASCA_VISITASI,
                    \App\StateMachine\AkreditasiStateMachine::STATUS_VALIDASI_ADMIN,
                    \App\StateMachine\AkreditasiStateMachine::STATUS_SELESAI,
                ], true))
                    <x-ui.tab wire:click="setTab('instrumen')" :active="$activeTab === 'instrumen'">Penilaian</x-ui.tab>
                @endif
                @if($akreditasi->status == \App\StateMachine\AkreditasiStateMachine::STATUS_VALIDASI_ADMIN
                    || $akreditasi->status == \App\StateMachine\AkreditasiStateMachine::STATUS_PASCA_VISITASI
                    || $akreditasi->status == \App\StateMachine\AkreditasiStateMachine::STATUS_SELESAI)
                    <x-ui.tab wire:click="setTab('laporan_visitasi')" :active="$activeTab === 'laporan_visitasi'">Laporan Visitasi</x-ui.tab>
                @endif
            </x-ui.tabs>
        </div>

        <div class="spm-detail-tab-content p-6">
            @include('livewire.pages.asesor.akreditasi-detail.tabs.profil')
            @include('livewire.pages.asesor.akreditasi-detail.tabs.ipm')
            @include('livewire.pages.asesor.akreditasi-detail.tabs.sdm')
            @include('livewire.pages.asesor.akreditasi-detail.tabs.edpm-pesantren')
            @include('livewire.pages.asesor.akreditasi-detail.tabs.instrumen')
            @include('livewire.pages.asesor.akreditasi-detail.tabs.laporan-visitasi')

            {{-- Rejection Section for Ketua Kelompok --}}
            @if($asesorTipe == 1 && !empty($rejectionStatus))
                {{-- Rejection count and remaining attempts --}}
                @if($rejectionStatus['count'] > 0 || $rejectionStatus['active'])
                    <div class="mt-6">
                        <x-ui.section-card title="Status Penolakan" subtitle="Informasi penolakan dan sisa kesempatan.">
                            <div class="p-6">
                                <div class="d-flex align-items-center gap-3 mb-4">
                                    <span class="fw-semibold">Penolakan:</span>
                                    <x-ui.badge variant="{{ $rejectionStatus['count'] >= $rejectionStatus['limit'] ? 'danger' : 'info' }}">
                                        {{ $rejectionStatus['count'] }} dari {{ $rejectionStatus['limit'] }}
                                    </x-ui.badge>
                                    @if($rejectionStatus['limit'] - $rejectionStatus['count'] > 0)
                                        <span class="text-muted fs-8">(Sisa {{ $rejectionStatus['limit'] - $rejectionStatus['count'] }} kesempatan)</span>
                                    @endif
                                </div>

                                {{-- Accept/Reject options after perbaikan submission --}}
                                @if($rejectionStatus['active'] && $rejectionStatus['active']->status === 'submitted')
                                    <x-ui.alert variant="info" icon="information-2" title="Perbaikan Telah Dikirim" class="mb-4">
                                        <div>
                                            Pesantren telah mengirim perbaikan. Silakan review dan pilih tindakan.
                                            @if($rejectionStatus['active']->items)
                                                <div class="mt-2">
                                                    <span class="text-muted fs-8">Item yang diperbaiki:</span>
                                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                                        @foreach($rejectionStatus['active']->items as $item)
                                                            <x-ui.badge variant="light">{{ $item }}</x-ui.badge>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                            <div class="d-flex gap-2 mt-3">
                                                <x-ui.button @click="confirmTerimaPerbaikan($wire)" wire:loading.attr="disabled" variant="success" size="sm">
                                                    <span wire:loading.remove wire:target="acceptPerbaikan">Terima Perbaikan</span>
                                                    <span wire:loading wire:target="acceptPerbaikan">Memproses...</span>
                                                </x-ui.button>
                                                <x-ui.button wire:click="rejectAgain" variant="danger" size="sm">
                                                    Tolak Lagi
                                                </x-ui.button>
                                            </div>
                                        </div>
                                    </x-ui.alert>
                                @endif

                                {{-- Rejection History --}}
                                @if($rejectionStatus['history']->count() > 0)
                                    <div class="spm-detail-label mb-3">Riwayat Penolakan</div>
                                    <div class="d-flex flex-column gap-3">
                                        @foreach($rejectionStatus['history'] as $rejection)
                                            <div class="spm-soft-panel">
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <div class="fw-semibold">
                                                        @if($rejection->type === 'admin_final')
                                                            Penolakan Final (Admin)
                                                        @else
                                                            Penolakan #{{ $rejection->rejection_number }}
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
                                                @if($rejection->items)
                                                    <div class="mb-2">
                                                        <span class="text-muted fs-8">Item ditolak:</span>
                                                        <div class="d-flex flex-wrap gap-1 mt-1">
                                                            @foreach($rejection->items as $item)
                                                                <x-ui.badge variant="light">{{ $item }}</x-ui.badge>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                                @if($rejection->explanation)
                                                    <div class="mb-2">
                                                        <span class="text-muted fs-8">Catatan:</span>
                                                        <div class="fs-7">{{ $rejection->explanation }}</div>
                                                    </div>
                                                @endif
                                                <div class="d-flex gap-3 text-muted fs-8">
                                                    <span>Tanggal: {{ $rejection->created_at->format('d M Y H:i') }}</span>
                                                    @if($rejection->perbaikan_submitted_at)
                                                        <span>Perbaikan dikirim: {{ $rejection->perbaikan_submitted_at->format('d M Y H:i') }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </x-ui.section-card>
                    </div>
                @endif

            @endif
        </div>
    </x-ui.card>

    @if($canSubmitDocumentRejection)
        <x-ui.modal name="asesor-reject-documents-modal" maxWidth="2xl" focusable>
            <form x-on:submit.prevent="confirmKirimPenolakan($wire)" data-ui-modal-form>
                <x-ui.modal-header
                    title="Tolak Dokumen"
                    subtitle="Pilih bagian yang perlu diperbaiki, lalu berikan catatan yang jelas untuk pesantren."
                    icon="cross-circle"
                    variant="danger"
                />

                <x-ui.modal-body>
                    <x-ui.form-field label="Item yang Ditolak" required :error="$errors->get('rejectedItems')">
                        <div class="row g-3">
                            @foreach($selectableItems as $section)
                                <div class="col-lg-6">
                                    <div class="spm-soft-panel h-100">
                                        @if(empty($section['children']))
                                            <x-ui.checkbox model="rejectedItems" :value="$section['id']" class="align-items-center">
                                                <span class="fw-semibold">{{ $section['label'] }}</span>
                                            </x-ui.checkbox>
                                        @else
                                            <div class="fw-semibold text-gray-800 mb-3">{{ $section['label'] }}</div>
                                            <div class="d-flex flex-column gap-2 ps-1">
                                                @foreach($section['children'] as $child)
                                                    @if(empty($child['children']))
                                                        <x-ui.checkbox model="rejectedItems" :value="$child['id']" class="align-items-center">
                                                            <span>{{ $child['label'] }}</span>
                                                        </x-ui.checkbox>
                                                    @else
                                                        <div class="fw-semibold text-gray-700 mt-1">{{ $child['label'] }}</div>
                                                        <div class="d-flex flex-column gap-1 ps-3">
                                                            @foreach($child['children'] as $subChild)
                                                                <x-ui.checkbox model="rejectedItems" :value="$subChild['id']" class="align-items-center">
                                                                    <span class="fs-8">{{ $subChild['label'] }}</span>
                                                                </x-ui.checkbox>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </x-ui.form-field>

                    <x-ui.form-field label="Catatan Penolakan" for="rejectionExplanation" required :error="$errors->get('rejectionExplanation')" hint="Minimal 10 karakter. Tulis bagian yang perlu diperbaiki dan ekspektasi perbaikannya.">
                        <x-ui.textarea
                            model="rejectionExplanation"
                            id="rejectionExplanation"
                            rows="4"
                            required
                            placeholder="Contoh: Dokumen kurikulum belum memuat struktur program dan perlu dilengkapi dengan bukti pendukung..."
                        />
                    </x-ui.form-field>
                </x-ui.modal-body>

                <x-ui.modal-footer>
                    <x-ui.button type="button" variant="light" x-on:click="$dispatch('close-modal', 'asesor-reject-documents-modal')">Batal</x-ui.button>
                    <x-ui.button type="submit" variant="danger" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="submitRejection">
                            <x-ui.icon name="cross-circle" class="fs-4 me-1" />
                            Kirim Penolakan
                        </span>
                        <span wire:loading wire:target="submitRejection">Memproses...</span>
                    </x-ui.button>
                </x-ui.modal-footer>
            </form>
        </x-ui.modal>
    @endif
</x-ui.page>
