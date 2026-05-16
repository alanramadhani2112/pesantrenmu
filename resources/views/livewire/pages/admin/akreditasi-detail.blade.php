<?php

use App\Models\Akreditasi;
use App\Models\Pesantren;
use App\Models\Ipm;
use App\Models\SdmPesantren;
use App\Models\MasterEdpmKomponen;
use App\Models\Edpm;
use App\Models\EdpmCatatan;
use App\Models\AkreditasiEdpm;
use App\Models\AkreditasiEdpmCatatan;
use App\Services\ResubmissionService;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;
    public $akreditasi;
    public $pesantren;
    public $ipm;
    public $sdm;
    public $komponens;

    // Pesantren's EDPM data (read only)
    public $pesantrenEvaluasis = [];
    public $pesantrenCatatans = [];
    public $pesantrenLinks = [];

    // Assessor 1 EDPM evaluation
    public $asesor1Evaluasis = [];
    public $asesor1Catatans = [];
    public $asesor1Nks = [];
    public $asesor1CatatanNks = [];
    public $asesor1ButirCatatans = [];

    // Assessor 2 EDPM evaluation
    public $asesor2Evaluasis = [];
    public $asesor2Catatans = [];
    public $asesor2ButirCatatans = [];

    public $tgl_visitasi;
    public $tgl_visitasi_akhir;

    public $nomor_sk;
    public $sertifikat_file;
    public $masa_berlaku;
    public $masa_berlaku_akhir;
    public $catatan_admin;

    // Admin NV (Nilai Verifikasi)
    public $adminNvs = [];

    // Resubmission chain data
    public $chainTimeline = [];
    public $resubmissionStatus = null;

    // Rejection data
    public $rejectionCategories = [];
    public $rejectionStatus = [];

    public $activeTab = 'profil';
    public $levels = [];
    public $fields = [
        'santri_l',
        'santri_p',
        'ustadz_dirosah_l',
        'ustadz_dirosah_p',
        'ustadz_non_dirosah_l',
        'ustadz_non_dirosah_p',
        'pamong_l',
        'pamong_p',
        'musyrif_l',
        'musyrif_p',
        'tendik_l',
        'tendik_p',
    ];

    public function mount($uuid)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->canAccessAdminArea()) {
                    abort(403);
                }

        $akreditasiService = app(\App\Services\AkreditasiService::class);
        $pesantrenService = app(\App\Services\PesantrenService::class);

        $this->akreditasi = $akreditasiService->findAkreditasi($uuid, ['user.pesantren', 'assessments.asesor.user', 'assessment1', 'assessment2']);

        if (!$this->akreditasi) {
            abort(404);
        }

        $userId = $this->akreditasi->user_id;
        $this->pesantren = $pesantrenService->getProfile($userId);
        $this->ipm = $pesantrenService->getIpm($userId);
        $this->sdm = $pesantrenService->getSdm($userId)->keyBy('tingkat');
        if ($this->pesantren && $this->pesantren->relationLoaded('units')) {
            $this->levels = $this->pesantren->units->pluck('unit')->toArray();
        }
        
        $pEdpmData = $pesantrenService->getEdpmData($userId);
        $this->komponens = $pEdpmData['komponens'];

        // Load Pesantren EDPM
        $pEvaluasis = $pEdpmData['existingEdpms']->pluck('isian', 'butir_id');
        $pLinks = $pEdpmData['existingEdpms']->pluck('link', 'butir_id');
        $pCatatans = $pEdpmData['existingCatatans'];

        // Load Assessor 1 EDPM
        $asesor1Id = $this->akreditasi->assessment1->asesor_id ?? null;
        if ($asesor1Id) {
            $a1Data = $akreditasiService->getAsesorEdpmData($this->akreditasi->id, $asesor1Id);
            $a1Evaluasis = $a1Data['evaluasis'];
            $a1Nks = $a1Data['nks'];
            $a1Nvs = $a1Data['nvs'];
            $a1ButirCatatans = $a1Data['butirCatatans'];
            $a1Catatans = $a1Data['catatans'];
            $a1CatatanNks = $a1Data['catatanNks'];
        }

        // Load Assessor 2 EDPM
        $asesor2Id = $this->akreditasi->assessment2->asesor_id ?? null;
        if ($asesor2Id) {
            $a2Data = $akreditasiService->getAsesorEdpmData($this->akreditasi->id, $asesor2Id);
            $a2Evaluasis = $a2Data['evaluasis'];
            $a2ButirCatatans = $a2Data['butirCatatans'];
            $a2Catatans = $a2Data['catatans'];
        }

        foreach ($this->komponens as $komponen) {
            $this->pesantrenCatatans[$komponen->id] = $pCatatans[$komponen->id] ?? '';
            $this->asesor1Catatans[$komponen->id] = $a1Catatans[$komponen->id] ?? '';
            $this->asesor2Catatans[$komponen->id] = $a2Catatans[$komponen->id] ?? '';

            foreach ($komponen->butirs as $butir) {
                $this->pesantrenEvaluasis[$butir->id] = $pEvaluasis[$butir->id] ?? '';
                $this->pesantrenLinks[$butir->id] = $pLinks[$butir->id] ?? null;
                $this->asesor1Evaluasis[$butir->id] = $a1Evaluasis[$butir->id] ?? '';
                $this->asesor1Nks[$butir->id] = $a1Nks[$butir->id] ?? '';
                $this->adminNvs[$butir->id] = $a1Nvs[$butir->id] ?? ($this->akreditasi->status == 3 ? ($a1Nks[$butir->id] ?? '') : '');
                $this->asesor1ButirCatatans[$butir->id] = $a1ButirCatatans[$butir->id] ?? '';
                $this->asesor2Evaluasis[$butir->id] = $a2Evaluasis[$butir->id] ?? '';
                $this->asesor2ButirCatatans[$butir->id] = $a2ButirCatatans[$butir->id] ?? '';
            }
            $this->asesor1CatatanNks[$komponen->id] = $a1CatatanNks[$komponen->id] ?? '';
        }

        $this->nomor_sk = $this->akreditasi->nomor_sk;
        $this->masa_berlaku = $this->akreditasi->masa_berlaku;
        $this->masa_berlaku_akhir = $this->akreditasi->masa_berlaku_akhir;
        $this->tgl_visitasi = $this->akreditasi->tgl_visitasi;
        $this->tgl_visitasi_akhir = $this->akreditasi->tgl_visitasi_akhir;

        // Load resubmission chain timeline data
        $resubmissionService = app(ResubmissionService::class);
        $hasChain = $this->akreditasi->parent !== null || Akreditasi::where('parent', $this->akreditasi->id)->exists();
        if ($hasChain) {
            $this->chainTimeline = $resubmissionService->getChainTimeline($this->akreditasi->id);
            $this->resubmissionStatus = $resubmissionService->getResubmissionStatus($this->akreditasi->id);
        }

        // Load rejection status data
        $rejectionService = app(\App\Services\RejectionService::class);
        $this->rejectionStatus = $rejectionService->getRejectionStatus($this->akreditasi->id);
    }

    public function toggleLock()
    {
        if ($this->pesantren) {
            $prevLocked = $this->pesantren->is_locked;
            $this->pesantren->is_locked = !$this->pesantren->is_locked;
            $this->pesantren->save();

            $status = $this->pesantren->is_locked ? 'terkunci' : 'terbuka';

            if ($prevLocked && !$this->pesantren->is_locked) {
                // Notifikasi ke pesantren saat data dibuka kuncinya
                $this->akreditasi->user->notify(new \App\Notifications\AkreditasiNotification(
                    'buka_kunci',
                    'Akses Data Dibuka',
                    'Administrator telah membuka kunci data Anda. Anda sekarang dapat memperbarui profil dan dokumen.',
                    route('pesantren.profile')
                ));
            }

            $this->dispatch('notification-received', title: 'Berhasil', message: "Akses data pesantren berhasil diubah menjadi $status.");
        }
    }

    public function openVisitasiEditModal()
    {
        $this->tgl_visitasi = $this->akreditasi->tgl_visitasi;
        $this->tgl_visitasi_akhir = $this->akreditasi->tgl_visitasi_akhir ?? $this->akreditasi->tgl_visitasi;
        $this->resetErrorBag();
        $this->dispatch('open-modal', 'visitasi-edit-modal');
    }

    public function saveVisitasiReschedule()
    {
        $akreditasiService = app(\App\Services\AkreditasiService::class);
        
        $this->validate([
            'tgl_visitasi' => 'required|date',
            'tgl_visitasi_akhir' => 'required|date|after_or_equal:tgl_visitasi',
        ]);

        if ($akreditasiService->rescheduleVisitasi($this->akreditasi->id, $this->tgl_visitasi, $this->tgl_visitasi_akhir)) {
            $this->dispatch('close-modal', 'visitasi-edit-modal');
            $this->dispatch('notification-received', type: 'success', title: 'Berhasil!', message: 'Jadwal Visitasi berhasil diperbarui.');
            $this->akreditasi->refresh();
        } else {
             $this->dispatch('notification-received', type: 'error', title: 'Gagal!', message: 'Jadwal Visitasi gagal diperbarui.');
        }
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    protected function messages()
    {
        return [
            'adminNvs.*.required' => 'Nilai NV wajib diisi.',
            'adminNvs.*.integer' => 'Nilai NV harus berupa angka.',
            'adminNvs.*.between' => 'Nilai NV harus antara 1 sampai 4.',
        ];
    }

    protected function validationAttributes()
    {
        $attributes = [];
        foreach ($this->komponens as $k) {
            foreach ($k->butirs as $b) {
                $attributes["adminNvs.{$b->id}"] = "Nilai NV Butir {$b->nomor_butir}";
            }
        }
        return $attributes;
    }

    public function saveAdminNv()
    {
        if ($this->akreditasi->status != 3) {
            session()->flash('error', 'Data tidak dapat diubah karena status bukan Validasi.');
            return;
        }

        // Check if Kartu Kendali and Laporan Visitasi are uploaded
        if (empty($this->akreditasi->kartu_kendali) || empty($this->akreditasi->laporan_visitasi_file)) {
            $this->dispatch(
                'notification-received',
                type: 'error',
                title: 'Data Belum Lengkap',
                message: 'Nilai NV belum dapat disimpan karena Kartu Kendali atau Laporan Visitasi belum diunggah.'
            );
            return;
        }

        try {
            $this->validate([
                'adminNvs.*' => 'required|integer|between:1,4',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $missingItems = [];
            $errors = $e->validator->errors()->messages();

            foreach ($errors as $key => $messages) {
                if (preg_match('/adminNvs\.(\d+)/', $key, $matches)) {
                    $butirId = $matches[1];

                    // Find butir info from our komponens collection
                    foreach ($this->komponens as $komponen) {
                        $butir = $komponen->butirs->firstWhere('id', $butirId);
                        if ($butir) {
                            $missingItems[] = "<li><b>NV</b>: Butir {$butir->nomor_butir} ({$komponen->nama})</li>";
                            break;
                        }
                    }
                }
            }

            $htmlList = '<ul class="text-left list-disc pl-5 mt-2 space-y-1 text-[11px]">' . implode('', array_unique($missingItems)) . '</ul>';

            $this->dispatch(
                'validation-failed',
                title: 'Nilai NV Belum Lengkap',
                html: "Mohon lengkapi nilai verifikasi berikut sebelum menyimpan:<br>" . $htmlList
            );
            throw $e;
        }

        $asesor1Id = $this->akreditasi->assessment1->asesor_id ?? null;
        if (!$asesor1Id) {
            session()->flash('error', 'Asesor 1 tidak ditemukan.');
            return;
        }

        $akreditasiService = app(\App\Services\AkreditasiService::class);
        if ($asesor1Id) {
            $akreditasiService->updateAdminNv($this->akreditasi->id, $asesor1Id, $this->adminNvs);
        }

        $this->dispatch(
            'notification-received',
            type: 'success',
            title: 'Berhasil!',
            message: 'Nilai Verifikasi berhasil disimpan.'
        );
    }

    private function determineResults()
    {
        $bobotKomponen = [
            'MUTU LULUSAN' => 35,
            'PROSES PEMBELAJARAN' => 29,
            'MUTU USTAZ' => 18,
            'MANAJEMEN PESANTREN' => 18,
            'INDIKATOR PEMENUHAN RELATIF' => 97,
        ];

        $iprNullComponents = $this->komponens->filter(function ($k) {
            return is_null($k->ipr);
        });
        $iprNotNullComponents = $this->komponens->filter(function ($k) {
            return !is_null($k->ipr);
        });

        $totalSkorIprNull = 0;
        foreach ($iprNullComponents as $k) {
            $b = $bobotKomponen[$k->nama] ?? 0;
            $c_total = count($k->butirs) * 4;
            $c_ci = 0;
            foreach ($k->butirs as $butir) {
                $c_ci += (int)($this->adminNvs[$butir->id] ?? 0);
            }
            if ($c_total > 0) {
                $totalSkorIprNull += round(($c_ci / $c_total) * $b);
            }
        }

        $totalSkorIprNotNull = 0;
        foreach ($iprNotNullComponents as $k) {
            $c_total = count($k->butirs) * 4;
            $c_ci = 0;
            foreach ($k->butirs as $butir) {
                $c_ci += (int)($this->adminNvs[$butir->id] ?? 0);
            }
            if ($c_total > 0) {
                $totalSkorIprNotNull += round(($c_ci / $c_total) * 100);
            }
        }

        $nilai = round((0.7 * $totalSkorIprNull) + (0.3 * $totalSkorIprNotNull));

        $peringkat = 'NA';
        if ($nilai >= 86) {
            $peringkat = 'Unggul';
        } elseif ($nilai >= 70) {
            $peringkat = 'Baik';
        } elseif ($nilai >= 0) {
            $peringkat = 'Cukup';
        }

        return ['nilai' => $nilai, 'peringkat' => $peringkat];
    }

    public function approve()
    {
        if (!$this->checkScores()) {
            return;
        }

        $this->validate([
            'nomor_sk' => 'required|string|max:255',
            'sertifikat_file' => 'required|file|mimes:pdf|max:10240',
            'masa_berlaku' => 'required|date',
            'masa_berlaku_akhir' => 'required|date|after:masa_berlaku',
        ]);

        $results = $this->determineResults();
        $akreditasiService = app(\App\Services\AkreditasiService::class);
        
        $akreditasiService->finalizeAkreditasi($this->akreditasi->id, [
            'nomor_sk' => $this->nomor_sk,
            'sertifikat_file' => $this->sertifikat_file,
            'masa_berlaku' => $this->masa_berlaku,
            'masa_berlaku_akhir' => $this->masa_berlaku_akhir,
            'nilai' => $results['nilai'],
            'peringkat' => $results['peringkat'],
        ], true);

        session()->flash('status', 'Akreditasi berhasil disetujui.');
        return redirect()->route('admin.akreditasi');
    }

    public function reject()
    {
        if (!$this->checkScores()) {
            return;
        }

        $this->validate([
            'rejectionCategories' => 'required|array|min:1',
            'rejectionCategories.*.category' => 'required|string|in:nilai_tidak_memenuhi,laporan_tidak_lengkap,kartu_kendali_tidak_sesuai,inkonsistensi_data,lainnya',
            'rejectionCategories.*.explanation' => 'required|string|min:10|max:2000',
        ], [
            'rejectionCategories.required' => 'Pilih minimal satu kategori penolakan.',
            'rejectionCategories.min' => 'Pilih minimal satu kategori penolakan.',
            'rejectionCategories.*.category.required' => 'Kategori wajib dipilih.',
            'rejectionCategories.*.category.in' => 'Kategori tidak valid.',
            'rejectionCategories.*.explanation.required' => 'Penjelasan wajib diisi untuk setiap kategori.',
            'rejectionCategories.*.explanation.min' => 'Penjelasan minimal 10 karakter.',
        ]);

        $akreditasiService = app(\App\Services\AkreditasiService::class);
        $result = $akreditasiService->finalizeAkreditasi($this->akreditasi->id, [
            'rejection_categories' => $this->rejectionCategories,
        ], false);

        if ($result) {
            session()->flash('status', 'Akreditasi telah ditolak.');
            return redirect()->route('admin.akreditasi');
        } else {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal', message: 'Penolakan gagal diproses.');
        }
    }

    public function addRejectionCategory()
    {
        $this->rejectionCategories[] = ['category' => '', 'explanation' => ''];
    }

    public function removeRejectionCategory($index)
    {
        unset($this->rejectionCategories[$index]);
        $this->rejectionCategories = array_values($this->rejectionCategories);
    }

    public function getTotal($field)
    {
        $total = 0;
        foreach ($this->levels as $level) {
            $total += (int)($this->sdm[$level]->$field ?? 0);
        }
        return $total;
    }

    private function checkScores()
    {
        $isMissing = false;
        foreach ($this->komponens as $komponen) {
            foreach ($komponen->butirs as $butir) {
                if (empty($this->asesor1Nks[$butir->id]) || empty($this->adminNvs[$butir->id])) {
                    $isMissing = true;
                    break 2;
                }
            }
        }

        if ($isMissing) {
            $this->dispatch(
                'notification-received',
                type: 'error',
                title: 'Data Belum Lengkap',
                message: 'Tidak dapat memproses akreditasi. Pastikan nilai NK (Asesor) dan NV (Admin) telah diisi untuk semua butir.'
            );
            return false;
        }

        return true;
    }
}; ?>

@php
    $statusVariant = match ((int) $akreditasi->status) {
        1 => 'success',
        2 => 'danger',
        3 => 'warning',
        4 => 'info',
        default => 'primary',
    };

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

<x-slot name="header">{{ __('Detail Akreditasi') }}</x-slot>

<x-ui.page
    title="Detail Akreditasi"
    subtitle="{{ $pesantren?->nama_pesantren ?? $akreditasi->user->name }}"
    x-data="{ ...akreditasiManagement(), ...adminManagement() }"
>
    <x-slot:toolbar>
        <x-ui.status-badge :variant="$statusVariant">
            {{ Akreditasi::getStatusLabel($akreditasi->status) }}
        </x-ui.status-badge>

        @if($resubmissionStatus)
            <x-ui.badge variant="warning">
                Pengajuan Ulang: {{ $resubmissionStatus['count'] }}/{{ $resubmissionStatus['limit'] }}
            </x-ui.badge>
        @endif

        <x-ui.button :href="route('admin.akreditasi')" variant="light">
            <x-ui.icon name="exit-right" class="fs-4 me-1" />
            Kembali
        </x-ui.button>
    </x-slot:toolbar>

    <div class="row g-5 mb-6">
        <div class="col-lg-4">
            <x-ui.stat-card label="Status Pengajuan" value="{{ Akreditasi::getStatusLabel($akreditasi->status) }}" variant="{{ $statusVariant }}">
                <x-slot:icon><x-ui.icon name="shield-tick" class="fs-2" /></x-slot:icon>
            </x-ui.stat-card>
        </div>

        <div class="col-lg-4">
            <x-ui.stat-card label="Tim Penilai" value="{{ $akreditasi->assessments->count() }} Asesor" variant="info">
                <x-slot:icon><x-ui.icon name="profile-user" class="fs-2" /></x-slot:icon>
            </x-ui.stat-card>
        </div>

        <div class="col-lg-4">
            <x-ui.stat-card label="Jadwal Visitasi" value="{{ $akreditasi->tgl_visitasi ? \Carbon\Carbon::parse($akreditasi->tgl_visitasi)->format('d M Y') : 'Belum Dijadwalkan' }}" variant="success">
                <x-slot:icon><x-ui.icon name="calendar" class="fs-2" /></x-slot:icon>
            </x-ui.stat-card>
        </div>
    </div>

    {{-- Rejection History and Admin Final Rejection Detail --}}
    @if(!empty($rejectionStatus) && ($rejectionStatus['count'] > 0 || $rejectionStatus['history']->count() > 0))
        <div class="mb-6">
            {{-- Admin Final Rejection Detail --}}
            @php
                $adminFinalRejection = $rejectionStatus['history']->where('type', 'admin_final')->first();
            @endphp
            @if($adminFinalRejection)
                <x-ui.section-card title="Detail Penolakan Final (Admin)" subtitle="Penolakan terstruktur oleh Admin pada tahap Validasi." class="mb-4">
                    <div class="p-6">
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

            {{-- Rejection History --}}
            @if($rejectionStatus['history']->count() > 0)
                <x-ui.section-card title="Riwayat Penolakan" subtitle="Catatan penolakan asesor dan admin untuk pengajuan ini.">
                    <div class="p-6">
                        <div class="d-flex flex-column gap-4">
                            @foreach($rejectionStatus['history'] as $rejection)
                                <div class="spm-soft-panel">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="fw-bold">
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
                                    @if($rejection->type === 'admin_final' && $rejection->categories)
                                        <div class="mb-2">
                                            <span class="text-muted fs-8">Kategori:</span>
                                            <div class="d-flex flex-wrap gap-1 mt-1">
                                                @foreach($rejection->categories as $cat)
                                                    <x-ui.badge variant="danger">
                                                        {{ config('akreditasi.final_rejection_categories.' . ($cat['category'] ?? ''), $cat['category'] ?? '-') }}
                                                    </x-ui.badge>
                                                @endforeach
                                            </div>
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
                    </div>
                </x-ui.section-card>
            @endif
        </div>
    @endif

    <x-ui.card flush>
        <div class="px-6 pt-5">
            <x-ui.tabs>
                <x-ui.tab wire:click="setTab('profil')" :active="$activeTab === 'profil'">Profil</x-ui.tab>
                <x-ui.tab wire:click="setTab('ipm')" :active="$activeTab === 'ipm'">IPM</x-ui.tab>
                <x-ui.tab wire:click="setTab('sdm')" :active="$activeTab === 'sdm'">SDM</x-ui.tab>
                <x-ui.tab wire:click="setTab('edpm_pesantren')" :active="$activeTab === 'edpm_pesantren'">EDPM</x-ui.tab>
                <x-ui.tab wire:click="setTab('instrumen')" :active="$activeTab === 'instrumen'">NA</x-ui.tab>
                <x-ui.tab wire:click="setTab('laporan_visitasi')" :active="$activeTab === 'laporan_visitasi'">Laporan Visitasi</x-ui.tab>
                @if(count($chainTimeline) > 0)
                    <x-ui.tab wire:click="setTab('riwayat')" :active="$activeTab === 'riwayat'">Riwayat Pengajuan</x-ui.tab>
                @endif
            </x-ui.tabs>
        </div>

        <div class="p-6">
            @if ($activeTab === 'profil')
                <div class="d-flex flex-column gap-6">
                    <x-ui.section-card title="Profil Pesantren" subtitle="Identitas pesantren dan status akses data.">
                        <x-slot:toolbar>
                            @if($pesantren)
                                <x-ui.button
                                    type="button"
                                    wire:click="toggleLock"
                                    wire:loading.attr="disabled"
                                    :variant="$pesantren?->is_locked ? 'warning' : 'light'"
                                    size="sm"
                                >
                                    <x-ui.icon name="shield-tick" class="fs-4 me-1" wire:loading.remove wire:target="toggleLock" />
                                    <span wire:loading.remove wire:target="toggleLock">
                                        {{ $pesantren?->is_locked ? 'Buka Kunci Data' : 'Kunci Data' }}
                                    </span>
                                    <span wire:loading wire:target="toggleLock">Memproses...</span>
                                </x-ui.button>
                            @endif
                        </x-slot:toolbar>

                        <div class="p-6">
                            <div class="row g-5">
                                <x-ui.detail-item label="Nama Pesantren" value="{{ $pesantren->nama_pesantren ?? '-' }}" />
                                <x-ui.detail-item label="NSP" value="{{ $pesantren->ns_pesantren ?? '-' }}" />
                                <x-ui.detail-item label="Alamat" span="2">
                                    <div class="spm-detail-block spm-detail-value-muted">{{ $pesantren->alamat ?? '-' }}</div>
                                </x-ui.detail-item>
                                <x-ui.detail-item label="Kota/Kabupaten" value="{{ $pesantren->kota_kabupaten ?? '-' }}" />
                                <x-ui.detail-item label="Provinsi" value="{{ $pesantren->provinsi ?? '-' }}" />
                                <x-ui.detail-item label="Nama Mudir" value="{{ $pesantren->nama_mudir ?? '-' }}" />
                                <x-ui.detail-item label="Tahun Pendirian" value="{{ $pesantren->tahun_pendirian ?? '-' }}" />
                            </div>
                        </div>
                    </x-ui.section-card>

                    <x-ui.section-card title="Layanan & Fasilitas" subtitle="Unit layanan pendidikan dan luas sarana.">
                        <div class="p-6">
                            <div class="row g-6">
                                <div class="col-lg-7">
                                    @if($pesantren && $pesantren->units && $pesantren->units->count() > 0)
                                        <x-ui.simple-table dense>
                                            <thead>
                                                <tr>
                                                    <th class="ps-4">Unit</th>
                                                    <th class="text-end pe-4">Jumlah Rombel</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($pesantren->units as $unit)
                                                    <tr>
                                                        <td class="ps-4 text-uppercase fw-bold">{{ $unit->unit }}</td>
                                                        <td class="text-end pe-4">
                                                            <x-ui.badge variant="success">{{ $unit->jumlah_rombel }} Rombel</x-ui.badge>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </x-ui.simple-table>
                                    @else
                                        <x-ui.empty-state title="Belum Ada Unit" description="Data unit pendidikan belum diisi." />
                                    @endif
                                </div>

                                <div class="col-lg-5">
                                    <div class="d-flex flex-column gap-4">
                                        <x-ui.stat-card label="Total Luas Tanah" value="{{ $pesantren->luas_tanah ?? '-' }} m2" variant="success">
                                            <x-slot:icon><x-ui.icon name="geolocation" class="fs-2" /></x-slot:icon>
                                        </x-ui.stat-card>
                                        <x-ui.stat-card label="Total Luas Bangunan" value="{{ $pesantren->luas_bangunan ?? '-' }} m2" variant="info">
                                            <x-slot:icon><x-ui.icon name="category" class="fs-2" /></x-slot:icon>
                                        </x-ui.stat-card>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </x-ui.section-card>

                    <x-ui.section-card title="Dokumen Pesantren" subtitle="Dokumen utama dan dokumen pendukung profil.">
                        <div class="p-6">
                            <div class="row g-5">
                                <div class="col-lg-6">
                                    <div class="spm-detail-label mb-3">Dokumen Utama</div>
                                    <div class="spm-document-list">
                                        @foreach($dokumenUtama as $field => $label)
                                            <x-ui.document-item :label="$label" :href="$pesantren && $pesantren->$field ? Storage::url($pesantren->$field) : null" />
                                        @endforeach
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="spm-detail-label mb-3">Dokumen Sekunder</div>
                                    <div class="spm-document-list">
                                        @foreach($dokumenSekunder as $field => $label)
                                            <x-ui.document-item :label="$label" :href="$pesantren && $pesantren->$field ? Storage::url($pesantren->$field) : null" />
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </x-ui.section-card>

                    <x-ui.section-card title="Asesor & Visitasi" subtitle="Tim penilai dan jadwal penilaian.">
                        <div class="p-6">
                            <div class="row g-5">
                                @forelse ($akreditasi->assessments as $assessment)
                                    <x-ui.detail-item label="{{ $assessment->tipe == 1 ? 'Ketua' : 'Anggota' }}" value="{{ $assessment->asesor->user->name ?? '-' }}" />
                                @empty
                                    <div class="col-12">
                                        <x-ui.empty-state title="Belum Ada Asesor" description="Asesor belum ditugaskan untuk pengajuan ini." />
                                    </div>
                                @endforelse

                                @if ($akreditasi->assessments->isNotEmpty())
                                    @php $mainAssessment = $akreditasi->assessments->first(); @endphp
                                    <x-ui.detail-item label="Penilaian Mulai" value="{{ \Carbon\Carbon::parse($mainAssessment->tanggal_mulai)->format('d M Y') }}" />
                                    <x-ui.detail-item label="Penilaian Berakhir" value="{{ \Carbon\Carbon::parse($mainAssessment->tanggal_berakhir)->format('d M Y') }}" />
                                @endif

                                @if ($akreditasi->tgl_visitasi)
                                    <x-ui.detail-item label="Visitasi Mulai" value="{{ \Carbon\Carbon::parse($akreditasi->tgl_visitasi)->format('d M Y') }}" />
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-end justify-content-between gap-3">
                                            <div class="spm-detail-item">
                                                <div class="spm-detail-label">Visitasi Berakhir</div>
                                                <div class="spm-detail-value">
                                                    {{ \Carbon\Carbon::parse($akreditasi->tgl_visitasi_akhir ?? $akreditasi->tgl_visitasi)->format('d M Y') }}
                                                </div>
                                            </div>

                                            @if(in_array($akreditasi->status, [1, 2]))
                                                <x-ui.status-badge variant="secondary">Reschedule Terkunci</x-ui.status-badge>
                                            @else
                                                <x-ui.button type="button" wire:click="openVisitasiEditModal" variant="light" size="sm">
                                                    Reschedule
                                                </x-ui.button>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </x-ui.section-card>
                </div>
            @endif

            @if ($activeTab === 'ipm')
                <x-ui.section-card title="Indikator Pemenuhan Mutlak" subtitle="Status dokumen IPM dari pesantren.">
                    <div class="p-6">
                        <div class="spm-document-list">
                            @foreach ($ipmItems as $field => $label)
                                <x-ui.document-item :label="$label" :href="$ipm && $ipm->$field ? Storage::url($ipm->$field) : null" />
                            @endforeach
                        </div>
                    </div>
                </x-ui.section-card>
            @endif

            @if ($activeTab === 'riwayat' && count($chainTimeline) > 0)
                <x-ui.section-card title="Riwayat Pengajuan" subtitle="Timeline pengajuan akreditasi dalam rantai pengajuan ulang.">
                    <div class="p-6">
                        <x-ui.simple-table dense>
                            <thead>
                                <tr>
                                    <th class="ps-4">#</th>
                                    <th>Tanggal Pengajuan</th>
                                    <th>Status</th>
                                    <th>Alasan Penolakan</th>
                                    <th>Waktu Antar Pengajuan</th>
                                    <th class="text-end pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($chainTimeline as $index => $entry)
                                    @php
                                        $entryStatusVariant = match ((int) $entry->status) {
                                            1 => 'success',
                                            2 => 'danger',
                                            3 => 'warning',
                                            4 => 'info',
                                            default => 'primary',
                                        };
                                        $timeElapsed = null;
                                        if ($index > 0) {
                                            $prevEntry = $chainTimeline[$index - 1];
                                            $timeElapsed = \Carbon\Carbon::parse($prevEntry->created_at)->diffForHumans(\Carbon\Carbon::parse($entry->created_at), true);
                                        }
                                    @endphp
                                    <tr class="{{ $entry->id === $akreditasi->id ? 'bg-light-primary' : '' }}">
                                        <td class="ps-4 fw-bold">{{ $index + 1 }}</td>
                                        <td>{{ \Carbon\Carbon::parse($entry->created_at)->format('d M Y H:i') }}</td>
                                        <td>
                                            <x-ui.badge :variant="$entryStatusVariant">
                                                {{ Akreditasi::getStatusLabel($entry->status) }}
                                            </x-ui.badge>
                                        </td>
                                        <td>{{ (int) $entry->status === 2 ? ($entry->catatan ?? '-') : '-' }}</td>
                                        <td>{{ $timeElapsed ?? '-' }}</td>
                                        <td class="text-end pe-4">
                                            <a href="{{ route('admin.akreditasi-detail', $entry->uuid) }}" class="btn btn-sm btn-light-primary">
                                                <x-ui.icon name="eye" class="fs-4" />
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </x-ui.simple-table>
                    </div>
                </x-ui.section-card>
            @endif

            @if ($activeTab === 'sdm')
                <x-ui.section-card title="Data SDM Pesantren" subtitle="Rekap santri, ustadz, pamong, musyrif, dan tenaga kependidikan.">
                    <div class="p-6">
                        <x-ui.simple-table tableClass="spm-wide-table">
                            <thead>
                                <tr class="text-center">
                                    <th rowspan="2" class="ps-4">No.</th>
                                    <th rowspan="2" class="text-start">Bentuk</th>
                                    <th colspan="2">Santri</th>
                                    <th colspan="2">Ustadz Dirosah</th>
                                    <th colspan="2">Ustadz Non Dirosah</th>
                                    <th colspan="2">Pamong</th>
                                    <th colspan="2">Musyrif/Ah</th>
                                    <th colspan="2" class="pe-4">Tenaga Kependidikan</th>
                                </tr>
                                <tr class="text-center">
                                    @for($i = 0; $i < 6; $i++)
                                        <th>L</th>
                                        <th>P</th>
                                    @endfor
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($levels as $index => $level)
                                    <tr class="text-center">
                                        <td class="ps-4 fw-bold">{{ $index + 1 }}</td>
                                        <td class="text-start text-uppercase fw-bold">{{ $level }}</td>
                                        @foreach($fields as $field)
                                            <td>{{ $sdm[$level]->$field ?? 0 }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="text-center">
                                    <td colspan="2" class="ps-4 text-uppercase text-start">Jumlah</td>
                                    @foreach($fields as $field)
                                        <td>{{ $this->getTotal($field) }}</td>
                                    @endforeach
                                </tr>
                            </tfoot>
                        </x-ui.simple-table>
                    </div>
                </x-ui.section-card>
            @endif

            @if ($activeTab === 'edpm_pesantren')
                <div class="d-flex flex-column gap-6">
                    <x-ui.section-card title="EDPM Pesantren" subtitle="Isian evaluasi diri dan bukti yang dikirim pesantren.">
                        <div class="p-6">
                            <x-ui.simple-table tableClass="spm-edpm-review-table">
                                <thead>
                                    <tr>
                                        <th class="ps-4 w-100px">No Butir</th>
                                        <th>Pernyataan</th>
                                        <th class="text-center w-125px">Isian</th>
                                        <th class="text-center pe-4 w-150px">Bukti</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($komponens as $komponen)
                                        @foreach ($komponen->butirs as $butir)
                                            <tr>
                                                <td class="ps-4 fw-bold text-primary">{{ $butir->nomor_butir }}</td>
                                                <td class="spm-edpm-statement">{{ $butir->butir_pernyataan }}</td>
                                                <td class="text-center">
                                                    <x-ui.badge variant="warning">{{ $pesantrenEvaluasis[$butir->id] }}</x-ui.badge>
                                                </td>
                                                <td class="text-center pe-4">
                                                    @if(!empty($pesantrenLinks[$butir->id]))
                                                        <x-ui.button :href="$pesantrenLinks[$butir->id]" target="_blank" variant="light" size="sm">Bukti</x-ui.button>
                                                    @else
                                                        <x-ui.status-badge variant="secondary">-</x-ui.status-badge>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </x-ui.simple-table>
                        </div>
                    </x-ui.section-card>

                    <x-ui.section-card title="Catatan Kinerja Satuan Pendidikan" subtitle="Catatan pesantren per komponen EDPM.">
                        <div class="p-6">
                            <div class="row g-5">
                                @foreach ($komponens as $komponen)
                                    <div class="col-lg-6">
                                        <div class="spm-soft-panel h-100">
                                            <div class="spm-detail-label">{{ $komponen->nama }}</div>
                                            <div class="spm-detail-value spm-detail-value-muted">
                                                {{ $pesantrenCatatans[$komponen->id] ?: 'Tidak ada catatan.' }}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </x-ui.section-card>
                </div>
            @endif

            @if ($activeTab === 'instrumen')
                <div class="d-flex flex-column gap-6">
                    @if ($akreditasi->status == 3 && (empty($akreditasi->kartu_kendali) || empty($akreditasi->laporan_visitasi_file)))
                        <div class="spm-inline-alert">
                            <span class="symbol symbol-35px">
                                <span class="symbol-label bg-light-warning text-warning">
                                    <x-ui.icon name="timer" class="fs-3" />
                                </span>
                            </span>
                            <div>
                                <div class="spm-inline-alert-title">Kelengkapan Dokumen Wajib</div>
                                <div class="spm-inline-alert-text">
                                    Nilai NV hanya dapat disimpan apabila Kartu Kendali dan Laporan Visitasi telah diunggah.
                                </div>
                            </div>
                        </div>
                    @endif

                    <x-ui.section-card title="Nilai Akhir" subtitle="Perbandingan NA asesor, NK, NV admin, catatan butir, dan rekomendasi.">
                        <div class="p-6">
                            <x-ui.simple-table tableClass="spm-score-table">
                                <thead>
                                    <tr>
                                        <th class="ps-4 w-150px">Komponen</th>
                                        <th class="text-center w-80px">No SK</th>
                                        <th class="text-center w-90px">No Butir</th>
                                        <th>Pernyataan</th>
                                        <th class="text-center w-80px">NA 1</th>
                                        <th class="text-center w-80px">NA 2</th>
                                        <th class="text-center w-80px">NK</th>
                                        <th class="text-center w-110px">NV</th>
                                        <th class="w-220px">Catatan Butir</th>
                                        <th class="pe-4 w-260px">Rekomendasi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($komponens as $komponen)
                                        @php $butirsCount = count($komponen->butirs); @endphp
                                        @foreach ($komponen->butirs as $index => $butir)
                                            <tr>
                                                @if ($index === 0)
                                                    <td rowspan="{{ $butirsCount }}" class="ps-4 fw-bold text-primary text-uppercase align-middle">
                                                        {{ $komponen->nama }}
                                                    </td>
                                                @endif
                                                <td class="text-center text-muted">{{ $butir->no_sk }}</td>
                                                <td class="text-center fw-bold">{{ $butir->nomor_butir }}</td>
                                                <td class="spm-edpm-statement">{{ $butir->butir_pernyataan }}</td>
                                                <td class="text-center fw-bold">{{ $asesor1Evaluasis[$butir->id] ?? '' }}</td>
                                                <td class="text-center fw-bold">{{ $asesor2Evaluasis[$butir->id] ?? '' }}</td>
                                                <td class="text-center fw-bold text-warning">{{ $asesor1Nks[$butir->id] ?? '' }}</td>
                                                <td class="text-center">
                                                    @if ($akreditasi->status == 3)
                                                        <x-ui.select
                                                            model="adminNvs.{{ $butir->id }}"
                                                            modifier="live"
                                                            :options="['1' => '1', '2' => '2', '3' => '3', '4' => '4']"
                                                            placeholder="Pilih"
                                                            size="sm"
                                                            class="spm-score-control mx-auto"
                                                        />
                                                        @error('adminNvs.' . $butir->id)
                                                            <div class="invalid-feedback d-block fs-9">{{ $message }}</div>
                                                        @enderror
                                                    @else
                                                        <x-ui.badge variant="primary">{{ $adminNvs[$butir->id] ?? '' }}</x-ui.badge>
                                                    @endif
                                                </td>
                                                <td class="fs-8 text-muted">{{ $asesor1ButirCatatans[$butir->id] ?? '' }}</td>
                                                @if ($index === 0)
                                                    <td rowspan="{{ $butirsCount }}" class="pe-4 fs-8 text-gray-700 align-top">
                                                        {!! $asesor1Catatans[$komponen->id] ?? '-' !!}
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </x-ui.simple-table>
                        </div>
                    </x-ui.section-card>

                    @if ($akreditasi->status == 3)
                        <div class="spm-action-panel d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4">
                            <div>
                                <h3 class="spm-card-title mb-1">Nilai Verifikasi (NV)</h3>
                                <div class="text-muted fw-semibold fs-7">Simpan nilai verifikasi setelah semua butir lengkap.</div>
                            </div>
                            <x-ui.button type="button" @click="confirmSaveNV($wire)" wire:loading.attr="disabled" variant="primary">
                                <span wire:loading.remove wire:target="saveAdminNv">Simpan NV</span>
                                <span wire:loading wire:target="saveAdminNv">Menyimpan...</span>
                            </x-ui.button>
                        </div>
                    @endif

                    @if ($akreditasi->status == 3 || $akreditasi->status == 1)
                        <x-ui.section-card title="Ringkasan Data Penilaian" subtitle="Perhitungan skor komponen dan hasil akhir.">
                            <div class="p-6">
                                @php
                                    $totalCmaks = 0;
                                    $totalCI = 0;
                                    $totalBK = 0;
                                    $totalSkorKomponen = 0;
                                    $grandTotalSkor = 0;

                                    $bobotKomponen = [
                                        'MUTU LULUSAN' => 35,
                                        'PROSES PEMBELAJARAN' => 29,
                                        'MUTU USTAZ' => 18,
                                        'MANAJEMEN PESANTREN' => 18,
                                        'INDIKATOR PEMENUHAN RELATIF' => 97,
                                    ];

                                    $iprNullComponents = $komponens->filter(function($k) { return is_null($k->ipr); });
                                    $iprNotNullComponents = $komponens->filter(function($k) { return !is_null($k->ipr); });

                                    $totalSkorIprNull = 0;
                                    foreach ($iprNullComponents as $k) {
                                        $b = $bobotKomponen[$k->nama] ?? 0;
                                        $c_total = count($k->butirs) * 4;
                                        $c_ci = 0;
                                        foreach ($k->butirs as $butir) {
                                            $c_ci += (int)($adminNvs[$butir->id] ?? 0);
                                        }
                                        if ($c_total > 0) {
                                            $totalSkorIprNull += round(($c_ci / $c_total) * $b);
                                        }
                                    }
                                @endphp

                                <x-ui.simple-table tableClass="spm-wide-table">
                                    <thead>
                                        <tr>
                                            <th class="ps-4">Komponen</th>
                                            <th class="text-center">Cmaks</th>
                                            <th class="text-center">CI</th>
                                            <th class="text-center">BK</th>
                                            <th class="text-center">Skor Komponen</th>
                                            <th class="text-center pe-4">Total Skor</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($komponens as $index => $komponen)
                                            @php
                                                $totalButir = count($komponen->butirs);
                                                $cmaksKomponen = $totalButir * 4;
                                                $sumNvKomponen = 0;
                                                foreach ($komponen->butirs as $butir) {
                                                    $sumNvKomponen += (int) ($adminNvs[$butir->id] ?? 0);
                                                }
                                                $bkValue = $bobotKomponen[$komponen->nama] ?? 0;
                                                $isIpr = !is_null($komponen->ipr);
                                                $faktor = $isIpr ? 100 : $bkValue;
                                                $skorKomponen = $cmaksKomponen > 0 ? round(($sumNvKomponen / $cmaksKomponen) * $faktor) : 0;
                                            @endphp

                                            <tr>
                                                <td class="ps-4 fw-bold">{{ $komponen->nama }}</td>
                                                <td class="text-center fw-bold">{{ $cmaksKomponen }}</td>
                                                <td class="text-center fw-bold text-primary">{{ $sumNvKomponen }}</td>
                                                <td class="text-center fw-bold text-warning">{{ $bkValue }}</td>
                                                <td class="text-center fw-bold">{{ $skorKomponen }}</td>

                                                @if ($index === 0)
                                                    <td rowspan="{{ $iprNullComponents->count() }}" class="text-center pe-4 fw-bold fs-4 text-success align-middle">
                                                        {{ $totalSkorIprNull }}
                                                    </td>
                                                @elseif ($index === $iprNullComponents->count())
                                                    <td class="text-center pe-4 fw-bold fs-4 text-success align-middle">
                                                        {{ $skorKomponen }}
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </x-ui.simple-table>

                                @php
                                    $totalSkorIprNotNull = 0;
                                    foreach ($iprNotNullComponents as $k) {
                                        $c_total = count($k->butirs) * 4;
                                        $c_ci = 0;
                                        foreach ($k->butirs as $butir) {
                                            $c_ci += (int)($adminNvs[$butir->id] ?? 0);
                                        }
                                        if ($c_total > 0) {
                                            $totalSkorIprNotNull += round(($c_ci / $c_total) * 100);
                                        }
                                    }

                                    $nilaiAkreditasi = round((0.7 * $totalSkorIprNull) + (0.3 * $totalSkorIprNotNull));
                                    $peringkat = 'NA';
                                    if ($nilaiAkreditasi >= 86) {
                                        $peringkat = 'Unggul';
                                    } elseif ($nilaiAkreditasi >= 70) {
                                        $peringkat = 'Baik';
                                    } elseif ($nilaiAkreditasi >= 0) {
                                        $peringkat = 'Cukup';
                                    }
                                @endphp

                                <div class="row g-5 mt-2">
                                    <div class="col-md-6">
                                        <div class="spm-result-metric">
                                            <div class="spm-detail-label">Nilai Akreditasi</div>
                                            <div class="fs-2 fw-bold text-primary">{{ $nilaiAkreditasi }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="spm-result-metric">
                                            <div class="spm-detail-label">Peringkat Akreditasi</div>
                                            <div class="fs-2 fw-bold text-gray-900">{{ $peringkat }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </x-ui.section-card>
                    @endif

                    @if ($akreditasi->status == 3)
                        <div class="row g-6">
                            <div class="col-lg-6">
                                <x-ui.section-card title="Setujui Akreditasi" subtitle="Lengkapi SK dan sertifikat final.">
                                    <form @submit.prevent="confirmApprove($wire)" class="p-6">
                                        <x-ui.form-field label="Nomor SK" for="nomor_sk" :error="$errors->get('nomor_sk')">
                                            <x-ui.input model="nomor_sk" id="nomor_sk" placeholder="Masukkan nomor SK resmi..." required />
                                        </x-ui.form-field>

                                        <x-ui.form-field label="Unggah Sertifikat (PDF)" for="sertifikat_file" :error="$errors->get('sertifikat_file')">
                                            <x-ui.file-upload
                                                model="sertifikat_file"
                                                id="sertifikat_file"
                                                accept="application/pdf"
                                                :file="$sertifikat_file"
                                                placeholder="Pilih file sertifikat"
                                                hint="PDF maksimal 10MB"
                                            />
                                            <div wire:loading wire:target="sertifikat_file" class="text-primary fw-bold fs-8 mt-2">Mengunggah...</div>
                                        </x-ui.form-field>

                                        <div class="row g-5">
                                            <div class="col-md-6">
                                                <x-ui.form-field label="Mulai Berlaku" for="masa_berlaku" :error="$errors->get('masa_berlaku')">
                                                    <x-ui.input model="masa_berlaku" id="masa_berlaku" type="date" required />
                                                </x-ui.form-field>
                                            </div>
                                            <div class="col-md-6">
                                                <x-ui.form-field label="Akhir Berlaku" for="masa_berlaku_akhir" :error="$errors->get('masa_berlaku_akhir')">
                                                    <x-ui.input model="masa_berlaku_akhir" id="masa_berlaku_akhir" type="date" required />
                                                </x-ui.form-field>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-end">
                                            <x-ui.button type="submit" variant="success" wire:loading.attr="disabled">
                                                <span wire:loading.remove wire:target="approve">Setujui & Simpan</span>
                                                <span wire:loading wire:target="approve">Memproses...</span>
                                            </x-ui.button>
                                        </div>
                                    </form>
                                </x-ui.section-card>
                            </div>

                            <div class="col-lg-6">
                                <x-ui.section-card title="Tolak Akreditasi" subtitle="Pilih kategori dan berikan penjelasan per kategori.">
                                    <form wire:submit="reject" class="p-6">
                                        <div class="mb-4">
                                            <div class="spm-detail-label mb-2">Kategori Penolakan <span class="text-danger">*</span></div>

                                            @foreach($rejectionCategories as $index => $entry)
                                                <div class="spm-soft-panel mb-3">
                                                    <div class="d-flex align-items-start justify-content-between gap-2">
                                                        <div class="flex-grow-1">
                                                            <select wire:model="rejectionCategories.{{ $index }}.category" class="form-select form-select-sm mb-2">
                                                                <option value="">-- Pilih Kategori --</option>
                                                                @foreach(config('akreditasi.final_rejection_categories', []) as $key => $label)
                                                                    <option value="{{ $key }}">{{ $label }}</option>
                                                                @endforeach
                                                            </select>
                                                            @error("rejectionCategories.{$index}.category")
                                                                <div class="text-danger fs-8">{{ $message }}</div>
                                                            @enderror

                                                            <textarea
                                                                wire:model="rejectionCategories.{{ $index }}.explanation"
                                                                class="form-control form-control-sm"
                                                                rows="3"
                                                                placeholder="Penjelasan detail (min 10 karakter)..."
                                                            ></textarea>
                                                            @error("rejectionCategories.{$index}.explanation")
                                                                <div class="text-danger fs-8">{{ $message }}</div>
                                                            @enderror
                                                        </div>
                                                        <x-ui.button type="button" variant="light-danger" size="sm" wire:click="removeRejectionCategory({{ $index }})">
                                                            <x-ui.icon name="cross" class="fs-6" />
                                                        </x-ui.button>
                                                    </div>
                                                </div>
                                            @endforeach

                                            @error('rejectionCategories')
                                                <div class="text-danger fs-8 mb-2">{{ $message }}</div>
                                            @enderror

                                            <x-ui.button type="button" wire:click="addRejectionCategory" variant="light" size="sm">
                                                + Tambah Kategori
                                            </x-ui.button>
                                        </div>

                                        <div class="d-flex justify-content-end">
                                            <x-ui.button type="submit" variant="danger" wire:loading.attr="disabled">
                                                <span wire:loading.remove wire:target="reject">Tolak Pengajuan</span>
                                                <span wire:loading wire:target="reject">Memproses...</span>
                                            </x-ui.button>
                                        </div>
                                    </form>
                                </x-ui.section-card>
                            </div>
                        </div>
                    @endif

                    <div class="spm-scroll-actions">
                        <x-ui.button
                            type="button"
                            variant="primary"
                            class="btn-icon"
                            title="Scroll ke atas"
                            onclick="document.getElementById('main-content-scroll')?.scrollTo({top: 0, behavior: 'smooth'})"
                        >
                            <x-ui.icon name="arrow-up" class="fs-2" />
                        </x-ui.button>
                        <x-ui.button
                            type="button"
                            variant="primary"
                            class="btn-icon"
                            title="Scroll ke bawah"
                            onclick="const el = document.getElementById('main-content-scroll'); el?.scrollTo({top: el.scrollHeight, behavior: 'smooth'})"
                        >
                            <x-ui.icon name="arrow-down" class="fs-2" />
                        </x-ui.button>
                    </div>
                </div>
            @endif

            @if($activeTab === 'laporan_visitasi')
                <div class="d-flex flex-column gap-6">
                    @if($akreditasi->status >= 3)
                        <x-ui.section-card title="Kartu Kendali" subtitle="Dokumen kontrol validasi dari pesantren.">
                            <div class="p-6">
                                <div class="spm-document-list">
                                    <x-ui.document-item
                                        label="Dokumen Kartu Kendali"
                                        :href="$akreditasi->kartu_kendali ? Storage::url($akreditasi->kartu_kendali) : null"
                                        description="Diunggah oleh pesantren untuk validasi."
                                    />
                                </div>
                            </div>
                        </x-ui.section-card>
                    @endif

                    <x-ui.section-card title="Laporan Hasil Visitasi" subtitle="Dokumen laporan dari ketua dan anggota asesor.">
                        <div class="p-6">
                            <div class="row g-5">
                                <div class="col-lg-6">
                                    <div class="spm-document-list">
                                        <x-ui.document-item
                                            label="Laporan Ketua"
                                            :href="$akreditasi->laporan_visitasi_file ? Storage::url($akreditasi->laporan_visitasi_file) : null"
                                        />
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="spm-document-list">
                                        <x-ui.document-item
                                            label="Laporan Anggota"
                                            :href="$akreditasi->laporan_visitasi_file_2 ? Storage::url($akreditasi->laporan_visitasi_file_2) : null"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </x-ui.section-card>
                </div>
            @endif
        </div>

        <x-ui.modal name="visitasi-edit-modal" focusable>
            <form x-on:submit.prevent="confirmRescheduleVisitasi($wire)">
                <x-ui.modal-header
                    title="Reschedule Jadwal Visitasi"
                    subtitle="Perbarui jadwal visitasi dalam rentang penilaian."
                    icon="timer"
                />

                <x-ui.modal-body>
                    <div class="row g-5">
                        <div class="col-md-6">
                            <x-ui.form-field label="Tanggal Mulai Visitasi" for="tgl_visitasi" :error="$errors->get('tgl_visitasi')">
                                <x-ui.input model="tgl_visitasi" id="tgl_visitasi" type="date" />
                            </x-ui.form-field>
                        </div>

                        <div class="col-md-6">
                            <x-ui.form-field label="Tanggal Akhir Visitasi" for="tgl_visitasi_akhir" :error="$errors->get('tgl_visitasi_akhir')">
                                <x-ui.input model="tgl_visitasi_akhir" id="tgl_visitasi_akhir" type="date" />
                            </x-ui.form-field>
                        </div>
                    </div>
                </x-ui.modal-body>

                <x-ui.modal-footer>
                    <x-ui.button type="button" variant="light" x-on:click="$dispatch('close')">
                        Batal
                    </x-ui.button>

                    <x-ui.button type="submit" variant="primary">
                        Simpan Perubahan
                    </x-ui.button>
                </x-ui.modal-footer>
            </form>
        </x-ui.modal>
    </x-ui.card>
</x-ui.page>
