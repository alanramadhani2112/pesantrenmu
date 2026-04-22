<?php

use App\Models\MasterEdpmKomponen;
use App\Models\MasterEdpmButir;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public $komponens;

    // Form fields for Komponen
    public $komponen_nama;
    public $komponen_id;
    public $komponen_ipr;

    // Form fields for Butir
    public $butir_id;
    public $butir_komponen_id;
    public $butir_no_sk;
    public $butir_nomor_butir;
    public $butir_pernyataan;

    public $modalTitle = '';
    public $activeModal = ''; // 'komponen' or 'butir'

    public $activeTab = 'edpm'; // edpm or ipr

    public function mount()
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }
        $this->loadData();
    }

    public function loadData()
    {
        $masterEdpmService = app(\App\Services\MasterEdpmService::class);
        $this->komponens = $masterEdpmService->getKomponensData();
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function resetKomponenForm()
    {
        $this->komponen_nama = '';
        $this->komponen_id = null;
        $this->komponen_ipr = false;
        $this->resetErrorBag();
    }

    public function resetButirForm()
    {
        $this->butir_id = null;
        $this->butir_no_sk = '';
        $this->butir_nomor_butir = '';
        $this->butir_pernyataan = '';
        $this->resetErrorBag();
    }

    public function openKomponenModal($id = null)
    {
        $this->resetKomponenForm();
        if ($id) {
            $masterEdpmService = app(\App\Services\MasterEdpmService::class);
            $komponen = $masterEdpmService->findKomponen($id);
            if ($komponen) {
                $this->komponen_id = $komponen->id;
                $this->komponen_nama = $komponen->nama;
                $this->komponen_ipr = $komponen->ipr == 1;
                $this->modalTitle = 'Edit Komponen';
            }
        } else {
            $this->modalTitle = 'Tambah Komponen';
            $this->komponen_ipr = ($this->activeTab === 'ipr');
        }
        $this->activeModal = 'komponen';
        $this->dispatch('open-modal', 'edpm-komponen-modal');
    }

    public function saveKomponen()
    {
        $this->validate(['komponen_nama' => 'required|string|max:255']);

        $masterEdpmService = app(\App\Services\MasterEdpmService::class);
        $masterEdpmService->saveKomponen([
            'nama' => $this->komponen_nama,
            'ipr' => $this->komponen_ipr ? 1 : NULL
        ], $this->komponen_id);

        session()->flash('status', 'Komponen berhasil disimpan.');
        $this->loadData();
        $this->dispatch('close-modal', 'edpm-komponen-modal');
    }

    public function deleteKomponen($id)
    {
        $masterEdpmService = app(\App\Services\MasterEdpmService::class);
        $masterEdpmService->deleteKomponen($id);
        $this->loadData();
        session()->flash('status', 'Komponen berhasil dihapus.');
    }

    public function openButirModal($komponenId, $butirId = null)
    {
        $this->resetButirForm();
        $this->butir_komponen_id = $komponenId;

        if ($butirId) {
            $masterEdpmService = app(\App\Services\MasterEdpmService::class);
            $butir = $masterEdpmService->findButir($butirId);
            if ($butir) {
                $this->butir_id = $butir->id;
                $this->butir_no_sk = $butir->no_sk;
                $this->butir_nomor_butir = $butir->nomor_butir;
                $this->butir_pernyataan = $butir->butir_pernyataan;
                $this->modalTitle = 'Edit Butir Pernyataan';
            }
        } else {
            $this->modalTitle = 'Tambah Butir Pernyataan';
        }
        $this->activeModal = 'butir';
        $this->dispatch('open-modal', 'edpm-butir-modal');
    }

    public function saveButir()
    {
        $this->validate([
            'butir_nomor_butir' => 'required|string',
            'butir_pernyataan' => 'required|string',
        ]);

        $masterEdpmService = app(\App\Services\MasterEdpmService::class);
        $masterEdpmService->saveButir([
            'komponen_id' => $this->butir_komponen_id,
            'no_sk' => $this->butir_no_sk,
            'nomor_butir' => $this->butir_nomor_butir,
            'butir_pernyataan' => $this->butir_pernyataan,
        ], $this->butir_id);

        session()->flash('status', 'Butir pernyataan berhasil disimpan.');
        $this->loadData();
        $this->dispatch('close-modal', 'edpm-butir-modal');
    }

    public function deleteButir($id)
    {
        $masterEdpmService = app(\App\Services\MasterEdpmService::class);
        $masterEdpmService->deleteButir($id);
        $this->loadData();
        session()->flash('status', 'Butir pernyataan berhasil dihapus.');
    }
}; ?>

<div>
    <x-slot name="header">{{ __('Master Komponen') }}</x-slot>

    <div class="py-12" x-data="deleteConfirmation">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <!-- Session Status -->
                <x-auth-session-status class="mb-4" :status="session('status')" />

                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
                    <h2 class="text-xl font-extrabold text-[#111827]">Master Komponen & Butir</h2>

                    <div class="flex flex-wrap items-center gap-2">
                        <button wire:click="openKomponenModal()" class="bg-[#1e3a5f] text-white px-4 py-2 rounded-lg text-xs font-bold flex items-center gap-2 hover:bg-[#162d4a] transition-all shadow-sm active:scale-95">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Tambah Komponen
                        </button>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="mb-4 border-b border-gray-200">
                    <ul class="flex flex-wrap -mb-px text-sm font-medium text-center text-gray-500">
                        <li class="me-2">
                            <button wire:click="setTab('edpm')"
                                class="inline-block p-4 border-b-2 rounded-t-lg {{ $activeTab === 'edpm' ? 'text-indigo-600 border-indigo-600' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">
                                KOMPONEN EDPM
                            </button>
                        </li>
                        <li class="me-2">
                            <button wire:click="setTab('ipr')"
                                class="inline-block p-4 border-b-2 rounded-t-lg {{ $activeTab === 'ipr' ? 'text-indigo-600 border-indigo-600' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">
                                KOMPONEN IPR
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="space-y-8">
                    @php
                    $filteredKomponens = $komponens->filter(function($k) use ($activeTab) {
                    if ($activeTab === 'ipr') {
                    return $k->nama === 'INDIKATOR PEMENUHAN RELATIF';
                    } else {
                    return $k->nama !== 'INDIKATOR PEMENUHAN RELATIF';
                    }
                    });
                    @endphp

                    @forelse ($filteredKomponens as $komponen)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100 mb-8">
                        <div class="bg-gray-50/50 px-6 py-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-gray-100">
                            <div class="flex items-center gap-3">
                                <div class="w-2 h-8 bg-indigo-500 rounded-full"></div>
                                <div>
                                    <h4 class="font-bold text-[#111827] uppercase tracking-wide text-sm">{{ $komponen->nama }}</h4>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button wire:click="openButirModal({{ $komponen->id }})"
                                    class="inline-flex items-center gap-2 px-3 py-1.5 text-[11px] font-bold text-indigo-600 hover:text-indigo-800 transition-colors bg-indigo-50/50 rounded-lg hover:bg-indigo-100 uppercase">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Tambah Butir
                                </button>
                                <button wire:click="openKomponenModal({{ $komponen->id }})"
                                    class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors" title="Edit Komponen">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button @click="confirmDelete({{ $komponen->id }}, 'deleteKomponen', 'Hapus seluruh komponen dan butir di dalamnya?')"
                                    class="p-1.5 text-rose-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Hapus Komponen">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-100">
                                <thead>
                                    <tr class="bg-gray-50/20">
                                        <th class="px-6 py-3 text-left text-[11px] font-bold text-gray-400 uppercase tracking-widest w-24">No SK</th>
                                        <th class="px-6 py-3 text-left text-[11px] font-bold text-gray-400 uppercase tracking-widest w-24">No Butir</th>
                                        <th class="px-6 py-3 text-left text-[11px] font-bold text-gray-400 uppercase tracking-widest pl-6">Butir Pernyataan</th>
                                        <th class="px-6 py-3 text-right text-[11px] font-bold text-gray-400 uppercase tracking-widest pr-8 w-28">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @forelse($komponen->butirs as $butir)
                                    <tr class="hover:bg-gray-50/50 transition-colors duration-150 group border-b border-gray-50 last:border-0">
                                        <td class="px-6 py-4 text-xs font-bold text-gray-400">{{ $butir->no_sk }}</td>
                                        <td class="px-6 py-4 text-xs font-bold text-indigo-600 bg-indigo-50/30">{{ $butir->nomor_butir }}</td>
                                        <td class="px-6 py-4 text-xs text-[#374151] leading-relaxed">{{ $butir->butir_pernyataan }}</td>
                                        <td class="px-6 py-4 text-right pr-6 whitespace-nowrap">
                                            <div class="flex items-center justify-end gap-2">
                                                <button wire:click="openButirModal({{ $komponen->id }}, {{ $butir->id }})"
                                                    class="p-1.5 text-indigo-500 hover:bg-indigo-50 rounded-lg transition-colors" title="Edit Butir">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </button>
                                                <button @click="confirmDelete({{ $butir->id }}, 'deleteButir', 'Hapus butir ini?')"
                                                    class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg transition-colors" title="Hapus Butir">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-12 text-center">
                                            <div class="flex flex-col items-center gap-2">
                                                <svg class="w-8 h-8 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <p class="text-[11px] text-gray-400 font-bold uppercase tracking-widest">Belum ada butir pernyataan.</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-10 text-gray-500 border-2 border-dashed rounded-lg">
                        Belum ada master data untuk tab ini ({{ $activeTab == 'edpm' ? 'Komponen EDPM' : 'Komponen IPR' }}).
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Komponen -->
    <x-modal name="edpm-komponen-modal" focusable>
        <form wire:submit="saveKomponen" class="p-6">
            <h2 class="text-lg font-medium text-gray-900">{{ $modalTitle }}</h2>
            <div class="mt-6 space-y-4">
                <div>
                    <x-input-label for="komponen_nama" value="Nama Komponen" />
                    <x-text-input wire:model="komponen_nama" id="komponen_nama" class="mt-1 block w-full" placeholder="Contoh: MUTU LULUSAN" required />
                    <x-input-error :messages="$errors->get('komponen_nama')" class="mt-2" />
                </div>

                @if($activeTab === 'ipr')
                <div class="flex items-center gap-3 bg-gray-50 border rounded-lg p-3 hidden">
                    <input type="checkbox" wire:model="komponen_ipr" id="komponen_ipr" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 w-5 h-5">
                    <div>
                        <x-input-label for="komponen_ipr" value="Komponen IPR" class="font-bold text-gray-800" />
                        <p class="text-[10px] text-gray-500">Centang jika komponen ini termasuk dalam Indikator Pemenuhan Relatif (IPR)</p>
                    </div>
                </div>
                @endif
            </div>
            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                <x-primary-button class="ms-3">Simpan</x-primary-button>
            </div>
        </form>
    </x-modal>

    <!-- Modal Butir -->
    <x-modal name="edpm-butir-modal" focusable>
        <form wire:submit="saveButir" class="p-6">
            <h2 class="text-lg font-medium text-gray-900">{{ $modalTitle }}</h2>
            <div class="mt-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="butir_no_sk" value="No SK" />
                        <x-text-input wire:model="butir_no_sk" id="butir_no_sk" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('butir_no_sk')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="butir_nomor_butir" value="Nomor Butir" />
                        <x-text-input wire:model="butir_nomor_butir" id="butir_nomor_butir" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('butir_nomor_butir')" class="mt-2" />
                    </div>
                </div>
                <div>
                    <x-input-label for="butir_pernyataan" value="Butir Pernyataan" />
                    <textarea wire:model="butir_pernyataan" id="butir_pernyataan" rows="4" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required></textarea>
                    <x-input-error :messages="$errors->get('butir_pernyataan')" class="mt-2" />
                </div>
            </div>
            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                <x-primary-button class="ms-3">Simpan Butir</x-primary-button>
            </div>
        </form>
    </x-modal>
</div>