<?php

use App\Models\SdmPesantren;
use App\Models\Pesantren;
use App\Traits\ChecksSectionLock;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Str;

new #[Layout('layouts.app')] class extends Component {
    use ChecksSectionLock;
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
        'tendik_p',
    ];

    public $categories = [
        ['key' => 'santri', 'label' => 'Santri'],
        ['key' => 'ustadz_dirosah', 'label' => 'Ustadz Dirosah'],
        ['key' => 'ustadz_non_dirosah', 'label' => 'Ustadz Non Dirosah'],
        ['key' => 'pamong', 'label' => 'Pamong'],
        ['key' => 'musyrif', 'label' => 'Musyrif / Musyrifah'],
        ['key' => 'tendik', 'label' => 'Tenaga Kependidikan'],
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
        if (!$this->isSectionEditable('sdm')) {
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

@php
    $isLocked = auth()->user()->pesantren->is_locked;
    $sdmLockStatus = $this->getSectionLockStatus('sdm');
@endphp

<x-slot name="header">{{ __('Data SDM Pesantren') }}</x-slot>

<x-ui.page
    title="Data SDM Pesantren"
    subtitle="Kelola rekap santri, ustadz, pamong, musyrif, dan tenaga kependidikan."
    data-module-page="pesantren-sdm"
    x-data="sdmManagement"
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

    <x-ui.page-help
        title="Panduan Data SDM Pesantren"
        :items="[
            'Isi rekap jumlah santri, ustadz, pamong, musyrif, dan tenaga kependidikan',
            'Data SDM dikelompokkan per unit/jenjang pendidikan di pesantren',
            'Pastikan angka yang dimasukkan sesuai dengan kondisi aktual pesantren',
            'Data SDM akan digunakan sebagai bahan penilaian dalam proses akreditasi',
        ]"
        dismiss-key="help-pesantren-sdm"
    />

    @php
        $grandTotal = collect($categories)->sum(fn ($category) => $this->getGrandTotal($category['key']));
        $unitCount = count($levels ?? []);
    @endphp

    <div class="row g-5 mb-6">
        <div class="col-lg-4">
            <x-ui.stat-card label="Status SDM" value="{{ $isLocked ? 'Terkunci' : 'Aktif' }}" variant="{{ $isLocked ? 'warning' : 'success' }}" icon="shield-tick" />
        </div>

        <div class="col-lg-4">
            <x-ui.stat-card label="Total Unit" value="{{ $unitCount }} Unit" variant="info" icon="building" />
        </div>

        <div class="col-lg-4">
            <x-ui.stat-card label="Total Rekap" value="{{ $grandTotal }} Data" variant="primary" icon="people" />
        </div>
    </div>

    @if($isLocked)
        @if($sdmLockStatus === 'unlocked_for_correction')
            <div class="spm-inline-alert" style="border-left: 4px solid #f59e0b; background: #fffbeb; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                <x-ui.icon name="shield-tick" class="fs-2 text-warning" />
                <div>
                    <div class="spm-inline-alert-title">🔓 Koreksi Tersedia</div>
                    <div class="spm-inline-alert-text">Data SDM dibuka untuk perbaikan sesuai catatan asesor.</div>
                </div>
            </div>
        @else
            <div class="spm-inline-alert">
                <x-ui.icon name="shield-tick" class="fs-2 text-warning" />
                <div>
                    <div class="spm-inline-alert-title">🔒 Data Terkunci</div>
                    <div class="spm-inline-alert-text">Status data sedang dalam proses akreditasi dan tidak dapat diubah untuk sementara waktu.</div>
                </div>
            </div>
        @endif
    @endif

    <form x-on:submit.prevent="confirmSave($wire)" class="d-flex flex-column gap-6">
        @foreach($categories as $category)
            <x-ui.section-card
                :title="$category['label']"
                subtitle="Input rekap per unit layanan pendidikan."
            >
                <x-slot:toolbar>
                    <x-ui.status-badge variant="info">
                        Total {{ $this->getGrandTotal($category['key']) }}
                    </x-ui.status-badge>
                </x-slot:toolbar>

                <div class="p-6">
                    @if(count($levels) > 0)
                    <x-ui.simple-table dense tableClass="spm-sdm-table">
                        <thead>
                            <tr>
                                <th class="ps-4">Unit</th>
                                <th class="text-center">Laki-laki</th>
                                <th class="text-center">Perempuan</th>
                                <th class="text-center pe-4">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($levels as $level)
                                @php
                                    $fieldPrefix = $category['key'];
                                    $maleModel = "data.{$level}.{$fieldPrefix}_l";
                                    $femaleModel = "data.{$level}.{$fieldPrefix}_p";
                                @endphp

                                <tr>
                                    <td class="ps-4 fw-bold text-gray-800">
                                        {{ Str::of($level)->replace('_', ' ')->title() }}
                                    </td>
                                    <td class="text-center">
                                        <x-ui.input
                                            type="number"
                                            :id="'sdm-' . $category['key'] . '-' . $level . '-l'"
                                            :model="$maleModel"
                                            modifier="live"
                                            class="spm-number-input mx-auto"
                                            placeholder="0"
                                            min="0"
                                            step="1"
                                            :disabled="$sdmLockStatus === 'locked'"
                                        />
                                    </td>
                                    <td class="text-center">
                                        <x-ui.input
                                            type="number"
                                            :id="'sdm-' . $category['key'] . '-' . $level . '-p'"
                                            :model="$femaleModel"
                                            modifier="live"
                                            class="spm-number-input mx-auto"
                                            placeholder="0"
                                            min="0"
                                            step="1"
                                            :disabled="$sdmLockStatus === 'locked'"
                                        />
                                    </td>
                                    <td class="text-center pe-4">
                                        <x-ui.badge variant="primary">
                                            {{ (int)($data[$level][$fieldPrefix . '_l'] ?? 0) + (int)($data[$level][$fieldPrefix . '_p'] ?? 0) }}
                                        </x-ui.badge>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td class="ps-4 fw-bold text-gray-900">Total Rekap</td>
                                <td class="text-center fw-bold text-primary">
                                    {{ $this->getCategoryTotal($category['key'], 'l') }}
                                </td>
                                <td class="text-center fw-bold text-primary">
                                    {{ $this->getCategoryTotal($category['key'], 'p') }}
                                </td>
                                <td class="text-center pe-4 fw-bold text-success">
                                    {{ $this->getGrandTotal($category['key']) }}
                                </td>
                            </tr>
                        </tfoot>
                    </x-ui.simple-table>
                    @else
                        <div class="text-center py-6">
                            <x-ui.icon name="information-2" class="fs-2hx text-gray-400 mb-3 d-block mx-auto" />
                            <div class="fw-bold text-gray-700 fs-6 mb-1">Belum ada unit pendidikan</div>
                            <div class="text-muted fs-7 mb-4">Pilih layanan satuan pendidikan di halaman Profil terlebih dahulu agar data SDM bisa diinput per unit.</div>
                            <x-ui.button :href="route('pesantren.profile')" variant="light-primary" size="sm">
                                <x-ui.icon name="pencil" class="fs-5 me-1" />
                                Atur di Profil
                            </x-ui.button>
                        </div>
                    @endif
                </div>
            </x-ui.section-card>
        @endforeach

        <div class="spm-action-panel d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4">
            <div>
                <h3 class="spm-card-title mb-1">Simpan Rekap SDM</h3>
                <div class="text-muted fw-semibold fs-7">Pastikan data SDM telah diisi lengkap sebelum menyimpan.</div>
            </div>

            <div class="d-flex align-items-center gap-2">
                @if($sdmLockStatus === 'locked')
                    <x-ui.button type="button" variant="warning" disabled>
                        <x-ui.icon name="shield-tick" class="fs-4 me-1" />
                        🔒 Data Terkunci
                    </x-ui.button>
                @else
                    <x-ui.button type="submit" variant="primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save">Simpan Perubahan</span>
                        <span wire:loading wire:target="save">Memproses...</span>
                    </x-ui.button>
                @endif
            </div>
        </div>
    </form>
</x-ui.page>
