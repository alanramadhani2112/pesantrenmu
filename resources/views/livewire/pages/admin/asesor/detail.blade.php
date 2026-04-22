<?php

use App\Models\User;
use App\Models\Asesor;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.app')] class extends Component {
    public $user;
    public $asesor;

    public function mount($uuid)
    {
        $asesorService = app(\App\Services\AsesorService::class);
        $this->user = $asesorService->findAsesor($uuid);
        
        if (!$this->user) {
            abort(404);
        }

        $this->asesor = $this->user->asesor;
    }
}; ?>

<div class="py-12">
    <x-slot name="header">
        {{ __('Detail Asesor') }}
    </x-slot>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-6">
            <a href="{{ route('admin.asesor.index') }}" wire:navigate class="text-indigo-600 hover:text-indigo-900 font-medium flex items-center transition duration-150">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Sidebar Info Card -->
            <div class="space-y-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center">
                        <div class="h-32 w-32 rounded-2xl overflow-hidden bg-indigo-50 border-4 border-indigo-100 shadow-sm mx-auto mb-4">
                            @if($asesor && $asesor->foto)
                            <img src="{{ Storage::url($asesor->foto) }}" class="w-full h-full object-cover">
                            @else
                            <div class="w-full h-full flex items-center justify-center bg-indigo-600 text-white text-5xl font-bold">
                                {{ substr($user->name, 0, 1) }}
                            </div>
                            @endif
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 leading-tight">{{ $asesor->nama_dengan_gelar ?? $user->name }}</h3>
                        <p class="text-xs text-gray-400 mt-1 uppercase tracking-widest font-bold">NIA PM: {{ $asesor->nomor_induk_asesor_pm ?? '-' }}</p>

                        <div class="mt-4 flex justify-center">
                            @if($user->status == 1)
                            <span class="bg-green-100 text-green-800 py-1 px-4 rounded-full text-[10px] font-bold uppercase tracking-wider">Aktif</span>
                            @else
                            <span class="bg-red-100 text-red-800 py-1 px-4 rounded-full text-[10px] font-bold uppercase tracking-wider">Tidak Aktif</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 space-y-4">
                        <h4 class="text-xs font-bold text-gray-800 border-b pb-2 uppercase tracking-wider">Informasi Kontak</h4>
                        <div>
                            <span class="text-[10px] text-gray-400 uppercase font-bold block mb-1">Email Utama</span>
                            <div class="flex items-center text-sm text-gray-700">
                                <svg class="h-4 w-4 mr-2 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                {{ $user->email }}
                            </div>
                        </div>
                        @if($asesor && $asesor->email_pribadi)
                        <div>
                            <span class="text-[10px] text-gray-400 uppercase font-bold block mb-1">Email Pribadi</span>
                            <div class="text-sm text-gray-700">{{ $asesor->email_pribadi }}</div>
                        </div>
                        @endif
                        <div>
                            <span class="text-[10px] text-gray-400 uppercase font-bold block mb-1">No. WhatsApp</span>
                            <div class="flex items-center text-sm text-gray-700">
                                <svg class="h-4 w-4 mr-2 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                {{ $asesor->whatsapp ?? '-' }}
                            </div>
                        </div>
                        <div>
                            <span class="text-[10px] text-gray-400 uppercase font-bold block mb-1">Domisili</span>
                            <div class="text-sm text-gray-700 font-medium">
                                {{ $asesor->kota_kabupaten ?? '-' }}, {{ $asesor->provinsi ?? '-' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg md:col-span-2">
                <div class="border-b border-gray-100 p-6 flex justify-between items-center bg-gray-50/50">
                    <h4 class="text-lg font-bold text-gray-800 uppercase tracking-wide">Data Lengkap Asesor</h4>
                    <span class="text-[10px] font-bold bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full border border-indigo-200 uppercase tracking-tighter">Status: {{ $user->status == 1 ? 'Terverifikasi' : 'Pending' }}</span>
                </div>

                <div class="p-8">
                    @if($asesor)
                    <!-- Section A: Identitas -->
                    <div class="space-y-6 mb-12">
                        <div class="flex items-center gap-3 border-b border-indigo-50 pb-3">
                            <div class="p-1.5 bg-indigo-50 rounded text-indigo-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <h5 class="text-md font-bold text-gray-800">A. IDENTITAS DIRI</h5>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-10">
                            <div class="space-y-1">
                                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Nama Lengkap (Tanpa Gelar)</span>
                                <p class="text-gray-800 font-semibold border-b border-gray-50 pb-2">{{ $asesor->nama_tanpa_gelar ?: '-' }}</p>
                            </div>
                            <div class="space-y-1">
                                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">NIK / Nomor KTP</span>
                                <p class="text-gray-800 font-semibold border-b border-gray-50 pb-2">{{ $asesor->nik ?: '-' }}</p>
                            </div>
                            <div class="space-y-1">
                                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">NBM / NIA</span>
                                <p class="text-gray-800 font-semibold border-b border-gray-50 pb-2">{{ $asesor->nbm_nia ?: '-' }}</p>
                            </div>
                            <div class="space-y-1">
                                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Tempat, Tanggal Lahir</span>
                                <p class="text-gray-800 font-semibold border-b border-gray-50 pb-2">
                                    {{ $asesor->tempat_lahir ?? '-' }}, {{ $asesor->tanggal_lahir ? \Carbon\Carbon::parse($asesor->tanggal_lahir)->format('d F Y') : '-' }}
                                </p>
                            </div>
                            <div class="space-y-1">
                                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Jenis Kelamin</span>
                                <p class="text-gray-800 font-semibold border-b border-gray-50 pb-2">{{ $asesor->jenis_kelamin ?: '-' }}</p>
                            </div>
                            <div class="space-y-1">
                                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Status Perkawinan</span>
                                <p class="text-gray-800 font-semibold border-b border-gray-50 pb-2">{{ $asesor->status_perkawinan ?: '-' }}</p>
                            </div>
                            <div class="space-y-1">
                                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Pendidikan Terakhir</span>
                                <p class="text-gray-800 font-semibold border-b border-gray-50 pb-2">{{ $asesor->pendidikan_terakhir ?: '-' }}</p>
                            </div>
                            <div class="space-y-1">
                                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Tahun Sertifikat Terbit</span>
                                <p class="text-gray-800 font-semibold border-b border-gray-50 pb-2">{{ $asesor->tahun_terbit_sertifikat ?: '-' }}</p>
                            </div>
                            <div class="md:col-span-2 space-y-1">
                                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Alamat Rumah</span>
                                <p class="text-gray-700 text-sm bg-gray-50 p-3 rounded-lg border border-gray-100 italic">{{ $asesor->alamat_rumah ?: '-' }}</p>
                            </div>
                        </div>

                        <div class="mt-8 pt-6 border-t border-gray-100 grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-10">
                            <div class="space-y-1">
                                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Profesi</span>
                                <p class="text-gray-800 font-semibold border-b border-gray-50 pb-2">{{ $asesor->profesi ?: '-' }}</p>
                            </div>
                            <div class="space-y-1">
                                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Jabatan Utama</span>
                                <p class="text-gray-800 font-semibold border-b border-gray-50 pb-2">{{ $asesor->jabatan_utama ?: '-' }}</p>
                            </div>
                            <div class="space-y-1">
                                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Unit Kerja</span>
                                <p class="text-gray-800 font-semibold border-b border-gray-50 pb-2">{{ $asesor->unit_kerja ?: '-' }}</p>
                            </div>
                            <div class="space-y-1">
                                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Telp Kantor</span>
                                <p class="text-gray-800 font-semibold border-b border-gray-50 pb-2">{{ $asesor->telp_kantor ?: '-' }}</p>
                            </div>
                            <div class="md:col-span-2 space-y-1">
                                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Alamat Kantor</span>
                                <p class="text-gray-700 text-sm bg-gray-50 p-3 rounded-lg border border-gray-100 italic">{{ $asesor->alamat_kantor ?: '-' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Section B: Pengalaman -->
                    <div class="space-y-6 mb-12">
                        <div class="flex items-center gap-3 border-b border-emerald-50 pb-3">
                            <div class="p-1.5 bg-emerald-50 rounded text-emerald-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <h5 class="text-md font-bold text-gray-800">B. PENGALAMAN & REKAM JEJAK</h5>
                        </div>

                        <div class="space-y-8">
                            <!-- Riwayat Pendidikan -->
                            <div>
                                <h6 class="text-xs font-bold text-gray-500 mb-3 uppercase flex items-center gap-2 underline underline-offset-4 decoration-emerald-200">Riwayat Pendidikan</h6>
                                <div class="space-y-2">
                                    @forelse($asesor->riwayat_pendidikan ?? [] as $item)
                                    <div class="flex flex-col md:flex-row md:items-center justify-between bg-gray-50 p-3 px-4 rounded-xl border border-gray-100 group hover:border-emerald-200 transition-colors">
                                        <div>
                                            <span class="text-xs font-bold uppercase text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded mr-2">{{ $item['jenjang'] ?? '-' }}</span>
                                            <span class="text-sm font-bold text-gray-800">{{ $item['dimana'] ?? '-' }}</span>
                                        </div>
                                        <span class="text-xs font-medium text-gray-500 mt-1 md:mt-0 italic">{{ $item['kapan'] ?? '-' }}</span>
                                    </div>
                                    @empty
                                    <p class="text-xs text-gray-400 italic">Belum ada data riwayat pendidikan.</p>
                                    @endforelse
                                </div>
                            </div>

                            <!-- Pengalaman Bekerja -->
                            <div>
                                <h6 class="text-xs font-bold text-gray-500 mb-3 uppercase flex items-center gap-2 underline underline-offset-4 decoration-emerald-200">Pengalaman Bekerja</h6>
                                <div class="space-y-2">
                                    @forelse($asesor->pengalaman_bekerja ?? [] as $item)
                                    <div class="flex flex-col md:flex-row md:items-center justify-between bg-gray-50 p-3 px-4 rounded-xl border border-gray-100 group hover:border-emerald-200 transition-colors">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-gray-800">{{ $item['sebagai'] ?? '-' }}</span>
                                            <span class="text-xs text-gray-500">{{ $item['dimana'] ?? '-' }}</span>
                                        </div>
                                        <span class="text-xs font-medium text-gray-500 mt-1 md:mt-0 italic">{{ $item['kapan'] ?? '-' }}</span>
                                    </div>
                                    @empty
                                    <p class="text-xs text-gray-400 italic">Belum ada data pengalaman bekerja.</p>
                                    @endforelse
                                </div>
                            </div>

                            <!-- Pengalaman Pelatihan & Organisasi -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h6 class="text-xs font-bold text-gray-500 mb-3 uppercase flex items-center gap-2 underline underline-offset-4 decoration-emerald-200">Pelatihan</h6>
                                    <div class="space-y-2">
                                        @forelse($asesor->pengalaman_pelatihan ?? [] as $item)
                                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                                            <span class="block text-sm font-bold text-gray-800">{{ $item['sebagai'] ?? '-' }}</span>
                                            <div class="flex justify-between items-center mt-1">
                                                <span class="text-[10px] text-gray-500 truncate max-w-[150px]">{{ $item['dimana'] ?? '-' }}</span>
                                                <span class="text-[10px] font-bold text-emerald-600">{{ $item['kapan'] ?? '-' }}</span>
                                            </div>
                                        </div>
                                        @empty
                                        <p class="text-xs text-gray-400 italic">Belum ada data pelatihan.</p>
                                        @endforelse
                                    </div>
                                </div>
                                <div>
                                    <h6 class="text-xs font-bold text-gray-500 mb-3 uppercase flex items-center gap-2 underline underline-offset-4 decoration-emerald-200">Organisasi</h6>
                                    <div class="space-y-2">
                                        @forelse($asesor->pengalaman_berorganisasi ?? [] as $item)
                                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                                            <span class="block text-sm font-bold text-gray-800">{{ $item['sebagai'] ?? '-' }}</span>
                                            <div class="flex justify-between items-center mt-1">
                                                <span class="text-[10px] text-gray-500 truncate max-w-[150px]">{{ $item['dimana'] ?? '-' }}</span>
                                                <span class="text-[10px] font-bold text-emerald-600">{{ $item['kapan'] ?? '-' }}</span>
                                            </div>
                                        </div>
                                        @empty
                                        <p class="text-xs text-gray-400 italic">Belum ada data organisasi.</p>
                                        @endforelse
                                    </div>
                                </div>
                            </div>

                            <!-- Karya Publikasi -->
                            <div>
                                <h6 class="text-xs font-bold text-gray-500 mb-3 uppercase flex items-center gap-2 underline underline-offset-4 decoration-emerald-200">Karya Publikasi</h6>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    @forelse($asesor->karya_publikasi ?? [] as $item)
                                    <div class="bg-gray-50 p-3 rounded-xl border border-gray-100 flex items-center justify-between group hover:border-emerald-200 transition-colors">
                                        <span class="text-xs font-bold text-gray-700 truncate pr-2">{{ is_array($item) ? ($item['judul'] ?? '-') : $item }}</span>
                                        @if(is_array($item) && isset($item['link']) && $item['link'])
                                        <a href="{{ $item['link'] }}" target="_blank" class="text-emerald-600 hover:text-emerald-800 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        </a>
                                        @endif
                                    </div>
                                    @empty
                                    <p class="text-xs text-gray-400 italic col-span-2">Belum ada data karya publikasi.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section C: Dokumen -->
                    <div class="space-y-6">
                        <div class="flex items-center gap-3 border-b border-amber-50 pb-3">
                            <div class="p-1.5 bg-amber-50 rounded text-amber-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h5 class="text-md font-bold text-gray-800">C. DOKUMEN PENDUKUNG</h5>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            @php
                            $files = [
                            'ktp_file' => 'KTP / Identitas',
                            'ijazah_file' => 'Ijazah Terakhir',
                            'kartu_nbm_file' => 'Kartu NBM / NIA',
                            ];
                            @endphp

                            @foreach($files as $field => $label)
                            <div class="bg-white border rounded-2xl p-4 flex flex-col justify-between hover:shadow-lg transition-all group {{ $asesor && $asesor->$field ? 'border-amber-200 bg-amber-50/20' : 'border-gray-100 opacity-60' }}">
                                <div class="mb-4">
                                    <span class="text-[10px] font-bold text-gray-500 uppercase tracking-tight block mb-1">{{ $label }}</span>
                                    @if($asesor && $asesor->$field)
                                    <div class="h-1 bg-amber-100 rounded-full w-12"></div>
                                    @else
                                    <div class="h-1 bg-gray-100 rounded-full w-8"></div>
                                    @endif
                                </div>

                                @if($asesor && $asesor->$field)
                                <a href="{{ Storage::url($asesor->$field) }}" target="_blank" class="flex items-center gap-2 text-xs font-bold text-amber-600 hover:text-amber-800">
                                    <div class="p-2 bg-amber-100 rounded-lg group-hover:bg-amber-600 group-hover:text-white transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </div>
                                    LIHAT BERKAS
                                </a>
                                @else
                                <div class="flex items-center gap-2 text-[10px] text-gray-300 font-medium italic">
                                    <div class="p-2 border border-dashed border-gray-200 rounded-lg">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                    </div>
                                    TIDAK ADA BERKAS
                                </div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @else
                    <div class="text-center py-20 bg-gray-50 rounded-2xl border-2 border-dashed border-gray-100">
                        <svg class="h-16 w-16 text-gray-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <h6 class="text-lg font-bold text-gray-400">Profil Kosong</h6>
                        <p class="text-gray-400 italic text-sm">Asesor ini belum melengkapi profil mereka.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>