<?php

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\AkreditasiCatatan;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    use \Livewire\WithPagination;

    public $search = '';
    public $perPage = 10;
    public $sortField = 'id';
    public $sortAsc = false;
    public $periodeFilter = '';
    public $statusFilter = '';
    public $selectedAkreditasiNotes;
    public $selectedAssessment;
    public $visitasi_perbaikan = [];

    public function updatedPeriodeFilter()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function openCatatanModal($id)
    {
        $akreditasiService = app(\App\Services\AkreditasiService::class);
        $this->selectedAkreditasiNotes = $akreditasiService->findAkreditasiById($id, ['catatans.user']);
        $this->dispatch('open-modal', 'catatan-modal');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortAsc = !$this->sortAsc;
        } else {
            $this->sortAsc = true;
        }

        $this->sortField = $field;
    }

    public function mount()
    {
        if (!auth()->user()->isAsesor()) {
            abort(403);
        }
    }

    public function getAssessmentsProperty()
    {
        $asesor = auth()->user()->asesor;
        if (!$asesor) return collect();

        $asesorService = app(\App\Services\AsesorService::class);
        return $asesorService->getPaginatedAssessments(
            $asesor->id,
            $this->search,
            $this->periodeFilter,
            $this->statusFilter,
            $this->perPage,
            $this->sortField,
            $this->sortAsc
        );
    }


    public $visitasi_akreditasi_id;
    public $visitasi_tanggal;
    public $visitasi_tanggal_akhir;
    public $visitasi_catatan;
    public $visitasi_action = 'terima';

    public function openAturJadwalModal($id)
    {
        $asesorService = app(\App\Services\AsesorService::class);
        $this->selectedAssessment = $asesorService->findAssessment($id);
        
        if ($this->selectedAssessment) {
            $this->visitasi_akreditasi_id = $this->selectedAssessment->akreditasi_id;
            $this->visitasi_tanggal = date('Y-m-d');
            $this->visitasi_tanggal_akhir = date('Y-m-d');
            $this->visitasi_catatan = '';
            $this->visitasi_action = 'terima';
            $this->resetErrorBag();
            $this->dispatch('open-modal', 'atur-jadwal-modal');
        }
    }

    public function openTolakVisitasiModal($id)
    {
        $asesorService = app(\App\Services\AsesorService::class);
        $this->selectedAssessment = $asesorService->findAssessment($id);
        
        if ($this->selectedAssessment) {
            $this->visitasi_akreditasi_id = $this->selectedAssessment->akreditasi_id;
            $this->visitasi_catatan = '';
            $this->visitasi_perbaikan = [];
            $this->visitasi_action = 'tolak';
            $this->resetErrorBag();
            $this->dispatch('open-modal', 'tolak-visitasi-modal');
        }
    }

    public function submitVisitasi()
    {
        $asesorService = app(\App\Services\AsesorService::class);
        $assessment = $this->selectedAssessment;

        if ($this->visitasi_action == 'terima') {
            $this->validate([
                'visitasi_tanggal' => [
                    'required',
                    'date',
                    function ($attribute, $value, $fail) use ($assessment) {
                         if ($assessment && ($value < $assessment->tanggal_mulai || $value > $assessment->tanggal_berakhir)) {
                            $fail('Tanggal visitasi harus berada dalam rentang assessment (' . \Carbon\Carbon::parse($assessment->tanggal_mulai)->format('d/m/Y') . ' - ' . \Carbon\Carbon::parse($assessment->tanggal_berakhir)->format('d/m/Y') . ').');
                        }
                    },
                ],
                'visitasi_tanggal_akhir' => [
                    'required',
                    'date',
                    'after_or_equal:visitasi_tanggal',
                    function ($attribute, $value, $fail) use ($assessment) {
                         if ($assessment && ($value < $assessment->tanggal_mulai || $value > $assessment->tanggal_berakhir)) {
                            $fail('Tanggal visitasi akhir harus berada dalam rentang assessment (' . \Carbon\Carbon::parse($assessment->tanggal_mulai)->format('d/m/Y') . ' - ' . \Carbon\Carbon::parse($assessment->tanggal_berakhir)->format('d/m/Y') . ').');
                        }

                        $start = \Carbon\Carbon::parse($this->visitasi_tanggal);
                        $end = \Carbon\Carbon::parse($value);
                        if ($start->diffInDays($end) >= 4) {
                            $fail('Rentang visitasi maksimal adalah 4 hari.');
                        }
                    },
                ],
            ]);
        } else {
            $this->validate([
                'visitasi_perbaikan' => 'required|array|min:1',
                'visitasi_catatan' => 'required|min:10',
            ]);
        }

        if ($asesorService->processVisitasi($this->visitasi_akreditasi_id, auth()->id(), [
            'tanggal' => $this->visitasi_tanggal,
            'tanggal_akhir' => $this->visitasi_tanggal_akhir,
            'catatan' => $this->visitasi_catatan,
            'perbaikan' => $this->visitasi_perbaikan,
        ], $this->visitasi_action)) {
            $this->dispatch('close-modal', 'atur-jadwal-modal');
            $this->dispatch('close-modal', 'tolak-visitasi-modal');
            $this->js('window.location.reload()');
        }
    }
}; ?>

<div class="py-12" x-data="deleteConfirmation">
    <x-slot name="header">{{ __('Akreditasi') }}</x-slot>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <x-datatable.layout title="Pengajuan Akreditasi" :records="$this->assessments">
            <x-slot name="filters">
                <x-datatable.search placeholder="Cari Pesantren..." />

                <select wire:model.live="periodeFilter" class="text-xs border border-gray-100 rounded-lg bg-gray-50/50 py-2 pl-3 pr-8 focus:ring-1 focus:ring-[#1e3a5f] focus:border-[#1e3a5f] text-gray-500 font-bold">
                    <option value="">Periode</option>
                    @for($i = date('Y'); $i >= 2024; $i--)
                    <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </select>

                <select wire:model.live="statusFilter" class="text-xs border border-gray-100 rounded-lg bg-gray-50/50 py-2 pl-3 pr-8 focus:ring-1 focus:ring-[#1e3a5f] focus:border-[#1e3a5f] text-gray-500 font-bold">
                    <option value="">Status</option>
                    <option value="siap">Siap Visitasi</option>
                    <option value="belum">Belum Visitasi</option>
                    <option value="revisi">Perlu Revisi</option>
                    <option value="selesai">Selesai</option>
                </select>
            </x-slot>

            <x-slot name="thead">
                <th class="py-3 px-4 text-left text-[11px] font-bold text-gray-400 uppercase tracking-widest pl-6">PESANTREN</th>
                <th class="py-3 px-4 text-center text-[11px] font-bold text-gray-400 uppercase tracking-widest">JADWAL ASESSMENT</th>
                <th class="py-3 px-4 text-center text-[11px] font-bold text-gray-400 uppercase tracking-widest">STATUS</th>
                <th class="py-3 px-4 text-center text-[11px] font-bold text-gray-400 uppercase tracking-widest">JADWAL VISITASI</th>
                <th class="py-3 px-4 text-left text-[11px] font-bold text-gray-400 uppercase tracking-widest">CATATAN</th>
                <th class="py-3 px-4 text-right text-[11px] font-bold text-gray-400 uppercase tracking-widest pr-8">AKSI</th>
            </x-slot>

            <x-slot name="tbody">
                @forelse ($this->assessments as $index => $item)
                @if($item->akreditasi)
                <tr class="hover:bg-gray-50/50 transition-colors duration-150 group border-b border-gray-50 last:border-0" wire:key="ass-{{ $item->id }}">
                    <td class="py-5 px-4 pl-6">
                        <span class="text-sm font-bold text-[#374151]">{{ $item->akreditasi->user?->pesantren?->nama_pesantren ?? $item->akreditasi->user?->name ?? 'N/A' }}</span>
                    </td>
                    <td class="py-5 px-4 text-center">
                        <span class="text-xs font-bold text-gray-500">
                            {{ \Carbon\Carbon::parse($item->tanggal_mulai)->format('d') }}–{{ \Carbon\Carbon::parse($item->tanggal_berakhir)->format('d M Y') }}
                        </span>
                    </td>
                    <td class="py-5 px-4 text-center">
                        @if($item->akreditasi->status == 1)
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-tight bg-emerald-100 text-emerald-800">
                            Selesai
                        </span>
                        @elseif($item->akreditasi->status == 2)
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-tight bg-red-100 text-red-800">
                            Di Tolak
                        </span>
                        @elseif($item->akreditasi->status == 3)
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-tight bg-indigo-100 text-indigo-800">
                            Validasi
                        </span>
                        @elseif($item->akreditasi->tgl_visitasi)
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-tight bg-green-100 text-green-800">
                            Siap Visitasi
                        </span>
                        @elseif($item->akreditasi->catatans->whereNotNull('perbaikan')->filter(fn($c) => !empty($c->perbaikan))->isNotEmpty())
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-tight bg-red-100 text-red-800">
                            Perlu Revisi
                        </span>
                        @else
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-tight bg-amber-100 text-amber-800">
                            Belum Visitasi
                        </span>
                        @endif
                    </td>
                    <td class="py-5 px-4 text-center text-xs font-bold text-gray-500">
                        @if($item->akreditasi->tgl_visitasi)
                        {{ \Carbon\Carbon::parse($item->akreditasi->tgl_visitasi)->format('d') }}–{{ \Carbon\Carbon::parse($item->akreditasi->tgl_visitasi_akhir)->format('d M Y') }}
                        @else
                        <span class="text-gray-300">Belum Dijadwalkan</span>
                        @endif
                    </td>
                    <td class="py-5 px-4">
                        <button wire:click="openCatatanModal({{ $item->akreditasi->id }})" class="flex items-center gap-2 text-[10px] font-extrabold text-[#111827] hover:text-blue-600 transition-colors uppercase tracking-tight bg-gray-50 py-1 px-2.5 rounded-lg border border-gray-100">
                            <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            @if($item->akreditasi->catatans->count() > 0)
                            {{ $item->akreditasi->catatans->count() }} Catatan
                            @endif
                        </button>
                    </td>
                    <td class="py-5 px-4 text-right pr-6">
                        <div class="inline-block text-left" x-data="{ 
                            open: false,
                            dropdownPosition: { top: 0, left: 0 },
                            updatePosition() {
                                let rect = this.$refs.btn.getBoundingClientRect();
                                this.dropdownPosition = { 
                                    top: (rect.bottom + 5) + 'px', 
                                    left: (rect.right - 176) + 'px' 
                                };
                            }
                        }">
                            <button x-ref="btn" @click="open = !open; if(open) updatePosition()" @click.away="open = false"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[11px] font-bold text-gray-400 hover:text-gray-700 transition-colors bg-gray-50/50 rounded-lg group-hover:bg-gray-100">
                                Aksi
                                <svg class="w-3 h-3 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <template x-teleport="body">
                                <div x-show="open"
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95"
                                    :style="`position: fixed; top: ${dropdownPosition.top}; left: ${dropdownPosition.left}; z-index: 9999;`"
                                    class="w-44 bg-white rounded-xl shadow-2xl border border-gray-100 py-2 origin-top-right overflow-hidden shadow-slate-200/50" x-cloak>

                                    <a href="{{ route('asesor.akreditasi-detail', $item->akreditasi->uuid) }}" wire:navigate
                                        class="flex items-center w-full px-4 py-2.5 text-[11px] font-bold text-slate-700 hover:bg-slate-50 transition-colors gap-3 bg-blue-50/50">
                                        <svg class="w-4 h-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="3" />
                                            <circle cx="12" cy="12" r="8" />
                                        </svg>
                                        Lihat Detail
                                    </a>

                                    @if($item->akreditasi->status == 5 && $item->tipe == 1)
                                    <button wire:click="openAturJadwalModal({{ $item->id }})" @click="open = false"
                                        class="flex items-center w-full px-4 py-2.5 text-[11px] font-bold text-slate-600 hover:bg-slate-50 transition-colors gap-3">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            <circle cx="10" cy="14" r="0.5" fill="currentColor" />
                                            <circle cx="12" cy="14" r="0.5" fill="currentColor" />
                                            <circle cx="14" cy="14" r="0.5" fill="currentColor" />
                                            <circle cx="10" cy="16" r="0.5" fill="currentColor" />
                                            <circle cx="12" cy="16" r="0.5" fill="currentColor" />
                                            <circle cx="14" cy="16" r="0.5" fill="currentColor" />
                                        </svg>
                                        Atur Jadwal Visitasi
                                    </button>

                                    <button wire:click="openTolakVisitasiModal({{ $item->id }})" @click="open = false"
                                        class="flex items-center w-full px-4 py-2.5 text-[11px] font-bold text-slate-600 hover:bg-slate-50 transition-colors gap-3">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <circle cx="12" cy="12" r="9" />
                                            <path d="M15 9l-6 6M9 9l6 6" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        Tolak Visitasi
                                    </button>
                                    @endif

                                    @if($item->akreditasi->status == 4 || $item->akreditasi->status == 3 || $item->akreditasi->status == 1 || $item->akreditasi->status == 2)
                                    <a href="{{ route('asesor.akreditasi-detail', ['uuid' => $item->akreditasi->uuid, 'activeTab' => 'instrumen']) }}" wire:navigate
                                        class="flex items-center w-full px-4 py-2.5 text-[11px] font-bold text-indigo-600 hover:bg-slate-50 transition-colors gap-3 border-t border-gray-50/50">
                                        <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2-2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        {{ $item->akreditasi->status == 1 || $item->akreditasi->status == 2 ? 'Lihat Nilai' : 'Input Nilai' }}
                                    </a>
                                    @endif

                                    @if($item->akreditasi->status == 3 || $item->akreditasi->status == 1 || $item->akreditasi->status == 2)
                                    <a href="{{ route('asesor.akreditasi-detail', ['uuid' => $item->akreditasi->uuid, 'activeTab' => 'laporan_visitasi']) }}" wire:navigate
                                        class="flex items-center w-full px-4 py-2.5 text-[11px] font-bold text-emerald-600 hover:bg-emerald-50 transition-colors gap-3 border-t border-gray-50/50">
                                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                        </svg>
                                        {{ $item->akreditasi->status == 3 ? 'Upload Laporan' : 'Lihat Laporan' }}
                                    </a>
                                    @endif
                                </div>
                            </template>
                        </div>
                    </td>
                </tr>
                @endif
                @empty
                <tr>
                    <td colspan="6" class="py-16 text-center">
                        <div class="flex flex-col items-center gap-2">
                            <svg class="w-10 h-10 text-gray-400/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="text-xs text-gray-400 font-bold">Belum ada tugas akreditasi ditugaskan.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </x-slot>
        </x-datatable.layout>
    </div>

    <!-- Modal Atur Jadwal Visitasi -->
    <x-modal name="atur-jadwal-modal" focusable>
        <form wire:submit="submitVisitasi" class="p-8">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-8 h-8 rounded-lg bg-[#1e3a5f]/10 flex items-center justify-center text-[#1e3a5f]">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-slate-800">Atur Jadwal Visitasi</h2>
            </div>
            <p class="text-xs font-medium text-slate-500 mb-8">Tentukan jadwal visitasi Pesantren.</p>

            @if($selectedAssessment)
            <div class="bg-gray-50/50 rounded-2xl p-6 border border-slate-100 mb-8">
                <div class="mb-4">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Pesantren</p>
                    <p class="text-sm font-black text-[#1e3a5f]">{{ $selectedAssessment->akreditasi->user?->pesantren?->nama_pesantren ?? $selectedAssessment->akreditasi->user?->name }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Jadwal Assessment</p>
                    <p class="text-sm font-black text-[#1e3a5f]">{{ \Carbon\Carbon::parse($selectedAssessment->tanggal_mulai)->format('d') }}–{{ \Carbon\Carbon::parse($selectedAssessment->tanggal_berakhir)->format('d F Y') }}</p>
                </div>
            </div>
            @endif

            <div class="space-y-6">
                <h3 class="text-sm font-black text-slate-800">Input Jadwal</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-input-label value="Tanggal Mulai Visitasi" class="text-[11px] font-bold text-slate-500 uppercase tracking-widest !mb-2" />
                        <x-text-input wire:model="visitasi_tanggal" type="date" class="w-full !rounded-xl !bg-slate-50/50 !border-slate-100" />
                        <x-input-error :messages="$errors->get('visitasi_tanggal')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label value="Tanggal Selesai Visitasi" class="text-[11px] font-bold text-slate-500 uppercase tracking-widest !mb-2" />
                        <x-text-input wire:model="visitasi_tanggal_akhir" type="date" class="w-full !rounded-xl !bg-slate-50/50 !border-slate-100" />
                        <x-input-error :messages="$errors->get('visitasi_tanggal_akhir')" class="mt-2" />
                    </div>
                </div>
                <p class="text-[10px] font-bold text-red-500 leading-relaxed">
                    Rentang visitasi maksimal 4 hari dan harus berada dalam periode assessment yang telah ditetapkan oleh Admin Pusat.
                </p>

                <div>
                    <x-input-label value="Catatan Tambahan" class="text-sm font-black text-slate-800 !mb-4" />
                    <textarea wire:model="visitasi_catatan" rows="4"
                        class="w-full rounded-2xl border-slate-100 bg-slate-50/50 text-sm text-slate-600 focus:ring-[#1e3a5f] focus:border-[#1e3a5f] placeholder-slate-300"
                        placeholder="Contoh: Koordinasi kedatangan dengan pimpinan pesantren pukul 08.00 WIB."></textarea>
                </div>
            </div>

            <div class="mt-10 flex flex-col md:flex-row gap-3">
                <button type="submit" class="flex-1 bg-[#1e3a5f] text-white py-3.5 rounded-xl font-bold text-xs uppercase tracking-[0.2em] shadow-lg shadow-[#1e3a5f]/20 hover:bg-[#162d4a] transition-all">
                    {{ __('Atur Jadwal Visitasi') }}
                </button>
                <button type="button" x-on:click="$dispatch('close')" class="px-8 py-3.5 bg-slate-100 text-slate-400 rounded-xl font-bold text-xs uppercase tracking-[0.2em] hover:bg-slate-200 transition-all">
                    Batal
                </button>
            </div>
        </form>
    </x-modal>

    <!-- Modal Tolak Visitasi -->
    <x-modal name="tolak-visitasi-modal" focusable>
        <form wire:submit="submitVisitasi" class="p-8">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center text-red-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <circle cx="12" cy="12" r="9" />
                        <path d="M15 9l-6 6M9 9l6 6" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-slate-800">Tolak Visitasi</h2>
            </div>
            <p class="text-xs font-medium text-slate-500 mb-8">Berikan alasan penolakan untuk proses perbaikan dokumen.</p>

            @if($selectedAssessment)
            <div class="bg-gray-50/50 rounded-2xl p-6 border border-slate-100 mb-8">
                <div class="mb-4">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Pesantren</p>
                    <p class="text-sm font-black text-[#1e3a5f]">{{ $selectedAssessment->akreditasi->user?->pesantren?->nama_pesantren ?? $selectedAssessment->akreditasi->user?->name }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Jadwal Assessment</p>
                    <p class="text-sm font-black text-[#1e3a5f]">{{ \Carbon\Carbon::parse($selectedAssessment->tanggal_mulai)->format('d') }}–{{ \Carbon\Carbon::parse($selectedAssessment->tanggal_berakhir)->format('d F Y') }}</p>
                </div>
            </div>
            @endif

            <div class="space-y-6">
                <h3 class="text-sm font-black text-slate-800">Dokumen yang Memerlukan Perbaikan</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    @foreach(['Profil Pesantren', 'IPM', 'Data SDM', 'EPDM'] as $doc)
                    <label class="relative flex items-center p-3 rounded-xl border border-slate-100 bg-slate-50/50 cursor-pointer hover:bg-slate-100 transition-all select-none">
                        <input type="checkbox" wire:model="visitasi_perbaikan" value="{{ $doc }}" class="w-4 h-4 rounded border-slate-300 text-[#1e3a5f] focus:ring-[#1e3a5f]" />
                        <span class="ml-3 text-[11px] font-bold text-slate-600">{{ $doc }}</span>
                    </label>
                    @endforeach
                </div>
                <p class="text-[10px] font-bold text-red-500 leading-relaxed">
                    Minimal satu bagian harus dipilih sebelum melanjutkan.
                </p>

                <div>
                    <x-input-label value="Alasan Penolakan" class="text-sm font-black text-slate-800 !mb-4" />
                    <textarea wire:model="visitasi_catatan" rows="4"
                        class="w-full rounded-2xl border-slate-100 bg-slate-50/50 text-sm text-slate-600 focus:ring-[#1e3a5f] focus:border-[#1e3a5f] placeholder-slate-300"
                        placeholder="Jelaskan secara spesifik bagian yang perlu diperbaiki."></textarea>
                    <x-input-error :messages="$errors->get('visitasi_catatan')" class="mt-2" />
                </div>
            </div>

            <div class="mt-10 flex flex-col md:flex-row gap-3">
                <button type="submit" class="flex-1 bg-red-500 text-white py-3.5 rounded-xl font-bold text-xs uppercase tracking-[0.2em] shadow-lg shadow-red-500/20 hover:bg-red-600 transition-all">
                    {{ __('Tolak Visitasi') }}
                </button>
                <button type="button" x-on:click="$dispatch('close')" class="px-8 py-3.5 bg-slate-100 text-slate-400 rounded-xl font-bold text-xs uppercase tracking-[0.2em] hover:bg-slate-200 transition-all">
                    Batal
                </button>
            </div>
        </form>
    </x-modal>

    <!-- Modal Catatan (View Only) -->
    <x-modal name="catatan-modal" focusable>
        <div class="p-0 overflow-hidden rounded-3xl">
            @if($selectedAkreditasiNotes)
            @php
            $latestCatatan = $selectedAkreditasiNotes->catatans->sortByDesc('created_at')->first();
            $isRejection = $latestCatatan && !empty($latestCatatan->perbaikan);
            @endphp

            <div class="p-8">
                <div class="flex justify-between items-center mb-8">
                    <h2 class="text-xl font-bold text-[#1e3a5f]">
                        {{ $isRejection ? 'Catatan Penolakan Visitasi' : 'Catatan Penerimaan Visitasi' }}
                    </h2>
                    <button x-on:click="$dispatch('close')" class="text-slate-300 hover:text-slate-500 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-8 max-h-[75vh] overflow-y-auto pr-2 custom-scrollbar">
                    @forelse($selectedAkreditasiNotes->catatans->sortByDesc('created_at') as $catatan)
                    @php
                    $isNoteRejection = !empty($catatan->perbaikan);
                    @endphp
                    <div class="bg-white border-b border-slate-50 last:border-0 pb-8 last:pb-0">
                        <div class="flex items-center gap-4 mb-6">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($catatan->user->name) }}&color=1e3a5f&background=f1f5f9"
                                class="w-12 h-12 rounded-2xl border-2 border-white shadow-sm object-cover" alt="Avatar">
                            <div>
                                <h3 class="text-sm font-black text-[#1e3a5f]">{{ $catatan->user->name }}</h3>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                    {{ $catatan->user->isAsesor() ? 'Ketua Asesor' : ($catatan->user->isAdmin() ? 'Administrator Pusat' : 'Pihak Berwenang') }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Status:</p>
                                @if($isNoteRejection)
                                <span class="px-3 py-1.5 rounded-xl bg-amber-50 text-amber-600 text-[10px] font-black uppercase tracking-tight">Perlu Perbaikan Dokumen</span>
                                @else
                                <span class="px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-600 text-[10px] font-black uppercase tracking-tight">Visitasi Dijadwalkan</span>
                                @endif
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">
                                    {{ $isNoteRejection ? 'Tanggal Review:' : 'Jadwal Visitasi:' }}
                                </p>
                                <p class="text-[11px] font-black text-slate-700">
                                    @if($isNoteRejection)
                                    {{ $catatan->created_at->translatedFormat('d F Y') }}
                                    @else
                                    @if($selectedAkreditasiNotes->tgl_visitasi)
                                    {{ \Carbon\Carbon::parse($selectedAkreditasiNotes->tgl_visitasi)->format('d/m/y') }} - {{ \Carbon\Carbon::parse($selectedAkreditasiNotes->tgl_visitasi_akhir)->format('d/m/y') }}
                                    @else
                                    {{ $catatan->created_at->translatedFormat('d F Y') }}
                                    @endif
                                    @endif
                                </p>
                            </div>
                        </div>

                        @if($isNoteRejection)
                        <div class="mb-6">
                            <p class="text-[11px] font-black text-slate-800 mb-3">Dokumen yang memerlukan perbaikan</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach(explode(', ', $catatan->perbaikan) as $p)
                                <div class="flex items-center gap-2 px-3 py-2 bg-amber-500 rounded-xl text-white">
                                    @switch($p)
                                    @case('Profil Pesantren')
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    @break
                                    @case('IPM')
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    @break
                                    @case('Data SDM')
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    @break
                                    @case('EPDM')
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    @break
                                    @default
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    @endswitch
                                    <span class="text-[10px] font-black uppercase tracking-tight">{{ $p }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <div class="rounded-3xl p-6 {{ $isNoteRejection ? 'bg-amber-50 text-slate-700' : 'bg-emerald-50 text-slate-700' }}">
                            <div class="text-xs leading-relaxed font-medium space-y-4 prose-sm prose-slate max-w-none">
                                {!! nl2br(e($catatan->catatan)) !!}
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-12">
                        <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-gray-400 font-medium font-bold text-xs text-center">Tidak ada catatan ditemukan.</p>
                    </div>
                    @endforelse
                </div>
            </div>
            @endif
        </div>
    </x-modal>
</div>