<?php

use App\Models\MasterEdpmKomponen;
use App\Models\Edpm;
use App\Models\EdpmCatatan;
use App\Traits\ChecksSectionLock;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Str;

new #[Layout('layouts.app')] class extends Component {
    use ChecksSectionLock;
    public $komponens;
    public $evaluasis = [];
    public $links = [];
    public $catatans = [];
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
        if (!$this->hasAnyEdpmButirEditable()) {
            $this->dispatch('show-metronic-alert', type: 'error', title: 'Akses Ditolak', message: 'Data terkunci karena sedang dalam proses akreditasi.');
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
        if (!$this->hasAnyEdpmButirEditable()) {
            $this->dispatch('show-metronic-alert', type: 'error', title: 'Akses Ditolak', message: 'Data terkunci karena sedang dalam proses akreditasi.');
            return;
        }

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
        if (!isset($this->komponens[$index])) {
            return false;
        }

        foreach ($this->komponens[$index]->butirs as $butir) {
            if (!isset($this->evaluasis[$butir->id]) || $this->evaluasis[$butir->id] === '') {
                return false;
            }

            if (!isset($this->links[$butir->id]) || $this->links[$butir->id] === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if any EDPM butir is editable (either pesantren not locked or butir unlocked).
     */
    protected function hasAnyEdpmButirEditable(): bool
    {
        $pesantren = auth()->user()->pesantren;
        if (!$pesantren || !$pesantren->is_locked) {
            return true;
        }

        // Check if any butir is unlocked
        foreach ($this->komponens as $komponen) {
            foreach ($komponen->butirs as $butir) {
                if ($this->isSectionEditable('edpm.butir.' . $butir->id)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if a specific butir is editable.
     */
    public function isButirEditable(int $butirId): bool
    {
        return $this->isSectionEditable('edpm.butir.' . $butirId);
    }
}; ?>

@php
    $isLocked = auth()->user()->pesantren->is_locked;
    $currentKomponen = $komponens[$activeStep] ?? null;
    $komponenCount = is_countable($komponens ?? null) ? count($komponens) : 0;
@endphp

<x-slot name="header">{{ __('Evaluasi Data Pesantren Muhammadiyah (EDPM)') }}</x-slot>

<x-ui.page
    title="Evaluasi Data Pesantren Muhammadiyah (EDPM)"
    subtitle="Susun evaluasi per komponen, tautan bukti, dan catatan kinerja."
    data-module-page="pesantren-edpm"
    x-data="edpmManagement"
>
    <x-slot:toolbar>
        <x-ui.status-badge :variant="$isLocked ? 'warning' : 'success'">
            {{ $isLocked ? 'Terkunci' : 'Aktif' }}
        </x-ui.status-badge>

        <x-ui.button :href="route('pesantren.profile')" variant="light">
            <x-ui.icon name="exit-right" class="fs-4 me-1" />
            Kembali
        </x-ui.button>
    </x-slot:toolbar>

    <div class="row g-5 mb-6">
        <div class="col-lg-4">
            <x-ui.stat-card label="Status EDPM" value="{{ $isLocked ? 'Terkunci' : 'Aktif' }}" variant="{{ $isLocked ? 'warning' : 'success' }}">
                <x-slot:icon><x-ui.icon name="shield-tick" class="fs-2" /></x-slot:icon>
            </x-ui.stat-card>
        </div>

        <div class="col-lg-4">
            <x-ui.stat-card label="Komponen Aktif" value="{{ $komponenCount }} Komponen" variant="info">
                <x-slot:icon><x-ui.icon name="menu" class="fs-2" /></x-slot:icon>
            </x-ui.stat-card>
        </div>

        <div class="col-lg-4">
            <x-ui.stat-card label="Langkah Saat Ini" value="{{ $currentKomponen?->nama ?? 'Belum Ada Komponen' }}" variant="primary">
                <x-slot:icon><x-ui.icon name="layers" class="fs-2" /></x-slot:icon>
            </x-ui.stat-card>
        </div>
    </div>

    @if($isLocked)
        @php $hasAnyEdpmEditable = $this->hasAnyEdpmButirEditable(); @endphp
        @if($hasAnyEdpmEditable)
            <div class="spm-inline-alert" style="border-left: 4px solid #f59e0b; background: #fffbeb; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                <x-ui.icon name="shield-tick" class="fs-2 text-warning" />
                <div>
                    <div class="spm-inline-alert-title">🔓 Koreksi Tersedia</div>
                    <div class="spm-inline-alert-text">Beberapa butir EDPM dibuka untuk perbaikan. Butir yang ditandai 🔓 dapat diedit.</div>
                </div>
            </div>
        @else
            <div class="spm-inline-alert">
                <x-ui.icon name="shield-tick" class="fs-2 text-warning" />
                <div>
                    <div class="spm-inline-alert-title">🔒 Data Terkunci</div>
                    <div class="spm-inline-alert-text">Data EDPM tidak dapat diubah karena pesantren sedang dalam proses akreditasi.</div>
                </div>
            </div>
        @endif
    @endif

    @if($komponenCount > 0)
        <x-ui.section-card title="Tahapan EDPM" subtitle="Pilih komponen untuk mengisi nilai evaluasi dan tautan bukti.">
            <div class="p-6">
                <div class="spm-edpm-stepper">
                    @foreach($komponens as $index => $komponen)
                        @php
                            $isActive = $activeStep === $index;
                            $isComplete = $this->isStepComplete($index);
                            $variant = $isActive ? 'primary' : ($isComplete ? 'success' : 'light');
                            $badgeClass = $isActive ? 'badge badge-light-primary' : ($isComplete ? 'badge badge-light-success' : 'badge badge-light-secondary');
                        @endphp

                        <x-ui.button
                            type="button"
                            wire:click="setStep({{ $index }})"
                            :variant="$variant"
                            class="w-100"
                            size="sm"
                        >
                            <span class="{{ $badgeClass }} me-3">{{ $index + 1 }}</span>
                            <span class="d-flex flex-column text-start min-w-0">
                                <span class="fw-bold text-truncate">{{ $komponen->nama }}</span>
                                <span class="fs-8 opacity-75 text-truncate">{{ count($komponen->butirs) }} butir</span>
                            </span>
                        </x-ui.button>
                    @endforeach
                </div>
            </div>
        </x-ui.section-card>

        @if($currentKomponen)
            <x-ui.section-card
                :title="$currentKomponen->nama"
                subtitle="Isi evaluasi, tautan bukti, dan catatan untuk komponen yang sedang aktif."
            >
                <x-slot:toolbar>
                    <x-ui.status-badge :variant="$this->isStepComplete($activeStep) ? 'success' : 'warning'">
                        {{ $this->isStepComplete($activeStep) ? 'Lengkap' : 'Perlu Diisi' }}
                    </x-ui.status-badge>
                </x-slot:toolbar>

                <div class="p-6">
                    <x-ui.simple-table tableClass="spm-edpm-table">
                        <thead>
                            <tr>
                                <th class="ps-4">Butir</th>
                                <th>Pernyataan</th>
                                <th style="min-width: 220px;">Evaluasi</th>
                                <th style="min-width: 320px;" class="pe-4">Tautan Bukti</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($currentKomponen->butirs as $butir)
                                @php $butirEditable = $this->isButirEditable($butir->id); @endphp
                                <tr class="{{ ($isLocked && $butirEditable) ? 'bg-warning bg-opacity-10' : '' }}">
                                    <td class="ps-4 align-top">
                                        <div class="fw-bold text-gray-900">
                                            {{ $isLocked ? ($butirEditable ? '🔓 ' : '🔒 ') : '' }}{{ $butir->nomor_butir }}
                                        </div>
                                        <div class="text-muted fs-8">SK {{ $butir->no_sk }}</div>
                                    </td>
                                    <td class="align-top">
                                        <div class="spm-edpm-statement">{{ $butir->butir_pernyataan }}</div>
                                    </td>
                                    <td class="align-top">
                                        <x-ui.form-field
                                            label="Nilai"
                                            :for="'evaluasis-' . $butir->id"
                                            :error="$errors->get('evaluasis.' . $butir->id)"
                                        >
                                            <x-ui.select
                                                :id="'evaluasis-' . $butir->id"
                                                :model="'evaluasis.' . $butir->id"
                                                modifier="live"
                                                placeholder="Pilih nilai"
                                                :options="[1 => '1', 2 => '2', 3 => '3', 4 => '4']"
                                                class="spm-score-control"
                                                :disabled="$isLocked && !$butirEditable"
                                            />
                                        </x-ui.form-field>
                                    </td>
                                    <td class="align-top pe-4">
                                        <x-ui.form-field
                                            label="Tautan bukti"
                                            :for="'links-' . $butir->id"
                                            :error="$errors->get('links.' . $butir->id)"
                                        >
                                            <x-ui.input
                                                type="url"
                                                :id="'links-' . $butir->id"
                                                :model="'links.' . $butir->id"
                                                modifier="live"
                                                placeholder="https://..."
                                                :disabled="$isLocked && !$butirEditable"
                                            />
                                        </x-ui.form-field>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-ui.simple-table>
                </div>
            </x-ui.section-card>

            @if ($activeStep === $komponenCount - 1)
                <x-ui.section-card title="Catatan Kinerja Satuan Pendidikan" subtitle="Lengkapi catatan evaluasi untuk setiap komponen.">
                    <div class="p-6">
                        <div class="spm-input-grid">
                            @foreach($komponens as $komponen)
                                @php $komponenHasUnlocked = false;
                                    foreach ($komponen->butirs as $b) {
                                        if ($this->isButirEditable($b->id)) { $komponenHasUnlocked = true; break; }
                                    }
                                @endphp
                                <x-ui.form-field
                                    :label="$komponen->nama"
                                    :for="'catatans-' . $komponen->id"
                                    :error="$errors->get('catatans.' . $komponen->id)"
                                >
                                    <x-ui.textarea
                                        :id="'catatans-' . $komponen->id"
                                        :model="'catatans.' . $komponen->id"
                                        modifier="live"
                                        rows="4"
                                        placeholder="Catatan untuk {{ $komponen->nama }}"
                                        :disabled="$isLocked && !$komponenHasUnlocked"
                                    />
                                </x-ui.form-field>
                            @endforeach
                        </div>
                    </div>
                </x-ui.section-card>
            @endif

            <div class="spm-action-panel d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <h3 class="spm-card-title mb-1">Aksi EDPM</h3>
                    <div class="text-muted fw-semibold fs-7">
                        Gunakan draf untuk menyimpan sementara, lalu finalkan setelah semua komponen lengkap.
                    </div>
                </div>

                <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center gap-2">
                    <x-ui.button
                        type="button"
                        variant="light"
                        wire:click="prevStep"
                        wire:loading.attr="disabled"
                        :disabled="$activeStep === 0 || ($isLocked && !$this->hasAnyEdpmButirEditable())"
                    >
                        Sebelumnya
                    </x-ui.button>

                    @if(!$isLocked || $this->hasAnyEdpmButirEditable())
                        <x-ui.button
                            type="button"
                            variant="warning"
                            wire:loading.attr="disabled"
                            @click="confirmSaveDraft($wire)"
                        >
                            <span wire:loading.remove wire:target="saveDraft">Simpan Draf</span>
                            <span wire:loading wire:target="saveDraft">Memproses...</span>
                        </x-ui.button>
                    @endif

                    @if($activeStep === $komponenCount - 1)
                        <x-ui.button
                            type="button"
                            variant="success"
                            wire:loading.attr="disabled"
                            @click="confirmSimpan($wire)"
                            :disabled="$isLocked && !$this->hasAnyEdpmButirEditable()"
                        >
                            <span wire:loading.remove wire:target="save">Simpan Permanen</span>
                            <span wire:loading wire:target="save">Memproses...</span>
                        </x-ui.button>
                    @else
                        <x-ui.button
                            type="button"
                            variant="primary"
                            wire:loading.attr="disabled"
                            @click="validateAndNext($wire)"
                            :disabled="$isLocked && !$this->hasAnyEdpmButirEditable()"
                        >
                            <span wire:loading.remove wire:target="nextStep">Selanjutnya</span>
                            <span wire:loading wire:target="nextStep">Memproses...</span>
                        </x-ui.button>
                    @endif
                </div>
            </div>
        @endif
    @else
        <x-ui.card>
            <x-ui.empty-state title="Data Komponen Belum Tersedia" description="Pesantren ini belum memiliki komponen EDPM yang dapat diisi." />
        </x-ui.card>
    @endif
</x-ui.page>
