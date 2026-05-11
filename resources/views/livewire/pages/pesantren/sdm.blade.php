<?php

use App\Models\SdmPesantren;
use App\Models\Pesantren;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Str;

new #[Layout('layouts.app')] class extends Component {
    public $data = [];
    public $levels = [];
    public $unitIds = [];
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
        'tendik_p'
    ];

    public $categories = [
        ['key' => 'santri', 'label' => 'Santri', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
        ['key' => 'ustadz_dirosah', 'label' => 'Ustadz Dirosah', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
        ['key' => 'ustadz_non_dirosah', 'label' => 'Ustadz Non Dirosah', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
        ['key' => 'pamong', 'label' => 'Pamong', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
        ['key' => 'musyrif', 'label' => 'Musyrif/Musyrifah', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
        ['key' => 'tendik', 'label' => 'Tenaga Kependidikan', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
    ];

    public function mount()
    {
        if (!auth()->user()->isPesantren()) {
            abort(403);
        }

        $pesantrenService = app(\App\Services\PesantrenService::class);
        $pesantren = $pesantrenService->getProfile(auth()->id());

        if ($pesantren) {
            $this->levels = $pesantren->units->pluck('unit')->toArray();
            $this->unitIds = $pesantren->units->pluck('id', 'unit')->toArray();
        }

        $existingData = $pesantrenService->getSdm(auth()->id());

        foreach ($this->levels as $level) {
            foreach ($this->fields as $field) {
                $this->data[$level][$field] = $existingData->has($level) ? $existingData[$level]->$field : 0;
            }
        }
    }

    public function save()
    {
        $pesantrenService = app(\App\Services\PesantrenService::class);
        if (auth()->user()->pesantren->is_locked) {
            $this->dispatch('show-metronic-alert', type: 'error', title: 'Akses Ditolak', message: 'Data terkunci karena sedang dalam proses akreditasi.');
            return;
        }

        foreach ($this->levels as $level) {
            $unitId = $this->unitIds[$level] ?? null;
            $dataToSave = array_merge($this->data[$level], ['pesantren_unit_id' => $unitId]);
            $pesantrenService->updateSdm(auth()->id(), $level, $dataToSave);
        }

        $this->dispatch('notification-received', type: 'success', title: 'Berhasil!', message: 'Data SDM berhasil disimpan.');
    }

    public function getCategoryTotal($categoryKey, $fieldSuffix)
    {
        $field = $categoryKey . '_' . $fieldSuffix;
        $total = 0;
        foreach ($this->levels as $level) {
            $total += (int)($this->data[$level][$field] ?? 0);
        }
        return $total;
    }

    public function getGrandTotal($categoryKey)
    {
        return $this->getCategoryTotal($categoryKey, 'l') + $this->getCategoryTotal($categoryKey, 'p');
    }
}; ?>

<div class="py-12" x-data="sdmManagement">
    <x-slot name="header">
        {{ __('Data SDM Pesantren') }}
    </x-slot>

    <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
        @if(auth()->user()->pesantren->is_locked)
        <div class="mb-8 bg-rose-50 border border-rose-100 rounded-2xl p-5 flex gap-4 items-center">
            <div class="w-12 h-12 rounded-xl bg-white border border-rose-100 flex items-center justify-center text-rose-500 shadow-sm animate-pulse">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <div>
                <h4 class="text-sm font-bold text-rose-800 uppercase tracking-tight">Data Terkunci!</h4>
                <p class="text-xs text-rose-600/80 font-medium leading-relaxed">Status data sedang dalam proses akreditasi dan tidak dapat diubah untuk sementara waktu.</p>
            </div>
        </div>
        @endif

        <div class="mb-8">
            <div class="flex items-center justify-between mb-2 pl-1">
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">{{ __('Rekapitulasi Data SDM') }}</h3>
                <div class="h-0.5 flex-1 bg-gradient-to-r from-slate-100 to-transparent ml-4"></div>
            </div>
        </div>

        <div class="space-y-6">
            @foreach($categories as $category)
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden transition-all duration-300" x-data="{ expanded: true }">
                <!-- Header -->
                <div class="px-6 py-5 bg-slate-50/50 flex items-center justify-between border-b border-slate-100 group cursor-pointer" @click="expanded = !expanded">
                    <div class="flex items-center gap-4">
                        <div class="w-11 h-11 rounded-2xl bg-white border border-slate-200 flex items-center justify-center text-[#1e3a5f] shadow-sm transform group-hover:scale-105 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $category['icon'] }}" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-800 uppercase tracking-widest text-sm">{{ $category['label'] }}</h4>
                        </div>
                    </div>
                    <button class="w-8 h-8 rounded-full hover:bg-slate-100 flex items-center justify-center text-slate-400 transition-colors" :class="{ 'rotate-180': expanded }">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>

                <!-- Content -->
                <div x-show="expanded" x-collapse>
                    <div class="p-6">
                        <div class="space-y-5">
                            @foreach($levels as $level)
                            <div class="bg-slate-50/30 rounded-2xl p-5 border border-slate-100/80 group">
                                <div class="flex items-center gap-2 mb-4">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $category['icon'] }}" />
                                    </svg>
                                    <span class="text-[11px] font-black text-slate-500 uppercase tracking-[0.2em]">{{ Str::of($level)->replace('_', ' ')->upper() }}</span>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div class="space-y-1.5">
                                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest pl-1">Laki-laki</label>
                                        <div class="relative group/input">
                                            <input type="number"
                                                wire:model.live="data.{{ $level }}.{{ $category['key'] }}_l"
                                                class="w-full bg-white border border-slate-200 rounded-xl py-2.5 px-4 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-[#1e3a5f]/20 focus:border-[#1e3a5f] transition-all"
                                                placeholder="0"
                                                {{ auth()->user()->pesantren->is_locked ? 'disabled' : '' }}>
                                        </div>
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest pl-1">Perempuan</label>
                                        <div class="relative group/input">
                                            <input type="number"
                                                wire:model.live="data.{{ $level }}.{{ $category['key'] }}_p"
                                                class="w-full bg-white border border-slate-200 rounded-xl py-2.5 px-4 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-[#1e3a5f]/20 focus:border-[#1e3a5f] transition-all"
                                                placeholder="0"
                                                {{ auth()->user()->pesantren->is_locked ? 'disabled' : '' }}>
                                        </div>
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest pl-1">Total</label>
                                        <div class="w-full bg-slate-100/50 border border-slate-200/50 rounded-xl py-2.5 px-4 text-sm font-black text-[#1e3a5f] text-center shadow-inner">
                                            {{ (int)($data[$level][$category['key'].'_l'] ?? 0) + (int)($data[$level][$category['key'].'_p'] ?? 0) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach

                            <!-- Summary Box -->
                            <div class="mt-8 bg-[#eef7ff] rounded-[2rem] p-8 border border-[#d9ebf8]">
                                <div class="flex items-center gap-3 mb-6 font-bold text-[#5e718d]">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    <h5 class="text-sm">Total Keseluruhan {{ $category['label'] }}</h5>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                                    <div class="space-y-2">
                                        <label class="block text-sm font-bold text-[#5e718d]">Laki-laki</label>
                                        <div class="bg-white border border-[#e2eaf2] rounded-lg py-3 px-4 text-sm font-medium text-slate-500 shadow-sm">
                                            {{ $this->getCategoryTotal($category['key'], 'l') }}
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="block text-sm font-bold text-[#5e718d]">Perempuan</label>
                                        <div class="bg-white border border-[#e2eaf2] rounded-lg py-3 px-4 text-sm font-medium text-slate-500 shadow-sm">
                                            {{ $this->getCategoryTotal($category['key'], 'p') }}
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="block text-sm font-bold text-[#5e718d]">Total</label>
                                        <div class="bg-white border border-[#e2eaf2] rounded-lg py-3 px-4 text-sm font-medium text-slate-500 shadow-sm">
                                            {{ $this->getGrandTotal($category['key']) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-12 flex items-center justify-end gap-3 pb-20">
            <button type="button"
                class="px-8 py-3 rounded-2xl bg-white border border-slate-200 text-slate-500 text-[11px] font-black uppercase tracking-[0.2em] transition-all">
                Batal
            </button>
            <button type="button"
                @click="confirmSave($wire)"
                class="px-10 py-3 rounded-2xl bg-gray-900 text-white text-[11px] font-black uppercase tracking-[0.2em] transition-all flex items-center gap-3 disabled:opacity-50 disabled:cursor-not-allowed"
                {{ auth()->user()->pesantren->is_locked ? 'disabled' : '' }}>
                <span>Simpan Perubahan</span>
                <svg wire:loading wire:target="save" class="animate-spin h-4 w-4 text-white" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </button>
        </div>
    </div>
</div>
