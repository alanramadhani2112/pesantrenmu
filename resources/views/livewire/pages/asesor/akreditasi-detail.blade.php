@use('App\Models\Akreditasi')
@use('Illuminate\Support\Facades\Storage')
<div class="py-12" x-data="{ ...akreditasiManagement(), ...asesorManagement() }">
    <x-slot name="header">{{ __('Akreditasi Detail') }}</x-slot>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Visitasi Akreditasi:
                            {{ $pesantren->nama_pesantren ?? $akreditasi->user->name }}
                        </h2>
                        <p class="text-sm text-gray-500">Status Saat Ini: <span
                                class="font-semibold {{ Akreditasi::getStatusBadgeClass($akreditasi->status) }} px-2 py-0.5 rounded text-[10px]">{{ Akreditasi::getStatusLabel($akreditasi->status) }}</span>
                        </p>
                    </div>
                    <a href="{{ route('asesor.akreditasi') }}"
                        class="text-indigo-600 hover:text-indigo-900 font-medium">&larr; Kembali</a>
                </div>

                @if (session('status'))
                <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">
                    {{ session('status') }}
                </div>
                @endif

                <!-- Tabs -->
                <div class="mb-4 border-b border-gray-200">
                    <ul class="flex flex-wrap -mb-px text-sm font-medium text-center text-gray-500">
                        <li class="me-2">
                            <button wire:click="setTab('profil')"
                                class="inline-block p-3 border-b-2 rounded-t-lg {{ $activeTab === 'profil' ? 'text-indigo-600 border-indigo-600' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">Profil</button>
                        </li>
                        <li class="me-2">
                            <button wire:click="setTab('ipm')"
                                class="inline-block p-3 border-b-2 rounded-t-lg {{ $activeTab === 'ipm' ? 'text-indigo-600 border-indigo-600' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">IPM</button>
                        </li>
                        <li class="me-2">
                            <button wire:click="setTab('sdm')"
                                class="inline-block p-3 border-b-2 rounded-t-lg {{ $activeTab === 'sdm' ? 'text-indigo-600 border-indigo-600' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">SDM</button>
                        </li>
                        <li class="me-2">
                            <button wire:click="setTab('edpm_pesantren')"
                                class="inline-block p-3 border-b-2 rounded-t-lg {{ $activeTab === 'edpm_pesantren' ? 'text-indigo-600 border-indigo-600' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">EDPM</button>
                        </li>
                        @if($akreditasi->status != 5)
                        <li class="me-2">
                            <button wire:click="setTab('instrumen')"
                                class="inline-block p-3 border-b-2 rounded-t-lg {{ $activeTab === 'instrumen' ? 'text-indigo-600 border-indigo-600' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">NA</button>
                        </li>
                        @endif
                        @if($akreditasi->status == 3 || $akreditasi->status == 1 || $akreditasi->status == 2)
                        <li class="me-2">
                            <button wire:click="setTab('laporan_visitasi')"
                                class="inline-block p-3 border-b-2 rounded-t-lg {{ $activeTab === 'laporan_visitasi' ? 'text-indigo-600 border-indigo-600' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">Laporan Visitasi</button>
                        </li>
                        @endif
                    </ul>
                </div>

                <!-- Tab Contents -->
                <div class="mt-6">
                    @if ($activeTab === 'profil')
                    <div class="space-y-6">
                        <h3 class="text-lg font-bold text-gray-800 border-l-4 border-indigo-500 pl-3">PROFIL
                            PESANTREN</h3>
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
                            <div>
                                <p class="text-xs font-bold text-gray-500 uppercase">Nama Mudir</p>
                                <p class="text-gray-900">{{ $pesantren->nama_mudir ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-gray-500 uppercase">Tahun Pendirian</p>
                                <p class="text-gray-900">{{ $pesantren->tahun_pendirian ?? '-' }}</p>
                            </div>
                            @if($akreditasi->tgl_visitasi)
                            <div>
                                <p class="text-xs font-bold text-gray-500 uppercase">Tanggal Visitasi</p>
                                <p class="text-indigo-600 font-bold">
                                    {{ \Carbon\Carbon::parse($akreditasi->tgl_visitasi)->format('d F Y') }}
                                    @if($akreditasi->tgl_visitasi_akhir && $akreditasi->tgl_visitasi != $akreditasi->tgl_visitasi_akhir)
                                    - {{ \Carbon\Carbon::parse($akreditasi->tgl_visitasi_akhir)->format('d F Y') }}
                                    @endif
                                </p>
                            </div>
                            @endif
                        </div>
                        <div class="md:col-span-2 mt-4">
                            <p class="text-xs font-bold text-gray-500 uppercase mb-2">Layanan Satuan Pendidikan</p>
                            @if($pesantren->units && $pesantren->units->count() > 0)
                            <div class="overflow-x-auto border rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Unit</th>
                                            <th class="px-3 py-2 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Jml Rombel</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($pesantren->units as $unit)
                                        <tr>
                                            <td class="px-3 py-2 whitespace-nowrap text-xs md:text-sm font-bold text-gray-900 uppercase">{{ $unit->unit }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap text-xs md:text-sm text-center text-gray-700">{{ $unit->jumlah_rombel }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 border-t pt-4">
                                <div>
                                    <p class="text-xs font-bold text-gray-500 uppercase">Total Luas Tanah (m²)</p>
                                    <p class="text-gray-900 font-bold">{{ $pesantren->luas_tanah ?? '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-gray-500 uppercase">Total Luas Bangunan (m²)</p>
                                    <p class="text-gray-900 font-bold">{{ $pesantren->luas_bangunan ?? '-' }}</p>
                                </div>
                            </div>
                            @else
                            <p class="text-gray-900 italic text-sm">Belum ada data unit pendidikan.</p>
                            @endif
                        </div>
                        <!-- Dokumen Section -->
                        <div class="mt-6">
                            @php
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
                            @endphp

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50 p-6 rounded-lg mt-4">
                                <!-- Dokumen Utama -->
                                <div>
                                    <h4 class="text-sm font-bold text-gray-500 uppercase mb-3 border-b pb-1">Dokumen Utama</h4>
                                    <div class="space-y-2">
                                        @foreach($dokumenUtama as $field => $label)
                                        <div class="flex justify-between items-center bg-white p-2 rounded border border-gray-100">
                                            <span class="text-xs font-medium text-gray-700">{{ $label }}</span>
                                            @if($pesantren->$field)
                                            <a href="{{ Storage::url($pesantren->$field) }}" target="_blank" class="text-[10px] bg-indigo-50 text-indigo-600 px-2 py-1 rounded hover:bg-indigo-100 font-bold uppercase">Lihat</a>
                                            @else
                                            <span class="text-[10px] text-gray-400 italic"> - </span>
                                            @endif
                                        </div>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Dokumen Sekunder -->
                                <div>
                                    <h4 class="text-sm font-bold text-gray-500 uppercase mb-3 border-b pb-1">Dokumen Sekunder</h4>
                                    <div class="space-y-2">
                                        @foreach($dokumenSekunder as $field => $label)
                                        <div class="flex justify-between items-center bg-white p-2 rounded border border-gray-100">
                                            <span class="text-xs font-medium text-gray-700">{{ $label }}</span>
                                            @if($pesantren->$field)
                                            <a href="{{ Storage::url($pesantren->$field) }}" target="_blank" class="text-[10px] bg-indigo-50 text-indigo-600 px-2 py-1 rounded hover:bg-indigo-100 font-bold uppercase">Lihat</a>
                                            @else
                                            <span class="text-[10px] text-gray-400 italic"> - </span>
                                            @endif
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if ($activeTab === 'ipm')
                    <div class="space-y-6">
                        <h3 class="text-lg font-bold text-gray-800 border-l-4 border-indigo-500 pl-3">INDEKS
                            PEMENUHAN MUTLAK (IPM)</h3>
                        <div class="space-y-4">
                            @php
                            $ipmItems = [
                            'nsp_file' => '1. Izin operasional Kementerian Agama (NSP)',
                            'lulus_santri_file' =>
                            '2. Pernah meluluskan santri / memiliki santri kelas akhir',
                            'kurikulum_file' => '3. Menyelenggarakan kurikulum Dirasah Islamiyah',
                            'buku_ajar_file' => '4. Menggunakan buku ajar terbitan LP2 PPM',
                            ];
                            @endphp
                            @foreach ($ipmItems as $field => $label)
                            <div class="p-4 border rounded-lg bg-gray-50 flex justify-between items-center">
                                <span class="text-sm text-gray-700 font-medium">{{ $label }}</span>
                                <div>
                                    @if ($ipm && $ipm->$field)
                                    <a href="{{ Storage::url($ipm->$field) }}" target="_blank"
                                        class="bg-indigo-100 text-indigo-700 px-3 py-1 rounded text-xs font-bold hover:bg-indigo-200">Lihat
                                        Dokumen</a>
                                    @else
                                    <span class="text-red-500 text-xs italic">Belum diunggah</span>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if ($activeTab === 'sdm')
                    <div class="space-y-6">
                        <h3 class="text-lg font-bold text-gray-800 border-l-4 border-indigo-500 pl-3">REKAPITULASI
                            DATA SDM</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full border-collapse border border-gray-300 text-xs md:text-sm">
                                <thead class="bg-gray-100 uppercase font-bold text-nowrap">
                                    <tr>
                                        <th rowspan="2" class="border border-gray-300 px-2 py-2 text-center">NO</th>
                                        <th rowspan="2" class="border border-gray-300 px-4 py-2 text-center">BENTUK</th>
                                        <th colspan="2" class="border border-gray-300 px-2 py-1 text-center bg-green-50">SANTRI</th>
                                        <th colspan="2" class="border border-gray-300 px-2 py-1 text-center bg-blue-50">USTADZ DIROSAH</th>
                                        <th colspan="2" class="border border-gray-300 px-2 py-1 text-center bg-indigo-50">USTADZ NON DIROSAH</th>
                                        <th colspan="2" class="border border-gray-300 px-2 py-1 text-center bg-yellow-50">PAMONG</th>
                                        <th colspan="2" class="border border-gray-300 px-2 py-1 text-center bg-orange-50">MUSYRIF/MUSYRIFAH</th>
                                        <th colspan="2" class="border border-gray-300 px-2 py-1 text-center bg-purple-50">TENAGA KEPENDIDIKAN</th>
                                    </tr>
                                    <tr class="bg-gray-50 text-center">
                                        <th class="border border-gray-300 px-1 py-1">L</th>
                                        <th class="border border-gray-300 px-1 py-1">P</th>
                                        <th class="border border-gray-300 px-1 py-1">L</th>
                                        <th class="border border-gray-300 px-1 py-1">P</th>
                                        <th class="border border-gray-300 px-1 py-1">L</th>
                                        <th class="border border-gray-300 px-1 py-1">P</th>
                                        <th class="border border-gray-300 px-1 py-1">L</th>
                                        <th class="border border-gray-300 px-1 py-1">P</th>
                                        <th class="border border-gray-300 px-1 py-1">L</th>
                                        <th class="border border-gray-300 px-1 py-1">P</th>
                                        <th class="border border-gray-300 px-1 py-1">L</th>
                                        <th class="border border-gray-300 px-1 py-1">P</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($levels as $index => $level)
                                    <tr class="hover:bg-gray-50">
                                        <td class="border border-gray-300 px-2 py-1 text-center">{{ $index + 1 }}</td>
                                        <td class="border border-gray-300 px-4 py-1 font-medium bg-yellow-50 whitespace-nowrap">
                                            {{ \Illuminate\Support\Str::of($level)->replace('_', ' ')->upper() }}
                                        </td>
                                        @foreach($fields as $field)
                                        <td class="border border-gray-300 px-2 py-1 text-center">
                                            {{ $sdm[$level]->$field ?? 0 }}
                                        </td>
                                        @endforeach
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-blue-50 font-bold">
                                    <tr>
                                        <td colspan="2" class="border border-gray-300 px-4 py-2 text-center uppercase">JUMLAH</td>
                                        @foreach($fields as $field)
                                        <td class="border border-gray-300 px-2 py-2 text-center">
                                            {{ $this->getTotal($field) }}
                                        </td>
                                        @endforeach
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    @endif

                    @if ($activeTab === 'edpm_pesantren')
                    <div class="space-y-6">
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
                                    @php $butirsCount = count($komponen->butirs); @endphp
                                    @foreach ($komponen->butirs as $idx => $butir)
                                    <tr>
                                        <td class="border border-gray-300 px-2 py-2 text-center font-bold">
                                            {{ $butir->nomor_butir }}
                                        </td>
                                        <td class="border border-gray-300 px-4 py-2">
                                            {{ $butir->butir_pernyataan }}
                                        </td>
                                        <td
                                            class="border border-gray-300 px-4 py-2 font-medium bg-yellow-50 text-indigo-700 text-center">
                                            {{ $pesantrenEvaluasis[$butir->id] }}
                                        </td>
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
                    </div>
                    @endif

                    @if ($activeTab === 'instrumen')
                    <div class="space-y-6">
                        @if ($akreditasi->status == 4)
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-yellow-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                                <div>
                                    <h4 class="text-base font-bold text-yellow-900">Data Sedang Diverifikasi</h4>
                                    <p class="text-sm text-yellow-700 mt-1">Assessment telah diselesaikan dan saat ini sedang dalam proses verifikasi oleh admin. Data tidak dapat diubah.</p>
                                    @if ($this->asesorTipe == 1)
                                    <p class="text-sm text-yellow-700 mt-3">Nilai NK baru bisa diinputkan setelah NA1 DAN NA2 terisi/selesai diinputkan</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif

                        <form wire:submit="saveAsesorEdpm">
                            <div class="overflow-x-auto overflow-y-hidden mt-4">
                                <table class="min-w-full border-collapse border border-gray-300 text-xs md:text-sm">
                                    <thead class="bg-gray-100 font-bold uppercase">
                                        <tr>
                                            <th class="border border-gray-300 px-2 py-3 w-24">Komponen</th>
                                            <th class="border border-gray-300 px-1 py-3 w-12 text-center">No SK
                                            </th>
                                            <th class="border border-gray-300 px-1 py-3 w-12 text-center">No Butir
                                            </th>
                                            <th class="border border-gray-300 px-2 py-3 text-left">Butir Pernyataan
                                            </th>
                                            @if ($this->asesorTipe == 1)
                                            <th class="border border-gray-300 px-2 py-3 text-center w-20">NA 1</th>
                                            <th class="border border-gray-300 px-2 py-3 text-center w-20 bg-green-50">NA 2</th>
                                            <th class="border border-gray-300 px-2 py-3 text-center w-20 bg-amber-50">NK</th>
                                            <th class="border border-gray-300 px-2 py-3 text-center w-48 bg-blue-50 text-[10px]">CATATAN BUTIR (NK)</th>
                                            @else
                                            <th class="border border-gray-300 px-2 py-3 text-center w-24">NA</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($komponens as $komponen)
                                        @php $butirsCount = count($komponen->butirs); @endphp
                                        @foreach ($komponen->butirs as $index => $butir)
                                        <tr class="hover:bg-gray-50">
                                            @if ($index === 0)
                                            <td rowspan="{{ $butirsCount }}"
                                                class="border border-gray-300 px-2 py-2 font-bold text-center bg-gray-50 align-middle uppercase text-indigo-700">
                                                {{ $komponen->nama }}
                                            </td>
                                            @endif
                                            <td
                                                class="border border-gray-300 px-1 py-2 text-center text-gray-500">
                                                {{ $butir->no_sk }}
                                            </td>
                                            <td
                                                class="border border-gray-300 px-1 py-2 text-center font-bold">
                                                {{ $butir->nomor_butir }}
                                            </td>
                                            <td class="border border-gray-300 px-2 py-2">
                                                {{ $butir->butir_pernyataan }}
                                            </td>
                                            <td class="border border-gray-300 p-0">
                                                @if ($akreditasi->status == 3)
                                                    <div class="p-2 text-center font-bold text-gray-700 text-xs">
                                                        {{ $asesorEvaluasis[$butir->id] ?: '-' }}
                                                    </div>
                                                @else
                                                    <select
                                                        wire:model.live="asesorEvaluasis.{{ $butir->id }}"
                                                        class="w-full border-0 p-2 text-xs focus:ring-2 focus:ring-indigo-500 {{ $akreditasi->status == 4 && ($asesorTipe == 2 || !$isLocked) ? 'bg-white' : 'bg-gray-100 cursor-not-allowed' }}"
                                                        {{ $akreditasi->status == 4 && ($asesorTipe == 2 || !$isLocked) ? '' : 'disabled' }}>
                                                        <option value="">Pilih...</option>
                                                        <option value="1">1</option>
                                                        <option value="2">2</option>
                                                        <option value="3">3</option>
                                                        <option value="4">4</option>
                                                    </select>
                                                @endif
                                                @error('asesorEvaluasis.' . $butir->id)
                                                <span class="text-red-500 text-[10px] px-2 pb-1 block">{{ $message }}</span>
                                                @enderror
                                            </td>
                                            @if ($this->asesorTipe == 1)
                                            <td class="border border-gray-300 px-2 py-2 text-center font-bold bg-green-50/20 text-green-800">
                                                {{ $otherAsesorEvaluasis[$butir->id] ?? '' }}
                                            </td>
                                            <td class="border border-gray-300 p-0 bg-amber-50/10">
                                                @if ($akreditasi->status == 3)
                                                    <div class="p-2 text-center font-bold text-amber-900 text-xs">
                                                        {{ $asesorNks[$butir->id] ?: '-' }}
                                                    </div>
                                                @else
                                                    <select
                                                        wire:model.live="asesorNks.{{ $butir->id }}"
                                                        class="w-full border-0 p-2 text-xs focus:ring-2 focus:ring-amber-500 {{ $akreditasi->status == 4 && !empty($asesorEvaluasis[$butir->id]) && !empty($otherAsesorEvaluasis[$butir->id]) ? 'bg-white' : 'bg-gray-50 cursor-not-allowed' }}"
                                                        {{ $akreditasi->status == 4 && !empty($asesorEvaluasis[$butir->id]) && !empty($otherAsesorEvaluasis[$butir->id]) ? '' : 'disabled' }}>
                                                        <option value="">Pilih...</option>
                                                        <option value="1">1</option>
                                                        <option value="2">2</option>
                                                        <option value="3">3</option>
                                                        <option value="4">4</option>
                                                    </select>
                                                @endif
                                                @error('asesorNks.' . $butir->id)
                                                <span class="text-red-500 text-[10px] px-2 pb-1 block">{{ $message }}</span>
                                                @enderror
                                            </td>
                                            <td class="border border-gray-300 p-0 bg-blue-50/10">
                                                @if ($akreditasi->status == 3)
                                                    <div class="p-2 text-[10px] text-gray-700 whitespace-pre-line leading-relaxed">
                                                        {{ $asesorButirCatatans[$butir->id] ?: '-' }}
                                                    </div>
                                                @else
                                                    <textarea wire:model.live="asesorButirCatatans.{{ $butir->id }}"
                                                        class="w-full border-0 p-2 text-[10px] focus:ring-2 focus:ring-blue-500 min-h-[60px] {{ $akreditasi->status == 4 ? 'bg-white' : 'bg-gray-50 cursor-not-allowed' }}"
                                                        placeholder="Catatan butir..."
                                                        {{ $akreditasi->status == 4 ? '' : 'disabled' }}></textarea>
                                                @endif
                                            </td>
                                            @endif
                                        </tr>
                                        @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            @if ($this->asesorTipe == 1)
                            <div class="mt-8 space-y-6">
                                <h3 class="text-sm font-bold text-indigo-900 border-l-4 border-indigo-500 pl-3 uppercase">
                                    Catatan Rekomendasi Komponen (NK)</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @foreach ($komponens as $komponen)
                                    <div class="p-4 bg-blue-50/10 border border-blue-100 rounded-lg">
                                        <label class="block text-xs font-bold text-gray-700 uppercase mb-2">{{ $komponen->nama }}</label>
                                        @if ($akreditasi->status == 3)
                                            <div class="text-sm text-gray-800 bg-white p-3 rounded-lg border border-blue-100 min-h-[60px] leading-relaxed shadow-sm">
                                                {!! $asesorCatatans[$komponen->id] ?: '<span class="text-gray-400 italic">Tidak ada catatan.</span>' !!}
                                            </div>
                                        @else
                                            <x-quill-editor 
                                                wire:model.live="asesorCatatans.{{ $komponen->id }}"
                                                placeholder="Masukkan catatan rekomendasi {{ $komponen->nama }}..." 
                                                :disabled="$akreditasi->status != 4" />
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </form>

                        <div
                            class="flex justify-between items-center bg-indigo-50 p-4 rounded-lg border border-indigo-100 mt-4">
                            <div>
                                <h3
                                    class="text-sm font-bold text-indigo-900 border-l-4 border-indigo-500 pl-3 uppercase">
                                    Evaluasi Asesor</h3>
                                <p class="text-[10px] text-indigo-700 mt-1">Pastikan semua data sudah terisi sebelum melakukan verifikasi final.</p>
                            </div>
                            @if ($akreditasi->status == 4)
                            <div class="flex gap-2">
                                @if(!$isLocked || $asesorTipe == 2)
                                <x-secondary-button @click="confirmSaveDraft($wire)" wire:loading.attr="disabled" wire:target="saveAsesorEdpm">
                                    <span wire:loading.remove wire:target="saveAsesorEdpm">Simpan Draft</span>
                                    <span wire:loading wire:target="saveAsesorEdpm">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-700 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Menyimpan...
                                    </span>
                                </x-secondary-button>
                                @endif

                                @if($asesorTipe == 1)
                                <x-primary-button @click="confirmVerification($wire)" wire:loading.attr="disabled" wire:target="finalizeVerification">
                                    <span wire:loading.remove wire:target="finalizeVerification">Selesaikan & Verifikasi</span>
                                    <span wire:loading wire:target="finalizeVerification">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Memproses...
                                    </span>
                                </x-primary-button>
                                @else
                                <x-primary-button @click="confirmAsesor2Final($wire)" wire:loading.attr="disabled" wire:target="saveAsesorEdpm(true)">
                                    <span wire:loading.remove wire:target="saveAsesorEdpm(true)">Selesaikan (Final)</span>
                                    <span wire:loading wire:target="saveAsesorEdpm(true)">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Memproses...
                                    </span>
                                </x-primary-button>
                                @endif
                            </div>
                            @endif
                        </div>

                        <!-- Floating Navigation Buttons for NA Tab -->
                        <div class="fixed bottom-8 right-8 flex flex-col gap-3 z-50">
                            <button type="button"
                                onclick="document.getElementById('main-content-scroll').scrollTo({top: 0, behavior: 'smooth'})"
                                class="flex items-center justify-center w-12 h-12 bg-indigo-600 text-white rounded-full shadow-xl hover:bg-indigo-700 hover:scale-110 active:scale-95 transition-all duration-200 focus:outline-none focus:ring-4 focus:ring-indigo-300"
                                title="Scroll Ke Atas">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                </svg>
                            </button>
                            <button type="button"
                                onclick="const el = document.getElementById('main-content-scroll'); el.scrollTo({top: el.scrollHeight, behavior: 'smooth'})"
                                class="flex items-center justify-center w-12 h-12 bg-indigo-600 text-white rounded-full shadow-xl hover:bg-indigo-700 hover:scale-110 active:scale-95 transition-all duration-200 focus:outline-none focus:ring-4 focus:ring-indigo-300"
                                title="Scroll Ke Bawah">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    @endif

                    @if ($activeTab === 'laporan_visitasi')
                    <div class="space-y-6">
                        <div class="bg-indigo-50 border border-indigo-200 p-8 rounded-2xl shadow-sm">
                            <div class="flex items-center gap-4 mb-6">
                                <div class="h-12 w-12 bg-indigo-600 rounded-xl flex items-center justify-center text-white shadow-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-indigo-900">Instruksi Unggah Laporan Visitasi</h3>
                                    <p class="text-sm text-indigo-600">Silakan ikuti langkah-langkah di bawah ini untuk menyelesaikan laporan</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 relative">
                                <!-- Step 1 -->
                                <div class="bg-white p-6 rounded-xl border border-indigo-100 shadow-sm relative z-10">
                                    <span class="absolute -top-3 -left-3 h-8 w-8 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold shadow-md">1</span>
                                    <h4 class="font-bold text-gray-900 mb-2">Unduh Berkas</h4>
                                    <p class="text-xs text-gray-600 mb-4 leading-relaxed">Admin telah mengunggah template laporan, silakan unduh berkas tersebut di menu dokumen.</p>
                                    <a href="{{ route('documents.index', ['doc' => 'visitasi']) }}" class="inline-flex items-center text-[10px] font-bold text-indigo-600 hover:text-indigo-800 gap-1 group">
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
                                    <p class="text-xs text-gray-600 leading-relaxed">Pastikan seluruh data penilaian, rekomendasi, dan tanda tangan pada Laporan Visitasi sudah lengkap dan benar. </p>
                                </div>

                                <!-- Step 3 -->
                                <div class="bg-white p-6 rounded-xl border border-indigo-100 shadow-sm relative z-10">
                                    <span class="absolute -top-3 -left-3 h-8 w-8 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold shadow-md">3</span>
                                    <h4 class="font-bold text-gray-900 mb-2">Unggah</h4>

                                    <div class="space-y-4">
                                        <!-- My Report -->
                                        <div>
                                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Laporan Visitasi (Anda)</p>
                                            @php
                                            $myFile = $asesorTipe == 1 ? $akreditasi->laporan_visitasi_file : $akreditasi->laporan_visitasi_file_2;
                                            @endphp

                                            @if($myFile && !$errors->has('laporan_visitasi_file'))
                                            <div class="p-4 rounded-2xl border bg-emerald-50 border-emerald-100 flex items-center justify-between">
                                                <a href="{{ Storage::url($myFile) }}" target="_blank" class="flex items-center gap-2 text-emerald-600 text-[11px] font-bold hover:underline">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path d="M5 13l4 4L19 7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                    Lihat Laporan Saya
                                                </a>
                                            </div>
                                            @else
                                            <div class="space-y-4 border-t border-slate-50 pt-2">
                                                <input type="file" wire:model="laporan_visitasi_file" class="block w-full text-[11px] text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-[10px] file:font-black file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-slate-100 rounded-xl p-2 bg-slate-50/50" />
                                                <x-input-error :messages="$errors->get('laporan_visitasi_file')" />

                                                @if($laporan_visitasi_file)
                                                <button @click="confirmUploadLaporan($wire)" wire:loading.attr="disabled" class="w-full bg-[#1e293b] hover:bg-black text-white px-4 py-3 rounded-2xl text-[11px] font-black uppercase tracking-widest transition-all active:scale-95 disabled:opacity-50 flex items-center justify-center gap-3">
                                                    <svg wire:loading.remove wire:target="uploadLaporanVisitasi" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                                    </svg>
                                                    <svg wire:loading wire:target="uploadLaporanVisitasi" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    {{ __('Simpan Laporan') }}
                                                </button>
                                                @endif
                                            </div>
                                            @endif
                                        </div>

                                        <!-- Other Assessor Status -->
                                        @php
                                        $otherFile = $asesorTipe == 1 ? $akreditasi->laporan_visitasi_file_2 : $akreditasi->laporan_visitasi_file;
                                        $otherLabel = $asesorTipe == 1 ? 'Anggota (Asesor 2)' : 'Ketua (Asesor 1)';
                                        @endphp
                                        <div class="border-t border-slate-100 pt-4 mt-4">
                                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Status Laporan {{ $otherLabel }}</p>
                                            @if($otherFile)
                                            <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-100 uppercase tracking-tight">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                </svg>
                                                Sudah Diunggah
                                            </div>
                                            @else
                                            <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-50 text-amber-700 text-[10px] font-bold border border-amber-100 uppercase tracking-tight">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Belum Diunggah
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>