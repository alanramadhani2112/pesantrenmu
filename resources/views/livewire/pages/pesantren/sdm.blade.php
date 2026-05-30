<?php

use App\Models\SdmPesantren;
use App\Models\Pesantren;
use App\Traits\ChecksSectionLock;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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

    protected function rules(): array
    {
        $rules = [];

        foreach ($this->levels as $level) {
            foreach ($this->fields as $field) {
                $rules["data.{$level}.{$field}"] = 'required|integer|min:0|max:999999';
            }
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'integer' => ':attribute harus berupa angka bulat.',
            'min' => ':attribute minimal :min.',
            'max' => ':attribute maksimal :max.',
        ];
    }

    protected function validationAttributes(): array
    {
        $attributes = [];
        $fieldLabels = [
            'santri_l' => 'Santri laki-laki',
            'santri_p' => 'Santri perempuan',
            'ustadz_dirosah_l' => 'Ustadz dirosah laki-laki',
            'ustadz_dirosah_p' => 'Ustadz dirosah perempuan',
            'ustadz_non_dirosah_l' => 'Ustadz non dirosah laki-laki',
            'ustadz_non_dirosah_p' => 'Ustadz non dirosah perempuan',
            'pamong_l' => 'Pamong laki-laki',
            'pamong_p' => 'Pamong perempuan',
            'musyrif_l' => 'Musyrif laki-laki',
            'musyrif_p' => 'Musyrif perempuan',
            'tendik_l' => 'Tenaga kependidikan laki-laki',
            'tendik_p' => 'Tenaga kependidikan perempuan',
        ];

        foreach ($this->levels as $level) {
            $unitLabel = Str::of($level)->replace('_', ' ')->title();

            foreach ($this->fields as $field) {
                $attributes["data.{$level}.{$field}"] = ($fieldLabels[$field] ?? $field) . " unit {$unitLabel}";
            }
        }

        return $attributes;
    }

    public function save()
    {
        $pesantrenService = app(\App\Services\PesantrenService::class);
        if (!$this->isSectionEditable('sdm')) {
            $this->dispatch('show-metronic-alert', type: 'error', title: 'Akses Ditolak', message: 'Data terkunci karena sedang dalam proses akreditasi.');
            return;
        }

        if (count($this->levels) === 0) {
            $this->dispatch('show-metronic-alert', type: 'warning', title: 'Profil Belum Lengkap', message: 'Pilih layanan satuan pendidikan di Profil Pesantren sebelum mengisi Data SDM.');
            return;
        }

        try {
            $this->validate($this->rules(), $this->messages(), $this->validationAttributes());
        } catch (ValidationException $e) {
            $this->dispatch('show-validation-error');
            throw $e;
        }

        // PM-18 fix: kumpulkan semua data terlebih dahulu, lalu simpan dalam
        // satu DB::transaction agar partial-save tidak terjadi jika admin
        // mengunci pesantren di tengah loop.
        $allLevelData = [];
        foreach ($this->levels as $level) {
            $unitId = $this->unitIds[$level] ?? null;

            if (!$unitId) {
                $this->dispatch('show-metronic-alert', type: 'error', title: 'Unit Tidak Valid', message: 'Unit pendidikan tidak ditemukan. Perbarui Profil Pesantren sebelum menyimpan Data SDM.');
                return;
            }

            $values = [];
            foreach ($this->fields as $field) {
                $values[$field] = (int) ($this->data[$level][$field] ?? 0);
            }

            $allLevelData[] = [
                'tingkat' => $level,
                'data' => array_merge($values, ['pesantren_unit_id' => $unitId]),
            ];
        }

        $userId = auth()->id();
        $success = \Illuminate\Support\Facades\DB::transaction(function () use ($pesantrenService, $userId, $allLevelData) {
            foreach ($allLevelData as $item) {
                $result = $pesantrenService->updateSdm($userId, $item['tingkat'], $item['data']);
                if (! $result) {
                    return false;
                }
            }
            return true;
        });

        if ($success) {
            $this->dispatch('notification-received', type: 'success', title: 'Berhasil!', message: 'Data SDM berhasil disimpan.');
        } else {
            $this->dispatch('show-metronic-alert', type: 'error', title: 'Gagal', message: 'Data SDM gagal disimpan. Data mungkin terkunci atau terjadi kesalahan.');
        }
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
    $isLocked = auth()->user()->pesantren?->is_locked ?? false;
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
            <x-ui.alert variant="warning" icon="shield-tick" title="Koreksi Tersedia" class="mb-4">
                Data SDM dibuka untuk perbaikan sesuai catatan asesor.
            </x-ui.alert>
        @else
            <x-ui.alert variant="warning" icon="shield-tick" title="Data Terkunci">
                Status data sedang dalam proses akreditasi dan tidak dapat diubah untuk sementara waktu.
            </x-ui.alert>
        @endif
    @endif

    <form x-on:submit.prevent="confirmSave($wire)" class="d-flex flex-column gap-6">
        @if($errors->any())
            <x-ui.alert variant="danger" title="Data SDM belum valid" class="mb-0">
                <div class="mb-3">Periksa kembali angka yang ditandai sebelum menyimpan rekap SDM.</div>
                <ul class="mb-0 ps-4">
                    @foreach($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

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
                                    <td class="ps-4 fw-semibold text-gray-800">
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
                                            inputmode="numeric"
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
                                            inputmode="numeric"
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
                                <td class="ps-4 fw-semibold text-gray-900">Total Rekap</td>
                                <td class="text-center fw-semibold text-primary">
                                    {{ $this->getCategoryTotal($category['key'], 'l') }}
                                </td>
                                <td class="text-center fw-semibold text-primary">
                                    {{ $this->getCategoryTotal($category['key'], 'p') }}
                                </td>
                                <td class="text-center pe-4 fw-semibold text-success">
                                    {{ $this->getGrandTotal($category['key']) }}
                                </td>
                            </tr>
                        </tfoot>
                    </x-ui.simple-table>
                    @else
                        <div class="text-center py-6">
                            <x-ui.icon name="information-2" class="fs-2hx text-gray-400 mb-3 d-block mx-auto" />
                            <div class="fw-semibold text-gray-700 fs-6 mb-1">Belum ada unit pendidikan</div>
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
                @if(count($levels) === 0)
                    <x-ui.button type="button" variant="light" disabled>
                        Lengkapi Profil Dahulu
                    </x-ui.button>
                @elseif($sdmLockStatus === 'locked')
                    <x-ui.button type="button" variant="warning" disabled>
                        <x-ui.icon name="shield-tick" class="fs-4 me-1" />
                        Data Terkunci
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
