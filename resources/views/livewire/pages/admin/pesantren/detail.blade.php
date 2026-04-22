<?php

use App\Models\User;
use App\Models\Pesantren;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.app')] class extends Component {
    public $user;
    public $pesantren;

    public function mount($uuid)
    {
        $pesantrenService = app(\App\Services\PesantrenService::class);
        $this->user = $pesantrenService->findUserDetail($uuid);
        
        if (!$this->user) {
            abort(404);
        }
        
        $this->pesantren = $this->user->pesantren;
    }

    public function toggleLock()
    {
        if ($this->pesantren) {
            $pesantrenService = app(\App\Services\PesantrenService::class);
            if ($pesantrenService->toggleDataLock($this->pesantren->id)) {
                $status = $this->pesantren->refresh()->is_locked ? 'terkunci' : 'terbuka';
                $this->dispatch('notification-received', title: 'Berhasil', message: "Akses data pesantren berhasil diubah menjadi $status.");
            }
        }
    }
}; ?>

<div class="py-12">
    <x-slot name="header">
            {{ __('Detail Pesantren') }}
    </x-slot>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-6">
            <a href="{{ route('admin.pesantren.index') }}" wire:navigate class="text-indigo-600 hover:text-indigo-900 font-medium flex items-center transition duration-150">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Basic Info Card -->
            <div class="space-y-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center">
                        <div class="h-24 w-24 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold text-4xl mx-auto mb-4 border-4 border-indigo-100 shadow-md">
                            {{ substr($pesantren->nama_pesantren ?? $user->name, 0, 1) }}
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">{{ $pesantren->nama_pesantren ?? $user->name }}</h3>
                        <p class="text-[10px] text-gray-400 uppercase tracking-widest mb-4 font-bold">NSP: {{ $pesantren->ns_pesantren ?? '-' }}</p>
                        <div class="flex justify-center">
                            @if($user->status == 1)
                            <span class="bg-green-100 text-green-800 py-1 px-4 rounded-full text-xs font-bold uppercase tracking-wider">Aktif</span>
                            @else
                            <span class="bg-red-100 text-red-800 py-1 px-4 rounded-full text-xs font-bold uppercase tracking-wider">Tidak Aktif</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 space-y-4">
                        <h4 class="text-sm font-bold text-gray-800 border-b pb-2 uppercase tracking-wider">Informasi Kontak</h4>
                        <div>
                            <span class="text-[10px] text-gray-400 uppercase font-bold block mb-1">Email Pesantren</span>
                            <div class="flex items-center text-sm text-gray-700">
                                <svg class="h-4 w-4 mr-2 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                {{ $pesantren->email_pesantren ?? '-' }}
                            </div>
                        </div>
                        <div>
                            <span class="text-[10px] text-gray-400 uppercase font-bold block mb-1">No. Telp / WA</span>
                            <div class="flex items-center text-sm text-gray-700 mb-1">
                                <svg class="h-4 w-4 mr-2 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                {{ $pesantren->telp_pesantren ?? '-' }} / {{ $pesantren->hp_wa ?? '-' }}
                            </div>
                        </div>
                        <div>
                            <span class="text-[10px] text-gray-400 uppercase font-bold block mb-1">Lokasi</span>
                            <div class="text-sm text-gray-700 font-medium">
                                {{ $pesantren->kota_kabupaten ?? '-' }}, {{ $pesantren->provinsi ?? '-' }}
                            </div>
                        </div>
                    </div>
                    @if($pesantren)
                    <div class="mt-4 border-t pt-4">
                        <button wire:click="toggleLock" wire:loading.attr="disabled"
                            class="w-full justify-center inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150 {{ $pesantren->is_locked ? 'bg-amber-500 hover:bg-amber-600 focus:ring-amber-500 text-white' : 'bg-gray-800 hover:bg-gray-700 focus:ring-gray-500 text-white' }}">

                            <svg wire:loading.remove wire:target="toggleLock" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                @if($pesantren->is_locked)
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                                @else
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H10" opacity="0" /> <!-- Spacer -->
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 7a2 2 0 012 2" />
                                @endif
                            </svg>
                            <svg wire:loading wire:target="toggleLock" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>

                            {{ $pesantren->is_locked ? 'Buka Kunci Data' : 'Kunci Data Pesantren' }}
                        </button>
                        <p class="mt-2 text-[10px] text-center text-gray-400">
                            {{ $pesantren->is_locked ? 'Data pesantren saat ini terkunci (Read Only).' : 'Data pesantren dapat diedit oleh user.' }}
                        </p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Detailed Info Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg md:col-span-2">
                <div class="border-b border-gray-100 p-6 flex justify-between items-center bg-gray-50/50">
                    <h4 class="text-lg font-bold text-gray-800 uppercase tracking-wide">Data Lengkap Pesantren</h4>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-400 uppercase font-bold">Akreditasi:</span>
                        @php
                        $latestAkreditasi = $user->akreditasis()->latest()->first();
                        @endphp
                        @if (!$latestAkreditasi)
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-600 border border-gray-200 uppercase">-</span>
                        @elseif ($latestAkreditasi->status == 1)
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-700 border border-indigo-200 uppercase">
                            {{ $latestAkreditasi->peringkat ?? 'Berhasil' }}
                        </span>
                        @else
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-700 border border-amber-200 uppercase">
                            Proses
                        </span>
                        @endif
                    </div>
                </div>

                <div class="p-8">
                    @if($pesantren)
                    <!-- Section A: Profil -->
                    <div class="space-y-6 mb-10">
                        <div class="flex items-center gap-3 border-b border-indigo-50 pb-3">
                            <div class="p-1.5 bg-indigo-50 rounded text-indigo-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <h5 class="text-md font-bold text-gray-800">A. PROFIL PESANTREN</h5>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-1">
                                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Tahun Pendirian</span>
                                <p class="text-gray-800 font-semibold border-b border-gray-50 pb-2">{{ $pesantren->tahun_pendirian ?: '-' }}</p>
                            </div>
                            <div class="space-y-1">
                                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Nama Mudir</span>
                                <p class="text-gray-800 font-semibold border-b border-gray-50 pb-2">{{ $pesantren->nama_mudir ?: '-' }}</p>
                            </div>
                            <div class="space-y-1">
                                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Pendidikan Mudir</span>
                                <p class="text-gray-800 font-semibold border-b border-gray-50 pb-2">{{ $pesantren->jenjang_pendidikan_mudir ?: '-' }}</p>
                            </div>
                            <div class="space-y-1">
                                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Persyarikatan</span>
                                <p class="text-gray-800 font-semibold border-b border-gray-50 pb-2">{{ $pesantren->persyarikatan ?: '-' }}</p>
                            </div>
                            <div class="md:col-span-2 space-y-1">
                                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Alamat Lengkap</span>
                                <p class="text-gray-800 bg-gray-50 p-3 rounded-lg border border-gray-100 text-sm italic">{{ $pesantren->alamat ?: '-' }}</p>
                            </div>
                            <div class="md:col-span-2 space-y-1">
                                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Visi</span>
                                <p class="text-gray-800 text-sm whitespace-pre-line bg-indigo-50/30 p-4 rounded-lg border border-indigo-100/50">{{ $pesantren->visi ?: '-' }}</p>
                            </div>
                            <div class="md:col-span-2 space-y-1">
                                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Misi</span>
                                <p class="text-gray-800 text-sm whitespace-pre-line bg-indigo-50/30 p-4 rounded-lg border border-indigo-100/50">{{ $pesantren->misi ?: '-' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Section B: Data & Fasilitas -->
                    <div class="space-y-6 mb-10">
                        <div class="flex items-center gap-3 border-b border-emerald-50 pb-3">
                            <div class="p-1.5 bg-emerald-50 rounded text-emerald-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21h-6a2 2 0 00-2 2v1m-1-4l-3 3m0 0l3 3m-3-3h15M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <h5 class="text-md font-bold text-gray-800">B. DATA & FASILITAS</h5>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <h6 class="text-xs font-bold text-gray-500 mb-3 uppercase flex items-center gap-2 underline underline-offset-4 decoration-emerald-200">Layanan Pendidikan</h6>
                                <div class="space-y-2">
                                    @if($pesantren->units && $pesantren->units->count() > 0)
                                    @foreach($pesantren->units as $unit)
                                    <div class="flex justify-between items-center bg-gray-50 p-2 px-3 rounded-lg border border-gray-100 transition hover:border-emerald-200 hover:bg-emerald-50/30 group">
                                        <span class="text-xs font-bold uppercase text-gray-700">{{ str_replace('_', ' ', $unit->unit) }}</span>
                                        <span class="text-[10px] font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded border border-emerald-200 group-hover:bg-emerald-500 group-hover:text-white group-hover:border-emerald-600 transition-colors">{{ $unit->jumlah_rombel ?? 0 }} Rombel</span>
                                    </div>
                                    @endforeach
                                    @else
                                    <p class="text-xs text-gray-400 italic">Belum ada data unit satuan pendidikan.</p>
                                    @endif
                                </div>
                            </div>
                            <div>
                                <h6 class="text-xs font-bold text-gray-500 mb-3 uppercase flex items-center gap-2 underline underline-offset-4 decoration-sky-200">Luas Wilayah</h6>
                                <div class="grid grid-cols-1 gap-3">
                                    <div class="p-3 bg-gradient-to-r from-emerald-50 to-white rounded-xl border border-emerald-100 flex items-center gap-4">
                                        <div class="p-2 bg-emerald-100 rounded-full text-emerald-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <span class="block text-[10px] font-bold text-emerald-600 uppercase">Luas Tanah</span>
                                            <p class="text-lg font-bold text-gray-800">{{ $pesantren->luas_tanah ?: '0' }} <span class="text-xs text-gray-400 font-medium">m²</span></p>
                                        </div>
                                    </div>
                                    <div class="p-3 bg-gradient-to-r from-sky-50 to-white rounded-xl border border-sky-100 flex items-center gap-4">
                                        <div class="p-2 bg-sky-100 rounded-full text-sky-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                        </div>
                                        <div>
                                            <span class="block text-[10px] font-bold text-sky-600 uppercase">Luas Bangunan</span>
                                            <p class="text-lg font-bold text-gray-800">{{ $pesantren->luas_bangunan ?: '0' }} <span class="text-xs text-gray-400 font-medium">m²</span></p>
                                        </div>
                                    </div>
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
                            <h5 class="text-md font-bold text-gray-800">C. DOKUMEN PESANTREN</h5>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @php
                            $documents = [
                            'status_kepemilikan_tanah' => 'Status Kepemilikan Tanah',
                            'sertifikat_nsp' => 'Sertifikat NSP',
                            'rk_anggaran' => 'RK Anggaran',
                            'silabus_rpp' => 'Silabus dan RPP',
                            'peraturan_kepegawaian' => 'Peraturan Kepegawaian',
                            'file_lk_iapm' => 'File LK IAPM',
                            'laporan_tahunan' => 'Laporan Tahunan',
                            'dok_profil' => 'Dokumen Profil',
                            'dok_nsp' => 'Dokumen NSP',
                            'dok_renstra' => 'Dokumen Renstra',
                            'dok_rk_anggaran' => 'Dokumen RK Anggaran',
                            'dok_kurikulum' => 'Dokumen Kurikulum',
                            'dok_silabus_rpp' => 'Dokumen Silabus dan RPP',
                            'dok_kepengasuhan' => 'Dokumen Kepengasuhan',
                            'dok_peraturan_kepegawaian' => 'Dokumen Peraturan Kepegawaian',
                            'dok_sarpras' => 'Dokumen Sarpras',
                            'dok_laporan_tahunan' => 'Dokumen Laporan Tahunan',
                            'dok_sop' => 'Dokumen SOP',
                            ];
                            @endphp

                            @foreach($documents as $field => $label)
                            <div class="bg-white border rounded-lg p-3 flex flex-col justify-between hover:shadow-md transition group {{ $pesantren->$field ? 'border-amber-200 bg-amber-50/20' : 'border-gray-100 opacity-60' }}">
                                <span class="text-[10px] font-bold text-gray-500 uppercase tracking-tight mb-2 group-hover:text-amber-700 transition-colors">{{ $label }}</span>
                                @if($pesantren->$field)
                                <a href="{{ Storage::url($pesantren->$field) }}" target="_blank" class="flex items-center gap-2 text-xs font-bold text-amber-600 hover:text-amber-800 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    LIHAT DOKUMEN
                                </a>
                                @else
                                <span class="text-[10px] font-medium text-gray-300 uppercase italic">Belum Diunggah</span>
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
                        <p class="text-gray-400 italic text-sm">Pesantren ini belum melengkapi profil mereka.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>