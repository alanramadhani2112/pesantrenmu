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

<div x-data="deleteConfirmation" data-module-page="master-edpm">
    <x-ui.index-layout
        title="Master Komponen"
        subtitle="Kelola komponen dan butir pernyataan EDPM/IPR."
    >
        <x-slot name="toolbar">
            <x-ui.button wire:click="openKomponenModal()" variant="primary" size="sm">
                <x-ui.icon name="plus" class="fs-4 me-1" />
                Tambah Komponen
            </x-ui.button>
        </x-slot>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <x-ui.tabs class="mb-6">
            <x-ui.tab :active="$activeTab === 'edpm'" wire:click="setTab('edpm')">
                Komponen EDPM
            </x-ui.tab>

            <x-ui.tab :active="$activeTab === 'ipr'" wire:click="setTab('ipr')">
                Komponen IPR
            </x-ui.tab>
        </x-ui.tabs>

        @php
            $filteredKomponens = $komponens->filter(function ($komponen) use ($activeTab) {
                return $activeTab === 'ipr'
                    ? $komponen->nama === 'INDIKATOR PEMENUHAN RELATIF'
                    : $komponen->nama !== 'INDIKATOR PEMENUHAN RELATIF';
            });
        @endphp

        <div class="d-flex flex-column gap-5 spm-master-edpm-list">
            @forelse ($filteredKomponens as $komponen)
                <x-ui.section-card :title="$komponen->nama" class="spm-edpm-component-card">
                    <x-slot name="toolbar">
                        <x-ui.button wire:click="openButirModal({{ $komponen->id }})" variant="light" size="sm">
                            <x-ui.icon name="plus" class="fs-4 me-1" />
                            Tambah Butir
                        </x-ui.button>

                        <x-ui.icon-button
                            icon="pencil"
                            label="Edit Komponen"
                            variant="primary"
                            wire:click="openKomponenModal({{ $komponen->id }})"
                        />

                        <x-ui.icon-button
                            icon="trash"
                            label="Hapus Komponen"
                            variant="danger"
                            x-on:click="confirmDelete({{ $komponen->id }}, 'deleteKomponen', 'Hapus seluruh komponen dan butir di dalamnya?')"
                        />
                    </x-slot>

                    <x-ui.simple-table class="border-0 rounded-0 spm-edpm-table-wrap" table-class="spm-edpm-table">
                        <thead>
                            <tr>
                                <x-ui.table-th :min-width="false" class="spm-edpm-col-sk">No SK</x-ui.table-th>
                                <x-ui.table-th :min-width="false" class="spm-edpm-col-number">No Butir</x-ui.table-th>
                                <x-ui.table-th :min-width="false" class="spm-edpm-col-statement">Butir Pernyataan</x-ui.table-th>
                                <x-ui.table-th :min-width="false" align="end" class="spm-edpm-col-action">Aksi</x-ui.table-th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($komponen->butirs as $butir)
                                <tr wire:key="butir-{{ $butir->id }}">
                                    <td class="fw-semibold text-muted spm-edpm-cell-sk">{{ $butir->no_sk ?: '-' }}</td>
                                    <td class="spm-edpm-cell-number">
                                        <x-ui.badge variant="primary">{{ $butir->nomor_butir }}</x-ui.badge>
                                    </td>
                                    <td class="text-gray-700 fw-semibold spm-edpm-statement">{{ $butir->butir_pernyataan }}</td>
                                    <td class="text-end spm-edpm-cell-action">
                                        <div class="d-flex align-items-center justify-content-end gap-2">
                                            <x-ui.icon-button
                                                icon="pencil"
                                                label="Edit Butir"
                                                variant="primary"
                                                wire:click="openButirModal({{ $komponen->id }}, {{ $butir->id }})"
                                            />

                                            <x-ui.icon-button
                                                icon="trash"
                                                label="Hapus Butir"
                                                variant="danger"
                                                x-on:click="confirmDelete({{ $butir->id }}, 'deleteButir', 'Hapus butir ini?')"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">
                                        <x-ui.empty-state
                                            title="Belum ada butir pernyataan"
                                            description="Tambahkan butir untuk komponen ini agar bisa digunakan pada EDPM/IPR."
                                            class="py-10"
                                        />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </x-ui.simple-table>
                </x-ui.section-card>
            @empty
                <x-ui.empty-state
                    title="Belum ada master data"
                    :description="$activeTab == 'edpm' ? 'Belum ada master data untuk tab Komponen EDPM.' : 'Belum ada master data untuk tab Komponen IPR.'"
                    class="py-12 border border-dashed rounded"
                />
            @endforelse
        </div>
    </x-ui.index-layout>

    <!-- Modal Komponen -->
    <x-ui.modal name="edpm-komponen-modal" focusable>
        <form x-on:submit.prevent="confirmAction('saveKomponen', 'Simpan komponen?', 'Komponen EDPM/IPR akan disimpan.')">
            <x-ui.modal-header
                :title="$modalTitle"
                subtitle="Atur nama komponen EDPM/IPR."
                icon="data"
            />

            <x-ui.modal-body>
                <x-ui.form-field
                    label="Nama Komponen"
                    for="komponen_nama"
                    :error="$errors->get('komponen_nama')"
                >
                    <x-ui.input
                        model="komponen_nama"
                        id="komponen_nama"
                        placeholder="Contoh: MUTU LULUSAN"
                        required
                    />
                </x-ui.form-field>

                @if($activeTab === 'ipr')
                    <x-ui.form-field
                        class="mb-0 d-none"
                        label="Komponen IPR"
                        for="komponen_ipr"
                        hint="Centang jika komponen ini termasuk dalam Indikator Pemenuhan Relatif (IPR)."
                    >
                        <x-ui.checkbox
                            model="komponen_ipr"
                            id="komponen_ipr"
                            label="Termasuk IPR"
                        />
                    </x-ui.form-field>
                @endif

            </x-ui.modal-body>

            <x-ui.modal-footer>
                <x-ui.button type="button" variant="light" x-on:click="$dispatch('close')">Batal</x-ui.button>
                <x-ui.button type="submit" variant="primary">Simpan</x-ui.button>
            </x-ui.modal-footer>
        </form>
    </x-ui.modal>

    <!-- Modal Butir -->
    <x-ui.modal name="edpm-butir-modal" focusable>
        <form x-on:submit.prevent="confirmAction('saveButir', 'Simpan butir?', 'Butir pernyataan akan disimpan.')">
            <x-ui.modal-header
                :title="$modalTitle"
                subtitle="Lengkapi nomor dan pernyataan butir."
                icon="document"
            />

            <x-ui.modal-body>
                <div class="row g-5">
                    <div class="col-md-6">
                        <x-ui.form-field
                            label="No SK"
                            for="butir_no_sk"
                            :error="$errors->get('butir_no_sk')"
                        >
                            <x-ui.input model="butir_no_sk" id="butir_no_sk" />
                        </x-ui.form-field>
                    </div>

                    <div class="col-md-6">
                        <x-ui.form-field
                            label="Nomor Butir"
                            for="butir_nomor_butir"
                            :error="$errors->get('butir_nomor_butir')"
                        >
                            <x-ui.input
                                model="butir_nomor_butir"
                                id="butir_nomor_butir"
                                required
                            />
                        </x-ui.form-field>
                    </div>
                </div>

                <x-ui.form-field
                    label="Butir Pernyataan"
                    for="butir_pernyataan"
                    :error="$errors->get('butir_pernyataan')"
                    class="mb-0"
                >
                    <x-ui.textarea
                        model="butir_pernyataan"
                        id="butir_pernyataan"
                        rows="4"
                        required
                    />
                </x-ui.form-field>
            </x-ui.modal-body>

            <x-ui.modal-footer>
                <x-ui.button type="button" variant="light" x-on:click="$dispatch('close')">Batal</x-ui.button>
                <x-ui.button type="submit" variant="primary">Simpan Butir</x-ui.button>
            </x-ui.modal-footer>
        </form>
    </x-ui.modal>
</div>
