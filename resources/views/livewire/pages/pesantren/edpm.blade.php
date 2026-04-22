<?php

use App\Models\MasterEdpmKomponen;
use App\Models\Edpm;
use App\Models\EdpmCatatan;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Str;

new #[Layout('layouts.app')] class extends Component {
    public $komponens;
    public $evaluasis = []; // butir_id => isian
    public $links = [];     // butir_id => link
    public $catatans = [];  // komponen_id => catatan
    public $activeStep = 0;

    public function mount()
    {
        if (!auth()->user()->isPesantren()) {
            abort(403);
        }

        $this->loadData();
    }

    public function loadData()
    {
        $pesantrenService = app(\App\Services\PesantrenService::class);
        $data = $pesantrenService->getEdpmData(auth()->id());

        $this->komponens = $data['komponens'];
        $existingEdpms = $data['existingEdpms'];
        $existingCatatans = $data['existingCatatans'];

        foreach ($this->komponens as $komponen) {
            $this->catatans[$komponen->id] = $existingCatatans[$komponen->id] ?? '';
            foreach ($komponen->butirs as $butir) {
                $this->evaluasis[$butir->id] = $existingEdpms[$butir->id]->isian ?? '';
                $this->links[$butir->id] = $existingEdpms[$butir->id]->link ?? '';
            }
        }
    }

    public function nextStep()
    {
        // Validation for current step
        if (isset($this->komponens[$this->activeStep])) {
            $currentKomponen = $this->komponens[$this->activeStep];
            $rules = [];
            $messages = [];

            foreach ($currentKomponen->butirs as $butir) {
                $rules['evaluasis.' . $butir->id] = 'required|numeric|min:1|max:4';
                $rules['links.' . $butir->id] = 'required|url';
                $messages['evaluasis.' . $butir->id . '.required'] = 'Harap pilih nilai evaluasi untuk butir ' . $butir->nomor_butir;
                $messages['evaluasis.' . $butir->id . '.numeric'] = 'Nilai harus berupa angka.';
                $messages['evaluasis.' . $butir->id . '.min'] = 'Nilai minimal adalah 1.';
                $messages['evaluasis.' . $butir->id . '.max'] = 'Nilai maksimal adalah 4.';
                $messages['links.' . $butir->id . '.required'] = 'Harap isi tautan bukti untuk butir ' . $butir->nomor_butir;
                $messages['links.' . $butir->id . '.url'] = 'Format tautan bukti tidak valid (harus berupa URL valid).';
            }

            try {
                $this->validate($rules, $messages);
            } catch (\Illuminate\Validation\ValidationException $e) {
                $errorMessages = collect($e->errors())->flatten()->toArray();
                session()->flash('validation_errors', $errorMessages);
                $this->dispatch('show-validation-error');
                return;
            }
        }

        if ($this->activeStep < count($this->komponens) - 1) {
            $this->activeStep++;
        }
    }

    public function prevStep()
    {
        if ($this->activeStep > 0) {
            $this->activeStep--;
        }
    }

    public function setStep($step)
    {
        if ($step >= 0 && $step < count($this->komponens)) {
            $this->activeStep = $step;
        }
    }

    public function save()
    {
        $pesantrenService = app(\App\Services\PesantrenService::class);
        if (auth()->user()->pesantren->is_locked) {
            $this->js("Swal.fire({
                icon: 'error',
                title: 'Akses Ditolak',
                text: 'Data terkunci karena sedang dalam proses akreditasi.',
                confirmButtonColor: '#d33'
            })");
            return;
        }

        $rules = [
            'catatans.*' => 'nullable|string',
        ];
        $messages = [];

        foreach ($this->komponens as $komponen) {
            foreach ($komponen->butirs as $butir) {
                $rules['evaluasis.' . $butir->id] = 'required|numeric|min:1|max:4';
                $rules['links.' . $butir->id] = 'required|url';

                $messages['evaluasis.' . $butir->id . '.required'] = 'Harap pilih nilai evaluasi untuk butir ' . $butir->nomor_butir;
                $messages['evaluasis.' . $butir->id . '.numeric'] = 'Nilai harus berupa angka pada butir ' . $butir->nomor_butir;
                $messages['evaluasis.' . $butir->id . '.min'] = 'Nilai minimal adalah 1 pada butir ' . $butir->nomor_butir;
                $messages['evaluasis.' . $butir->id . '.max'] = 'Nilai maksimal adalah 4 pada butir ' . $butir->nomor_butir;

                $messages['links.' . $butir->id . '.required'] = 'Harap isi tautan bukti untuk butir ' . $butir->nomor_butir;
                $messages['links.' . $butir->id . '.url'] = 'Format tautan bukti tidak valid pada butir ' . $butir->nomor_butir;
            }
        }

        try {
            $this->validate($rules, $messages);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errorMessages = collect($e->errors())->flatten()->toArray();
            session()->flash('validation_errors', $errorMessages);
            $this->dispatch('show-validation-error');
            return;
        }

        if ($pesantrenService->saveEdpmEvaluation(auth()->id(), $this->evaluasis, $this->links, $this->catatans)) {
            session()->flash('status', 'Evaluasi EDPM berhasil disimpan.');
            $this->dispatch('notification-received', title: 'Berhasil', message: 'Evaluasi EDPM berhasil disimpan.');
        }
    }

    public function saveDraft()
    {
        $pesantrenService = app(\App\Services\PesantrenService::class);
        if (auth()->user()->pesantren->is_locked) {
            $this->js("Swal.fire({
                icon: 'error',
                title: 'Akses Ditolak',
                text: 'Data terkunci karena sedang dalam proses akreditasi.',
                confirmButtonColor: '#d33'
            })");
            return;
        }

        // Validate formats if present, but don't require values
        $this->validate([
            'evaluasis.*' => 'nullable|numeric|min:1|max:4',
            'links.*' => 'nullable|url',
            'catatans.*' => 'nullable|string',
        ]);

        if ($pesantrenService->saveEdpmDraft(auth()->id(), $this->evaluasis, $this->links, $this->catatans)) {
            $this->dispatch('notification-received', title: 'Draft Disimpan', message: 'Draft evaluasi EDPM berhasil disimpan.');
        }
    }

    public function isStepComplete($index)
    {
        if (!isset($this->komponens[$index])) return false;

        foreach ($this->komponens[$index]->butirs as $butir) {
            // Check if evaluation value exists and is not empty or null
            if (!isset($this->evaluasis[$butir->id]) || $this->evaluasis[$butir->id] === '') {
                return false;
            }
            if (!isset($this->links[$butir->id]) || $this->links[$butir->id] === '') {
                return false;
            }
        }
        return true;
    }
}; ?>

<div class="py-12" x-data="edpmManagement">
    <x-slot name="header">{{ __('Evaluasi Data Pesantren Muhammadiyah (EDPM)') }}</x-slot>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 overflow-x-auto">
                <header class="mb-6">
                    <h2 class="text-lg md:text-xl font-bold text-gray-800 border-l-4 border-indigo-600 pl-3">
                        {{ __('Evaluasi Data Pesantren Muhammadiyah (EDPM)') }}
                    </h2>
                </header>

                @php $isLocked = auth()->user()->pesantren->is_locked; @endphp

                @if($isLocked)
                <div class="mb-6 flex items-center gap-4 bg-amber-50 border border-amber-200 rounded-2xl px-5 py-4 shadow-sm">
                    <div class="flex-shrink-0 w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-black text-amber-800 uppercase tracking-wide">Data Terkunci</p>
                        <p class="text-xs text-amber-700 mt-0.5">Data EDPM tidak dapat diubah karena pesantren sedang dalam proses akreditasi.</p>
                    </div>
                </div>
                @endif

                @if($komponens && count($komponens) > 0)
                <!-- Stepper Headers -->
                <div class="mb-8 overflow-x-auto md:overflow-hidden pb-16 md:pb-20">
                    <div class="flex items-center justify-between md:justify-center md:flex-wrap gap-y-16 md:gap-y-20 relative min-w-[600px] md:min-w-full px-2">
                        <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-1 bg-gray-200 -z-10 md:hidden"></div>
                        @foreach($komponens as $index => $komponen)
                        @php
                        $isActive = $activeStep === $index;
                        $isComplete = $this->isStepComplete($index);

                        // Determain classes based on state
                        if ($isActive) {
                        $circleClasses = 'bg-indigo-600 text-white border-indigo-600 ring-2 ring-indigo-200';
                        $textClasses = 'text-indigo-600';
                        } elseif ($isComplete) {
                        $circleClasses = 'bg-green-100 text-green-600 border-green-500';
                        $textClasses = 'text-green-600';
                        } else {
                        $circleClasses = 'bg-white text-gray-400 border-gray-300';
                        $textClasses = 'text-gray-400';
                        }
                        @endphp
                        <div class="relative flex flex-col items-center group cursor-default bg-white px-2 md:w-1/4 lg:w-1/6">
                            <div class="w-8 h-8 md:w-10 md:h-10 rounded-full flex items-center justify-center font-bold text-xs md:text-sm border-2 transition-colors {{ $circleClasses }} z-10 mb-2">
                                {{ $index + 1 }}
                            </div>
                            <span class="text-[10px] md:text-xs font-semibold text-center w-full px-1 {{ $textClasses }} leading-tight">
                                {{ $komponen->nama }}
                            </span>
                        </div>
                        @endforeach
                    </div>

                </div>

                <div class="mt-4 mb-4">
                    <h3 class="text-base md:text-lg font-semibold text-gray-800 text-center border-b pb-2">
                        {{ $komponens[$activeStep]->nama ?? '' }}
                    </h3>
                </div>

                <form wire:submit.prevent>
                    <div class="space-y-6">
                        @if(isset($komponens[$activeStep]))
                        @php
                        $currentKomponen = $komponens[$activeStep];
                        @endphp

                        <!-- List Butir as Cards -->
                        <div class="grid gap-6">
                            @forelse($currentKomponen->butirs as $butir)
                            <div class="bg-white border rounded-lg shadow-sm hover:shadow-md transition-shadow p-4 md:p-6">
                                <div class="flex flex-col md:flex-row md:items-start gap-4">
                                    <!-- Badges -->
                                    <div class="flex flex-row md:flex-col gap-2 shrink-0">
                                        <div class="bg-gray-100 text-gray-600 text-xs font-bold px-3 py-1 rounded text-center whitespace-nowrap">
                                            SK: {{ $butir->no_sk }}
                                        </div>
                                        <div class="bg-indigo-50 text-indigo-700 text-xs font-bold px-3 py-1 rounded text-center whitespace-nowrap">
                                            No. {{ $butir->nomor_butir }}
                                        </div>
                                    </div>

                                    <!-- Content -->
                                    <div class="grow">
                                        <p class="text-sm md:text-base text-gray-800 leading-relaxed mb-4">
                                            {{ $butir->butir_pernyataan }}
                                        </p>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                                                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Pilih Nilai Evaluasi:</label>
                                                <select wire:model.live="evaluasis.{{ $butir->id }}"
                                                    @disabled($isLocked)
                                                    class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm @error('evaluasis.'.$butir->id) border-red-300 ring-red-200 @enderror {{ $isLocked ? 'opacity-50 cursor-not-allowed bg-gray-100' : '' }}">
                                                    <option value="">-- Pilih Nilai --</option>
                                                    <option value="1">1</option>
                                                    <option value="2">2</option>
                                                    <option value="3">3</option>
                                                    <option value="4">4</option>
                                                </select>
                                                @error('evaluasis.'.$butir->id)
                                                <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                                                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Tautan Bukti (Wajib):</label>
                                                <input type="url" wire:model.live="links.{{ $butir->id }}"
                                                    placeholder="https://..."
                                                    @disabled($isLocked)
                                                    class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm @error('links.'.$butir->id) border-red-300 ring-red-200 @enderror {{ $isLocked ? 'opacity-50 cursor-not-allowed bg-gray-100' : '' }}">
                                                @error('links.'.$butir->id)
                                                <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="text-center py-10 text-gray-500 italic border-2 border-dashed rounded-lg">
                                Belum ada butir pernyataan untuk komponen ini.
                            </div>
                            @endforelse
                        </div>

                        <!-- Catatan Section (Only on Last Step) -->
                        @if($activeStep === count($komponens) - 1)
                        <div class="mt-12 space-y-4">
                            <h3 class="text-lg font-bold text-gray-900 border-l-4 border-blue-500 pl-3">
                                Catatan & Deskripsi Kinerja
                            </h3>
                            <p class="text-sm text-gray-600 mb-4">
                                Mohon lengkapi catatan evaluasi untuk setiap komponen berikut sebelum menyimpan.
                            </p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                @foreach($komponens as $komponen)
                                <div class="bg-blue-50 border border-blue-100 rounded-lg p-4">
                                    <label class="block text-sm font-bold text-gray-800 mb-2">
                                        {{ $komponen->nama }}
                                    </label>
                                    <textarea wire:model.live="catatans.{{ $komponen->id }}"
                                        @disabled($isLocked)
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm min-h-[100px] {{ $isLocked ? 'opacity-50 cursor-not-allowed bg-gray-100' : '' }}"
                                        placeholder="Catatan untuk {{ strtolower($komponen->nama) }}..."></textarea>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        @endif
                    </div>

                    <div class="mt-8 flex flex-col md:flex-row items-center justify-between gap-4 border-t pt-6">
                        <!-- Prev Button -->
                        <button type="button" wire:click="prevStep"
                            class="w-full md:w-auto bg-gray-100/50 text-gray-600 font-bold py-3 px-8 rounded-2xl transition-all {{ ($activeStep === 0 || $isLocked) ? 'opacity-50 cursor-not-allowed' : '' }}"
                            {{ ($activeStep === 0 || $isLocked) ? 'disabled' : '' }}>
                            &laquo; Sebelumnya
                        </button>

                        <div class="flex flex-col md:flex-row items-center gap-3 w-full md:w-auto">
                            @if($isLocked)
                            <!-- Locked State Buttons -->
                            <button type="button" disabled
                                class="w-full md:w-auto bg-gray-300 text-gray-400 text-[11px] font-black py-3 px-10 rounded-2xl flex items-center justify-center gap-2 uppercase tracking-widest cursor-not-allowed select-none">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                Data Terkunci
                            </button>
                            @else
                            <!-- Draft Button -->
                            <button type="button" wire:click="saveDraft" wire:loading.attr="disabled"
                                class="w-full md:w-auto bg-amber-500 text-white font-bold py-3 px-8 rounded-2xl transition-all flex items-center justify-center gap-2">
                                <svg wire:loading.remove wire:target="saveDraft" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                                </svg>
                                <svg wire:loading wire:target="saveDraft" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Simpan Draft</span>
                            </button>

                            <!-- Next / Save Button -->
                            @if ($activeStep === count($komponens) - 1)
                            <button type="button" @click="confirmSimpan($wire)" wire:loading.attr="disabled"
                                class="w-full md:w-auto bg-gray-900 text-white text-[11px] font-black py-3 px-10 rounded-2xl flex items-center justify-center gap-2 uppercase tracking-widest transition-all">
                                <span>Simpan Permanen EDPM</span>
                            </button>
                            @else
                            <button type="button" @click="validateAndNext($wire)" wire:loading.attr="disabled"
                                class="w-full md:w-auto bg-indigo-600 text-white font-bold py-3 px-8 rounded-2xl flex items-center justify-center gap-2 transition-all">
                                <span>Selanjutnya</span>
                                <svg wire:loading.remove wire:target="nextStep" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                                <svg wire:loading wire:target="nextStep" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </button>
                            @endif
                            @endif
                        </div>
                    </div>
                </form>
                @else
                <div class="text-center py-10">
                    <p class="text-gray-500 italic">Data komponen EDPM belum tersedia.</p>
                </div>
                @endif
            </div>
        </div>
    </div>

</div>