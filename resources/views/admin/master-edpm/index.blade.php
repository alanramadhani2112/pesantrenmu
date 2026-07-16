@extends('layouts.app')

@section('content')
<div x-data="masterEdpm()" data-module-page="master-edpm">
    <x-ui.index-layout
        title="Master Komponen"
        subtitle="Kelola komponen dan butir pernyataan EDPM/IPR."
    >
        <x-slot name="toolbar">
            <x-ui.button variant="primary" size="sm" icon="plus" x-on:click="openKomponenModal()">
                Tambah Komponen
            </x-ui.button>
        </x-slot>

        {{-- Tabs --}}
        <x-ui.tabs class="mb-5">
            <x-ui.tab
                :active="true"
                x-bind:class="{ 'active': activeTab === 'edpm' }"
                x-on:click.prevent="activeTab = 'edpm'"
            >Komponen EDPM</x-ui.tab>
            <x-ui.tab
                x-bind:class="{ 'active': activeTab === 'ipr' }"
                x-on:click.prevent="activeTab = 'ipr'"
            >Komponen IPR</x-ui.tab>
        </x-ui.tabs>

        {{-- Guide Perhitungan --}}
        <div class="row g-5 mb-6">
            <div class="col-12 col-xl-7">
                <x-ui.card title="Formula Perhitungan Skor Akreditasi" subtitle="Rumus yang diterapkan sistem untuk menghitung nilai akhir akreditasi pesantren." class="h-100">
                    <div class="d-flex flex-column gap-4">
                        <div class="spm-soft-panel">
                            <div class="spm-detail-label">Skor per Komponen EDPM</div>
                            <div class="spm-detail-value fw-semibold font-monospace">Skor = (Ci / Cmaks) × BK</div>
                            <div class="text-muted fs-8 mt-1">Ci = total skor butir, Cmaks = jumlah butir × 4, BK = bobot komponen</div>
                        </div>

                        <div class="spm-soft-panel">
                            <div class="spm-detail-label">Skor Akhir</div>
                            <div class="spm-detail-value fw-semibold font-monospace">Nilai = (EDPM × 70%) + (IPR × 30%)</div>
                            <div class="text-muted fs-8 mt-1">EDPM = rata-rata skor 4 komponen, IPR = skor komponen IPR skala ratusan</div>
                        </div>

                        <div class="spm-soft-panel">
                            <div class="spm-detail-label">Peringkat Akreditasi</div>
                            <div class="d-flex flex-wrap gap-2 mt-1">
                                <x-ui.badge variant="success">A (Unggul): 86–100</x-ui.badge>
                                <x-ui.badge variant="primary">B (Baik Sekali): 71–85</x-ui.badge>
                                <x-ui.badge variant="warning">C (Baik): &lt; 70</x-ui.badge>
                            </div>
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <div class="col-12 col-xl-5">
                <x-ui.card title="Tabel Bobot Komponen" subtitle="Bobot standar yang berlaku untuk seluruh pesantren." class="h-100">
                    <x-ui.simple-table class="mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Komponen</th>
                                <th class="text-center">Bobot (BK)</th>
                                <th class="text-center pe-4">Kontribusi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="ps-4 fw-semibold">Mutu Lulusan</td>
                                <td class="text-center">35</td>
                            <td class="text-center pe-4" rowspan="4"><x-ui.badge variant="primary">70%</x-ui.badge></td>
                            </tr>
                            <tr>
                                <td class="ps-4 fw-semibold">Proses Pembelajaran</td>
                                <td class="text-center">29</td>
                            </tr>
                            <tr>
                                <td class="ps-4 fw-semibold">Mutu Ustaz</td>
                                <td class="text-center">18</td>
                            </tr>
                            <tr>
                                <td class="ps-4 fw-semibold">Manajemen Pesantren</td>
                                <td class="text-center">18</td>
                            </tr>
                            <tr>
                                <td class="ps-4 fw-semibold">Indikator Pemenuhan Relatif</td>
                                <td class="text-center">100</td>
                            <td class="text-center pe-4"><x-ui.badge variant="success">30%</x-ui.badge></td>
                            </tr>
                        </tbody>
                    </x-ui.simple-table>
                </x-ui.card>
            </div>
        </div>

        {{-- Komponen List --}}
        <div class="d-flex flex-column gap-5 spm-master-edpm-list">
            @forelse ($komponens->filter(fn($k) => false) as $komponen)
                {{-- placeholder, real rendering below --}}
            @empty
            @endforelse

            @foreach ($komponens as $komponen)
                <div x-show="activeTab === '{{ $komponen->ipr ? 'ipr' : 'edpm' }}'">
                    <x-ui.section-card :title="$komponen->nama" class="spm-edpm-component-card">
                        <x-slot name="toolbar">
                            <x-ui.button variant="light" size="sm" icon="plus"
                                         x-on:click="openButirModal({{ \Illuminate\Support\Js::from($komponen->id) }})">
                                Tambah Butir
                            </x-ui.button>

                            <x-ui.icon-button
                                icon="pencil"
                                label="Edit Komponen"
                                variant="primary"
                                x-on:click="openKomponenModal({{ \Illuminate\Support\Js::from($komponen->id) }}, {{ \Illuminate\Support\Js::from($komponen->nama) }}, {{ \Illuminate\Support\Js::from((bool) $komponen->ipr) }})"
                            />

                            <form method="POST" action="{{ route('admin.master-edpm.komponen.destroy', $komponen->id) }}" class="d-inline"
                                  x-on:submit.prevent="confirmDelete($event, 'Hapus seluruh komponen dan butir di dalamnya?')">
                                @csrf
                                @method('DELETE')
                                <x-ui.icon-button
                                    type="submit"
                                    icon="trash"
                                    label="Hapus Komponen"
                                    variant="danger"
                                />
                            </form>
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
                                    <tr>
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
                                                    x-on:click="openButirModal({{ \Illuminate\Support\Js::from($komponen->id) }}, {{ \Illuminate\Support\Js::from($butir->id) }}, {{ \Illuminate\Support\Js::from($butir->no_sk ?? '') }}, {{ \Illuminate\Support\Js::from($butir->nomor_butir) }}, {{ \Illuminate\Support\Js::from($butir->butir_pernyataan) }})"
                                                />

                                                <form method="POST" action="{{ route('admin.master-edpm.butir.destroy', $butir->id) }}" class="d-inline"
                                                      x-on:submit.prevent="confirmDelete($event, 'Hapus butir ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <x-ui.icon-button
                                                        type="submit"
                                                        icon="trash"
                                                        label="Hapus Butir"
                                                        variant="danger"
                                                    />
                                                </form>
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
                </div>
            @endforeach

            <template x-if="!hasItemsForTab">
                <x-ui.empty-state
                    title="Belum ada master data"
                    description="Belum ada master data untuk tab ini."
                    class="py-12 border border-dashed rounded"
                />
            </template>
        </div>
    </x-ui.index-layout>

    {{-- Modal Komponen --}}
    <x-ui.modal name="edpm-komponen-modal" focusable>
        <form method="POST" x-bind:action="komponenFormAction" x-on:submit.prevent="submitKomponenForm($event)">
            @csrf
            <template x-if="komponenId">
                <input type="hidden" name="_method" value="PUT">
            </template>

            <x-ui.modal-header
                x-bind:title="komponenId ? 'Edit Komponen' : 'Tambah Komponen'"
                subtitle="Atur nama komponen EDPM/IPR."
                icon="data"
            />

            <x-ui.modal-body>
                <div class="mb-5">
                    <label class="form-label required" for="komponen_nama">Nama Komponen</label>
                    <input type="text" name="nama" id="komponen_nama" x-model="komponenForm.nama"
                           class="form-control" placeholder="Contoh: MUTU LULUSAN" required>
                    @error('nama') <div class="text-danger fs-8 mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="mb-0" x-show="activeTab === 'ipr'" style="display: none;">
                    <label class="form-check">
                        <input type="checkbox" name="ipr" value="1" x-model="komponenForm.ipr" class="form-check-input">
                        <span class="form-check-label">Termasuk IPR</span>
                    </label>
                </div>
                <input type="hidden" name="ipr" x-bind:value="activeTab === 'ipr' ? '1' : '0'" x-show="false">
            </x-ui.modal-body>

            <x-ui.modal-footer>
                <x-ui.button type="button" variant="light" x-on:click="$dispatch('close')">Batal</x-ui.button>
                <x-ui.button type="submit" variant="primary">Simpan</x-ui.button>
            </x-ui.modal-footer>
        </form>
    </x-ui.modal>

    {{-- Modal Butir --}}
    <x-ui.modal name="edpm-butir-modal" focusable>
        <form method="POST" x-bind:action="butirFormAction" x-on:submit.prevent="submitButirForm($event)">
            @csrf
            <template x-if="butirId">
                <input type="hidden" name="_method" value="PUT">
            </template>
            <input type="hidden" name="komponen_id" x-bind:value="butirKomponenId">

            <x-ui.modal-header
                x-bind:title="butirId ? 'Edit Butir Pernyataan' : 'Tambah Butir Pernyataan'"
                subtitle="Lengkapi nomor dan pernyataan butir."
                icon="document"
            />

            <x-ui.modal-body>
                <div class="row g-5">
                    <x-ui.form-field label="No SK" for="butir_no_sk" class="col-md-6">
                        <input type="text" name="no_sk" id="butir_no_sk" x-model="butirForm.no_sk"
                               class="form-control" placeholder="Opsional">
                    </x-ui.form-field>
                    <div class="col-md-6">
                        <label class="form-label required" for="butir_nomor_butir">Nomor Butir</label>
                        <input type="text" name="nomor_butir" id="butir_nomor_butir" x-model="butirForm.nomor_butir"
                               class="form-control" placeholder="Contoh: 1.1" required>
                        @error('nomor_butir') <div class="text-danger fs-8 mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mt-5">
                    <label class="form-label required" for="butir_pernyataan">Butir Pernyataan</label>
                    <textarea name="butir_pernyataan" id="butir_pernyataan" x-model="butirForm.butir_pernyataan"
                              class="form-control" rows="4" placeholder="Isi butir pernyataan..." required></textarea>
                    @error('butir_pernyataan') <div class="text-danger fs-8 mt-1">{{ $message }}</div> @enderror
                </div>
            </x-ui.modal-body>

            <x-ui.modal-footer>
                <x-ui.button type="button" variant="light" x-on:click="$dispatch('close')">Batal</x-ui.button>
                <x-ui.button type="submit" variant="primary">Simpan</x-ui.button>
            </x-ui.modal-footer>
        </form>
    </x-ui.modal>
</div>

@if(session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.dispatchEvent(new CustomEvent('notification-received', {
                detail: { type: 'success', title: 'Berhasil!', message: @json(session('success')) }
            }));
        });
    </script>
@endif

@if(session('error'))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.dispatchEvent(new CustomEvent('notification-received', {
                detail: { type: 'error', title: 'Gagal', message: @json(session('error')) }
            }));
        });
    </script>
@endif

<script>
    function masterEdpm() {
        return {
            activeTab: 'edpm',

            // Komponen modal
            komponenId: null,
            komponenForm: { nama: '', ipr: false },
            get komponenFormAction() {
                return this.komponenId
                    ? '{{ url("admin/master-edpm/komponen") }}/' + this.komponenId
                    : '{{ route("admin.master-edpm.komponen.store") }}';
            },

            // Butir modal
            butirId: null,
            butirKomponenId: null,
            butirForm: { no_sk: '', nomor_butir: '', butir_pernyataan: '' },
            get butirFormAction() {
                return this.butirId
                    ? '{{ url("admin/master-edpm/butir") }}/' + this.butirId
                    : '{{ route("admin.master-edpm.butir.store") }}';
            },

            // Computed: check if current tab has items
            get hasItemsForTab() {
                const items = @json($komponens->map(fn($k) => ['id' => $k->id, 'ipr' => (bool)$k->ipr]));
                return items.some(k => this.activeTab === 'ipr' ? k.ipr : !k.ipr);
            },

            openKomponenModal(id = null, nama = '', ipr = false) {
                this.komponenId = id;
                this.komponenForm = { nama, ipr };
                this.$dispatch('open-modal', 'edpm-komponen-modal');
            },

            openButirModal(komponenId, butirId = null, no_sk = '', nomor_butir = '', butir_pernyataan = '') {
                this.butirKomponenId = komponenId;
                this.butirId = butirId;
                this.butirForm = { no_sk, nomor_butir, butir_pernyataan };
                this.$dispatch('open-modal', 'edpm-butir-modal');
            },

            submitKomponenForm(e) {
                e.target.submit();
            },

            submitButirForm(e) {
                e.target.submit();
            },

            confirmDelete(e, message) {
                if (typeof Swal !== 'undefined') {
                    window.SpmSwal.confirm({
                        title: 'Konfirmasi',
                        text: message,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, hapus',
                        cancelButtonText: 'Batal',
                    }).then((result) => {
                        if (result.isConfirmed) e.target.submit();
                    });
                } else {
                    if (confirm(message)) e.target.submit();
                }
            }
        };
    }
</script>
@endsection
