<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component {
    public $akreditasi;
    public $pesantren;
    public $ipm;
    public $sdm;
    public $komponens;
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

    public $pesantrenEvaluasis = [];
    public $pesantrenCatatans = [];
    public $pesantrenLinks = [];

    public $asesor1Evaluasis = [];
    public $asesor2Evaluasis = [];
    public $asesor1Nks = [];
    public $adminNvs = [];
    public $asesorButirCatatans = [];
    public $asesorCatatans = [];

    #[Url]
    public $activeTab = 'profil';
    public $kartu_kendali_file;
    public $visitasiTemplate;

    use WithFileUploads;

    public function mount($uuid)
    {
        $pesantrenService = app(\App\Services\PesantrenService::class);
        $data = $pesantrenService->getAkreditasiDetail($uuid, Auth::id());

        $this->akreditasi = $data['akreditasi'];
        $this->pesantren = $data['pesantren'];
        $this->ipm = $data['ipm'];
        $this->sdm = $data['sdm'];
        $this->komponens = $data['komponens'];
        $this->visitasiTemplate = $data['visitasiTemplate'];

        if ($this->pesantren && $this->pesantren->relationLoaded('units')) {
            $this->levels = $this->pesantren->units->pluck('unit')->toArray();
        }

        // Pesantren EDPM
        $this->pesantrenEvaluasis = $data['pesantren_edpm']['evaluasis'];
        $this->pesantrenLinks = $data['pesantren_edpm']['links'];
        $this->pesantrenCatatans = $data['pesantren_edpm']['catatans']->toArray();

        // Assessor 1
        if (!empty($data['asesor1'])) {
            $this->asesor1Evaluasis = $data['asesor1']['evaluasis'];
            $this->asesor1Nks = $data['asesor1']['nks'];
            $this->adminNvs = $data['asesor1']['nvs'];
            $this->asesorButirCatatans = $data['asesor1']['butir_catatans'];
            $this->asesorCatatans = $data['asesor1']['catatans'];
        }

        // Assessor 2
        if (!empty($data['asesor2'])) {
            $this->asesor2Evaluasis = $data['asesor2']['evaluasis'];
        }

        // Ensure all components have entries in catatans
        foreach ($this->komponens as $komponen) {
            if (!isset($this->pesantrenCatatans[$komponen->id])) {
                $this->pesantrenCatatans[$komponen->id] = '';
            }
        }
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function getTotal($field)
    {
        $total = 0;
        foreach ($this->levels as $level) {
            $total += (int)($this->sdm[$level]->$field ?? 0);
        }
        return $total;
    }

    public function uploadKartuKendali()
    {
        $pesantrenService = app(\App\Services\PesantrenService::class);
        
        if ($this->akreditasi->status != 3) {
            return;
        }

        $this->validate([
            'kartu_kendali_file' => 'required|file|mimes:pdf,docx|max:5120',
        ], [
            'kartu_kendali_file.required' => 'File Kartu Kendali wajib diunggah.',
            'kartu_kendali_file.mimes' => 'Format file harus PDF atau DOCX.',
            'kartu_kendali_file.max' => 'Ukuran file maksimal 5MB.',
        ]);

        $path = $this->kartu_kendali_file->store('akreditasi/kartu_kendali', 'public');

        $pesantrenService->uploadKartuKendali($this->akreditasi->id, $path);

        $this->reset(['kartu_kendali_file']);

        $this->dispatch(
            'notification-received',
            type: 'success',
            title: 'Berhasil!',
            message: 'Kartu Kendali berhasil diunggah.'
        );
    }
}; ?>

<div class="py-12" x-data="akreditasiPesantren()">
    <x-slot name="header">{{ __('Akreditasi Detail') }}</x-slot>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Detail Pengajuan Akreditasi</h2>
                        <p class="text-sm text-gray-500">Status:
                            <span class="font-semibold {{ \App\Models\Akreditasi::getStatusBadgeClass($akreditasi->status) }} px-2 py-0.5 rounded text-[10px]">
                                {{ \App\Models\Akreditasi::getStatusLabel($akreditasi->status) }}
                            </span>
                        </p>
                    </div>
                    <a href="{{ route('pesantren.akreditasi') }}" class="text-indigo-600 hover:text-indigo-900 font-medium">&larr; Kembali</a>
                </div>

                <!-- Tabs -->
                <div class="mb-4 border-b border-gray-200">
                    <ul class="flex flex-wrap -mb-px text-sm font-medium text-center text-gray-500">
                        <li class="me-2">
                            <button wire:click="setTab('profil')" class="inline-block p-4 border-b-2 rounded-t-lg {{ $activeTab === 'profil' ? 'text-indigo-600 border-indigo-600' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">Profil</button>
                        </li>
                        <li class="me-2">
                            <button wire:click="setTab('ipm')" class="inline-block p-4 border-b-2 rounded-t-lg {{ $activeTab === 'ipm' ? 'text-indigo-600 border-indigo-600' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">IPM</button>
                        </li>
                        <li class="me-2">
                            <button wire:click="setTab('sdm')" class="inline-block p-4 border-b-2 rounded-t-lg {{ $activeTab === 'sdm' ? 'text-indigo-600 border-indigo-600' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">SDM</button>
                        </li>
                        <li class="me-2">
                            <button wire:click="setTab('edpm')" class="inline-block p-4 border-b-2 rounded-t-lg {{ $activeTab === 'edpm' ? 'text-indigo-600 border-indigo-600' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">EDPM</button>
                        </li>
                        @if($akreditasi->status == 1 || $akreditasi->status == 2)
                        <li class="me-2">
                            <button wire:click="setTab('hasil')" class="inline-block p-4 border-b-2 rounded-t-lg {{ $activeTab === 'hasil' ? 'text-indigo-600 border-indigo-600' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">Hasil Penilaian</button>
                        </li>
                        @endif

                        @if($akreditasi->status == 3)
                        <li class="me-2">
                            <button wire:click="setTab('kartu')" class="inline-block p-4 border-b-2 rounded-t-lg {{ $activeTab === 'kartu' ? 'text-indigo-600 border-indigo-600' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">Kartu Kendali</button>
                        </li>
                        @endif
                    </ul>
                </div>

                <!-- Tab Contents -->
                <div class="mt-6">
                    @if ($activeTab === 'profil')
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 p-6 rounded-lg">
                            <div>
                                <p class="text-xs font-bold text-gray-500 uppercase">Nama Pesantren</p>
                                <p class="text-gray-900">{{ $pesantren->nama_pesantren ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-gray-500 uppercase">NSP</p>
                                <p class="text-gray-900">{{ $pesantren->ns_pesantren ?? '-' }}</p>
                            </div>
                            <div class="md:col-span-2">
                                <p class="text-xs font-bold text-gray-500 uppercase">Alamat</p>
                                <p class="text-gray-900">{{ $pesantren->alamat ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-gray-500 uppercase">Kota/Kabupaten</p>
                                <p class="text-gray-900">{{ $pesantren->kota_kabupaten ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-gray-500 uppercase">Provinsi</p>
                                <p class="text-gray-900">{{ $pesantren->provinsi ?? '-' }}</p>
                            </div>
                            @if($akreditasi->tgl_visitasi)
                            <div class="md:col-span-2 pt-4 border-t border-gray-100 mt-2">
                                <p class="text-xs font-bold text-indigo-500 uppercase flex items-center gap-1.5 mb-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    Jadwal Visitasi
                                </p>
                                <p class="text-indigo-700 font-extrabold text-lg">
                                    {{ \Carbon\Carbon::parse($akreditasi->tgl_visitasi)->format('d F Y') }}
                                    @if($akreditasi->tgl_visitasi_akhir && $akreditasi->tgl_visitasi != $akreditasi->tgl_visitasi_akhir)
                                    - {{ \Carbon\Carbon::parse($akreditasi->tgl_visitasi_akhir)->format('d F Y') }}
                                    @endif
                                </p>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if ($activeTab === 'ipm')
                    <div class="space-y-4">
                        @php
                        $ipmItems = [
                        'nsp_file' => '1. Izin operasional Kementerian Agama (NSP)',
                        'lulus_santri_file' => '2. Pernah meluluskan santri / memiliki santri kelas akhir',
                        'kurikulum_file' => '3. Menyelenggarakan kurikulum Dirasah Islamiyah',
                        'buku_ajar_file' => '4. Menggunakan buku ajar terbitan LP2 PPM',
                        ];
                        @endphp
                        @foreach ($ipmItems as $field => $label)
                        <div class="p-4 border rounded-lg bg-gray-50 flex justify-between items-center">
                            <span class="text-sm text-gray-700 font-medium">{{ $label }}</span>
                            <div>
                                @if ($ipm && $ipm->$field)
                                <a href="{{ Storage::url($ipm->$field) }}" target="_blank" class="bg-indigo-100 text-indigo-700 px-3 py-1 rounded text-xs font-bold hover:bg-indigo-200">Lihat Dokumen</a>
                                @else
                                <span class="text-red-500 text-xs italic">Belum diunggah</span>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    @if ($activeTab === 'sdm')
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse border border-gray-300 text-xs md:text-sm">
                            <thead class="bg-gray-100 uppercase font-bold text-[10px]">
                                <tr>
                                    <th rowspan="2" class="border border-gray-300 px-2 py-2">NO.</th>
                                    <th rowspan="2" class="border border-gray-300 px-2 py-2">BENTUK</th>
                                    <th colspan="2" class="border border-gray-300 px-2 py-1 bg-green-50">SANTRI</th>
                                    <th colspan="2" class="border border-gray-300 px-2 py-1 bg-blue-50">USTADZ DIROSAH</th>
                                    <th colspan="2" class="border border-gray-300 px-2 py-1 bg-indigo-50">USTADZ NON DIROSAH</th>
                                    <th colspan="2" class="border border-gray-300 px-2 py-1 bg-yellow-50">PAMONG</th>
                                    <th colspan="2" class="border border-gray-300 px-2 py-1 bg-orange-50">MUSYRIF/AH</th>
                                    <th colspan="2" class="border border-gray-300 px-2 py-1 bg-purple-50">TENAGA KEPENDIDIKAN</th>
                                </tr>
                                <tr class="bg-gray-50 text-center">
                                    <th class="border border-gray-300">L</th>
                                    <th class="border border-gray-300">P</th>
                                    <th class="border border-gray-300">L</th>
                                    <th class="border border-gray-300">P</th>
                                    <th class="border border-gray-300">L</th>
                                    <th class="border border-gray-300">P</th>
                                    <th class="border border-gray-300">L</th>
                                    <th class="border border-gray-300">P</th>
                                    <th class="border border-gray-300">L</th>
                                    <th class="border border-gray-300">P</th>
                                    <th class="border border-gray-300">L</th>
                                    <th class="border border-gray-300">P</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($levels as $index => $level)
                                <tr class="text-center">
                                    <td class="border border-gray-300 px-2 py-1 font-bold">{{ $index + 1 }}</td>
                                    <td class="border border-gray-300 px-2 py-1 font-bold text-left uppercase">{{ $level }}</td>
                                    @foreach($fields as $field)
                                    <td class="border border-gray-300 px-2 py-1">{{ $sdm[$level]->$field ?? 0 }}</td>
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-blue-50 font-bold text-center">
                                <tr>
                                    <td colspan="2" class="border border-gray-300 px-4 py-2 uppercase">JUMLAH</td>
                                    @foreach($fields as $field)
                                    <td class="border border-gray-300 px-2 py-2">{{ $this->getTotal($field) }}</td>
                                    @endforeach
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @endif

                    @if ($activeTab === 'edpm')
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse border border-gray-300 text-xs md:text-sm">
                            <thead class="bg-gray-100 uppercase">
                                <tr>
                                    <th class="border border-gray-300 px-2 py-2">No Butir</th>
                                    <th class="border border-gray-300 px-4 py-2 text-left">Pernyataan</th>
                                    <th class="border border-gray-300 px-4 py-2">Isian Pesantren</th>
                                    <th class="border border-gray-300 px-4 py-2">Bukti Pesantren</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($komponens as $komponen)
                                @foreach ($komponen->butirs as $butir)
                                <tr>
                                    <td class="border border-gray-300 px-2 py-2 text-center font-bold">{{ $butir->nomor_butir }}</td>
                                    <td class="border border-gray-300 px-4 py-2">{{ $butir->butir_pernyataan }}</td>
                                    <td class="border border-gray-300 px-4 py-2 font-medium bg-yellow-50 text-indigo-700 text-center">{{ $pesantrenEvaluasis[$butir->id] ?? '-' }}</td>
                                    <td class="border border-gray-300 px-4 py-2 text-center text-[10px]">
                                        @if(!empty($pesantrenLinks[$butir->id]))
                                        <a href="{{ $pesantrenLinks[$butir->id] }}" target="_blank" class="text-indigo-600 font-bold hover:underline break-all uppercase" title="{{ $pesantrenLinks[$butir->id] }}">LIHAT BUKTI</a>
                                        @else
                                        <span class="text-gray-400 italic">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-8 space-y-4">
                        <h3 class="text-sm font-bold text-gray-700 border-l-4 border-gray-400 pl-3 uppercase">
                            Catatan Kinerja Satuan Pendidikan (Per Komponen)
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach ($komponens as $komponen)
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">{{ $komponen->nama }}</h4>
                                <div class="text-sm text-gray-800 leading-relaxed">
                                    {{ $pesantrenCatatans[$komponen->id] ?: 'Tidak ada catatan.' }}
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if ($activeTab === 'hasil')
                    <div class="space-y-6">
                        @if($akreditasi->status == 1)
                        <div class="bg-green-50 border border-green-200 p-6 rounded-lg">
                            <h3 class="text-lg font-bold text-green-800 mb-4">Hasil Akreditasi Akhir</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <p class="text-xs font-bold text-green-600 uppercase">Nilai Akhir</p>
                                    <p class="text-3xl font-black text-green-900">{{ $akreditasi->nilai }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-green-600 uppercase">Peringkat</p>
                                    <p class="text-3xl font-black text-green-900">{{ $akreditasi->peringkat }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-green-600 uppercase">Nomor SK</p>
                                    <p class="text-gray-900 font-bold">{{ $akreditasi->nomor_sk }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-green-600 uppercase">Masa Berlaku</p>
                                    <p class="text-gray-900 font-bold">
                                        {{ \Carbon\Carbon::parse($akreditasi->masa_berlaku)->format('d F Y') }}
                                        @if($akreditasi->masa_berlaku_akhir && $akreditasi->masa_berlaku != $akreditasi->masa_berlaku_akhir)
                                        - {{ \Carbon\Carbon::parse($akreditasi->masa_berlaku_akhir)->format('d F Y') }}
                                        @endif
                                    </p>
                                </div>
                                @if($akreditasi->sertifikat_path)
                                <div class="md:col-span-2">
                                    <a href="{{ Storage::url($akreditasi->sertifikat_path) }}" target="_blank" class="inline-flex items-center px-6 py-2.5 bg-emerald-600 border border-transparent rounded-2xl font-bold text-xs text-white uppercase tracking-widest transition duration-150">
                                        Unduh Sertifikat
                                    </a>
                                </div>
                                @endif
                            </div>
                        </div>
                        @elseif($akreditasi->status == 2)
                        <div class="bg-red-50 border border-red-200 p-6 rounded-lg">
                            <h3 class="text-lg font-bold text-red-800 mb-2">Pengajuan Ditolak</h3>
                            <p class="text-gray-700">Catatan: {{ $akreditasi->catatan }}</p>
                        </div>
                        @endif

                        <div class="mt-8 bg-gradient-to-r from-indigo-50 to-purple-50 p-6 rounded-lg border border-indigo-200">
                            <h3 class="text-lg font-bold text-indigo-900 mb-4 border-b-2 border-indigo-300 pb-2">
                                DATA PENILAIAN
                            </h3>

                            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="border border-gray-300 px-3 py-2 text-left font-bold">Komponen</th>

                                            <th class="border border-gray-300 px-3 py-2 text-left font-bold">Catatan Rekomendasi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
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
                                        $skorKomponen = 0;
                                        if ($cmaksKomponen > 0) {
                                        $skorKomponen = round(($sumNvKomponen / $cmaksKomponen) * $faktor);
                                        }
                                        @endphp

                                        <tr class="hover:bg-gray-50 text-center">
                                            <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700 text-left uppercase">
                                                {{ $komponen->nama }}
                                            </td>


                                            <td class="border border-gray-300 px-3 py-2 text-left italic text-gray-600">
                                                {!! $asesorCatatans[$komponen->id] ?? '-' !!}
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if ($activeTab === 'kartu')
                    <div class="space-y-6">
                        <div class="bg-indigo-50 border border-indigo-200 p-8 rounded-2xl shadow-sm">
                            <div class="flex items-center gap-4 mb-6">
                                <div class="h-12 w-12 bg-indigo-600 rounded-xl flex items-center justify-center text-white shadow-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-indigo-900">Instruksi Unggah Kartu Kendali</h3>
                                    <p class="text-sm text-indigo-600">Silakan ikuti langkah-langkah di bawah ini untuk menyelesaikan proses validasi.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 relative">
                                <!-- Step 1 -->
                                <div class="bg-white p-6 rounded-xl border border-indigo-100 shadow-sm relative z-10">
                                    <span class="absolute -top-3 -left-3 h-8 w-8 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold shadow-md">1</span>
                                    <h4 class="font-bold text-gray-900 mb-2">Unduh Berkas</h4>
                                    <p class="text-xs text-gray-600 mb-4 leading-relaxed">Admin telah mengunggah Kartu Kendali Anda. Silakan unduh berkas tersebut di menu dokumen.</p>
                                    <a href="{{ route('documents.index', ['doc' => 'all']) }}" class="inline-flex items-center text-[10px] font-bold text-indigo-600 hover:text-indigo-800 gap-1 group">
                                        Buka Menu Dokumen
                                        <svg class="w-3 h-3 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                        </svg>
                                    </a>
                                </div>

                                <!-- Step 2 -->
                                <div class="bg-white p-6 rounded-xl border border-indigo-100 shadow-sm relative z-10">
                                    <span class="absolute -top-3 -left-3 h-8 w-8 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold shadow-md">2</span>
                                    <h4 class="font-bold text-gray-900 mb-2">Tinjau Dokumen</h4>
                                    <p class="text-xs text-gray-600 leading-relaxed">Pastikan seluruh data dan tanda tangan pada Kartu Kendali sudah sesuai dengan hasil visitasi yang telah dilaksanakan.</p>
                                </div>

                                <!-- Step 3 -->
                                <div class="bg-white p-6 rounded-xl border border-indigo-100 shadow-sm relative z-10">
                                    <span class="absolute -top-3 -left-3 h-8 w-8 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold shadow-md">3</span>
                                    <h4 class="font-bold text-gray-900 mb-2">Unggah</h4>

                                    @if($akreditasi->status == 3)
                                    @if($akreditasi->kartu_kendali && !$errors->has('kartu_kendali_file'))
                                    <div class="p-6 rounded-xl border bg-emerald-50 border-emerald-100 flex flex-col items-center gap-4 text-center">
                                        <div class="h-10 w-10 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center shadow-sm">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-[11px] font-black text-emerald-900 uppercase tracking-wider">Berhasil Diunggah</p>
                                            <p class="text-[10px] text-emerald-600 mt-1 font-medium leading-relaxed">Kartu Kendali sudah tersimpan dan sedang divalidasi oleh Admin.</p>
                                        </div>
                                        <a href="{{ Storage::url($akreditasi->kartu_kendali) }}" target="_blank" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-black uppercase tracking-widest py-2.5 rounded-xl transition-all shadow-md active:scale-95 text-center">
                                            Lihat Dokumen
                                        </a>
                                    </div>
                                    @else
                                    <div class="space-y-4">
                                        <div>
                                            <input wire:model="kartu_kendali_file" type="file" id="kartu_kendali_file" class="block w-full text-[10px] text-gray-900 border border-gray-200 rounded-lg cursor-pointer bg-gray-50 focus:outline-none file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-[10px] file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" accept=".pdf,.docx">
                                            <div wire:loading wire:target="kartu_kendali_file" class="text-[10px] text-indigo-600 mt-1 font-bold">Mengunggah...</div>
                                            <x-input-error :messages="$errors->get('kartu_kendali_file')" class="mt-1" />
                                        </div>

                                        <button type="button" @click="confirmUploadKartu($wire)" wire:loading.attr="disabled" {{ !$kartu_kendali_file ? 'disabled' : '' }} class="w-full bg-gray-900 text-white text-[11px] font-black uppercase tracking-[0.2em] py-3 px-4 rounded-2xl transition-all flex items-center justify-center gap-2 disabled:opacity-50 active:scale-95">
                                            <span wire:loading.remove wire:target="uploadKartuKendali">Simpan Kartu Kendali</span>
                                            <span wire:loading wire:target="uploadKartuKendali">
                                                <svg class="animate-spin h-3 w-3 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            </span>
                                        </button>
                                    </div>
                                    @endif
                                    @else
                                    <p class="text-xs text-gray-500 italic">Menu upload akan muncul saat status pengajuan Anda adalah 'Validasi'.</p>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-8 p-4 bg-amber-50 border border-amber-200 rounded-xl flex items-start gap-3">
                                <svg class="w-5 h-5 text-amber-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <p class="text-xs text-amber-800 leading-relaxed">
                                    <span class="font-bold">Penting:</span> Validasi Admin tidak dapat dilanjutkan sebelum Kartu Kendali diunggah kembali oleh pihak Pesantren. Pastikan format file adalah PDF atau DOCX dengan ukuran maksimal 5MB.
                                </p>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>