@extends('layouts.app')

@section('header', 'Evaluasi Diri Pesantren/Madrasah (EDPM)')

@section('content')
@php
    $isLocked = $pesantren->is_locked ?? false;
    $edpmKomponens = $komponens->where('jenis', 'edpm');
    $iprKomponens = $komponens->where('jenis', 'ipr');
    $edpmCount = $edpmKomponens->count();
    $iprCount = $iprKomponens->count();
@endphp

<div x-data="pesantrenEdpmPage({
    edpmCount: {{ $edpmCount }},
    iprCount: {{ $iprCount }}
})">
    <x-ui.page
        title="Evaluasi Diri Pesantren/Madrasah (EDPM)"
        subtitle="Isi nilai evaluasi, tautan bukti, dan catatan untuk setiap komponen."
        data-module-page="pesantren-edpm"
        class="spm-pesantren-form-page"
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

        @if($isLocked)
            <x-ui.alert variant="warning" icon="shield-tick" title="Data Terkunci" class="mb-4">
                Data EDPM terkunci karena sedang dalam proses akreditasi.
            </x-ui.alert>
        @endif

        @if(session('success'))
            <x-ui.alert variant="success" title="Berhasil" class="mb-4">{{ session('success') }}</x-ui.alert>
        @endif
        @if(session('error'))
            <x-ui.alert variant="danger" title="Gagal" class="mb-4">{{ session('error') }}</x-ui.alert>
        @endif

        @if($errors->any())
            <x-ui.alert variant="danger" title="Data EDPM belum valid" class="mb-6">
                <ul class="mb-0 ps-4">
                    @foreach($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        @if($komponens->isNotEmpty())
            {{-- Group Toggle Tabs --}}
            <x-ui.tabs class="mb-6">
                <x-ui.tab :active="true" x-on:click="setGroup('edpm')">
                    Komponen EDPM
                    <span class="badge badge-light-primary ms-2">{{ $edpmCount }}</span>
                </x-ui.tab>
                @if($iprCount > 0)
                    <x-ui.tab x-on:click="setGroup('ipr')">
                        Komponen IPR
                        <span class="badge badge-light-success ms-2">{{ $iprCount }}</span>
                    </x-ui.tab>
                @endif
            </x-ui.tabs>

            {{-- Stepper + Content --}}
            <div class="spm-section-stack row g-6">
                {{-- Stepper Sidebar --}}
                <div class="col-lg-3">
                    <div class="spm-edpm-stepper-card d-flex flex-column gap-2">
                        {{-- EDPM Steps --}}
                        <template x-if="activeGroup === 'edpm'">
                            <template x-for="(komponen, index) in edpmKomponens" :key="index">
                                <button type="button" class="btn w-100 text-start spm-edpm-step-button"
                                    :class="stepButtonClass(index)"
                                    @click="setStep(index)"
                                    :disabled="{{ $isLocked ? 'true' : 'false' }}">
                                    <span class="badge me-3" :class="stepBadgeClass(index)" x-text="index + 1"></span>
                                    <span class="d-flex flex-column min-w-0">
                                        <span class="fw-semibold text-truncate fs-7" x-text="komponen.nama"></span>
                                        <span class="fs-8 opacity-75" x-text="komponen.butirs.length + ' butir'"></span>
                                    </span>
                                </button>
                            </template>
                        </template>
                        {{-- IPR Steps --}}
                        <template x-if="activeGroup === 'ipr'">
                            <template x-for="(komponen, index) in iprKomponens" :key="index">
                                <button type="button" class="btn w-100 text-start spm-edpm-step-button"
                                    :class="stepButtonClass(index)"
                                    @click="setStep(index)"
                                    :disabled="{{ $isLocked ? 'true' : 'false' }}">
                                    <span class="badge me-3" :class="stepBadgeClass(index)" x-text="index + 1"></span>
                                    <span class="d-flex flex-column min-w-0">
                                        <span class="fw-semibold text-truncate fs-7" x-text="komponen.nama"></span>
                                        <span class="fs-8 opacity-75" x-text="komponen.butirs.length + ' butir'"></span>
                                    </span>
                                </button>
                            </template>
                        </template>
                    </div>
                </div>

                {{-- Content Area --}}
                <div class="col-lg-9">
                    <form action="{{ route('pesantren.edpm.save') }}" method="POST" id="edpmSaveForm">
                        @csrf
                        {{-- EDPM Content --}}
                        <template x-if="activeGroup === 'edpm'">
                            <template x-for="(komponen, kIndex) in edpmKomponens" :key="'edpm-' + kIndex">
                                <div x-show="activeStep === kIndex">
                                    <x-ui.section-card :title="'Komponen ' . $edpmKomponens->first()?->nama" class="spm-edpm-input-table">
                                        <template x-if="komponen">
                                            <div class="p-6">
                                                <div class="table-responsive">
                                                    <table data-ui-simple-table="metronic" class="spm-table-compact table table-bordered table-row-dashed align-middle">
                                                        <thead>
                                                            <tr class="bg-light">
                                                                <th style="width:50px">No</th>
                                                                <th>Indikator / Butir</th>
                                                                <th style="width:120px">Nilai Evaluasi</th>
                                                                <th style="width:200px">Tautan Bukti</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <template x-for="(butir, bIndex) in komponen.butirs" :key="bIndex">
                                                                <tr>
                                                                    <td class="text-center" x-text="butir.nomor_butir"></td>
                                                                    <td x-text="butir.indikator"></td>
                                                                    <td>
                                                                        <select data-ui-select="metronic" class="form-select form-select-sm form-select-solid"
                                                                            :name="'evaluasis[' + butir.id + ']'"
                                                                            @if($isLocked) disabled @endif>
                                                                            <option value="">Pilih</option>
                                                                            <option value="1">1</option>
                                                                            <option value="2">2</option>
                                                                            <option value="3">3</option>
                                                                            <option value="4">4</option>
                                                                        </select>
                                                                    </td>
                                                                    <td>
                                                                        <input data-ui-input="metronic" type="url" class="form-control form-control-sm form-control-solid"
                                                                            :name="'links[' + butir.id + ']'"
                                                                            placeholder="https://"
                                                                            @if($isLocked) disabled @endif />
                                                                    </td>
                                                                </tr>
                                                            </template>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <div class="mt-4">
                                                    <label class="form-label fw-semibold text-gray-700">Catatan</label>
                                                    <textarea class="form-control form-control-solid"
                                                        :name="'catatans[' + komponen.id + ']'"
                                                        rows="3"
                                                        placeholder="Catatan untuk komponen ini..."
                                                        @if($isLocked) disabled @endif></textarea>
                                                </div>
                                            </div>
                                        </template>
                                    </x-ui.section-card>
                                </div>
                            </template>
                        </template>

                        {{-- IPR Content --}}
                        <template x-if="activeGroup === 'ipr'">
                            <template x-for="(komponen, kIndex) in iprKomponens" :key="'ipr-' + kIndex">
                                <div x-show="activeStep === kIndex">
                                    <x-ui.section-card title="Komponen IPR" class="spm-edpm-input-table">
                                        <template x-if="komponen">
                                            <div class="p-6">
                                                <div class="table-responsive">
                                                    <table data-ui-simple-table="metronic" class="spm-table-compact table table-bordered table-row-dashed align-middle">
                                                        <thead>
                                                            <tr class="bg-light">
                                                                <th style="width:50px">No</th>
                                                                <th>Indikator / Butir</th>
                                                                <th style="width:120px">Nilai Evaluasi</th>
                                                                <th style="width:200px">Tautan Bukti</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <template x-for="(butir, bIndex) in komponen.butirs" :key="bIndex">
                                                                <tr>
                                                                    <td class="text-center" x-text="butir.nomor_butir"></td>
                                                                    <td x-text="butir.indikator"></td>
                                                                    <td>
                                                                        <select data-ui-select="metronic" class="form-select form-select-sm form-select-solid"
                                                                            :name="'evaluasis[' + butir.id + ']'"
                                                                            @if($isLocked) disabled @endif>
                                                                            <option value="">Pilih</option>
                                                                            <option value="1">1</option>
                                                                            <option value="2">2</option>
                                                                            <option value="3">3</option>
                                                                            <option value="4">4</option>
                                                                        </select>
                                                                    </td>
                                                                    <td>
                                                                        <input data-ui-input="metronic" type="url" class="form-control form-control-sm form-control-solid"
                                                                            :name="'links[' + butir.id + ']'"
                                                                            placeholder="https://"
                                                                            @if($isLocked) disabled @endif />
                                                                    </td>
                                                                </tr>
                                                            </template>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <div class="mt-4">
                                                    <label class="form-label fw-semibold text-gray-700">Catatan</label>
                                                    <textarea class="form-control form-control-solid"
                                                        :name="'catatans[' + komponen.id + ']'"
                                                        rows="3"
                                                        placeholder="Catatan untuk komponen ini..."
                                                        @if($isLocked) disabled @endif></textarea>
                                                </div>
                                            </div>
                                        </template>
                                    </x-ui.section-card>
                                </div>
                            </template>
                        </template>

                        {{-- Navigation --}}
                        <div class="spm-pesantren-form-actions d-flex justify-content-between mt-6">
                            <div>
                                <x-ui.button type="button" variant="light" x-show="activeStep > 0"
                                    @click="prevStep()">
                                    <i class="ki-solid ki-arrow-left fs-4 me-1"></i> Sebelumnya
                                </x-ui.button>
                            </div>
                            <div class="d-flex gap-3">
                                @if(!$isLocked)
                                    <x-ui.button type="button" variant="light" id="btnSaveDraft">
                                        <i class="ki-solid ki-save-2 fs-4 me-1"></i> Simpan Draft
                                    </x-ui.button>
                                    <x-ui.button type="submit" variant="primary" id="btnSaveEdpm">
                                        <i class="ki-solid ki-check fs-4 me-1"></i> Simpan
                                    </x-ui.button>
                                @endif
                            </div>
                            <div>
                                <x-ui.button type="button" variant="light"
                                    x-show="activeStep < componentCount() - 1"
                                    @click="nextStep()">
                                    Selanjutnya <i class="ki-solid ki-arrow-right fs-4 ms-1"></i>
                                </x-ui.button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @else
            <x-ui.alert variant="info" title="Tidak Ada Data" class="mb-0">
                Belum ada komponen EDPM yang tersedia.
            </x-ui.alert>
        @endif
    </x-ui.page>
</div>

{{-- Draft Form (separate, submits all data without validation) --}}
<form action="{{ route('pesantren.edpm.save-draft') }}" method="POST" id="edpmDraftForm" style="display:none;">
    @csrf
</form>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('pesantrenEdpmPage', (config) => ({
        activeStep: 0,
        activeGroup: 'edpm',
        edpmCount: config.edpmCount,
        iprCount: config.iprCount,
        edpmKomponens: @json($edpmKomponens->values()),
        iprKomponens: @json($iprKomponens->values()),

        setGroup(group) {
            this.activeGroup = group;
            this.activeStep = 0;
        },

        setStep(index) {
            this.activeStep = index;
        },

        nextStep() {
            if (this.activeStep < this.componentCount() - 1) {
                this.activeStep++;
            }
        },

        prevStep() {
            if (this.activeStep > 0) {
                this.activeStep--;
            }
        },

        componentCount() {
            return this.activeGroup === 'edpm' ? this.edpmKomponens.length : this.iprKomponens.length;
        },

        stepButtonClass(index) {
            if (index === this.activeStep) return 'btn-primary';
            return 'btn-light';
        },

        stepBadgeClass(index) {
            if (index === this.activeStep) return 'badge-light-primary';
            return 'badge-light-secondary';
        }
    }));
});

// SweetAlert confirmations
document.getElementById('btnSaveEdpm')?.addEventListener('click', function(e) {
    e.preventDefault();
    window.SpmSwal.confirm({
        title: 'Simpan Data EDPM?',
        text: 'Pastikan semua nilai evaluasi dan tautan bukti sudah diisi dengan benar.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Simpan',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('edpmSaveForm').requestSubmit();
        }
    });
});

document.getElementById('btnSaveDraft')?.addEventListener('click', function() {
    window.SpmSwal.confirm({
        title: 'Simpan Draft EDPM?',
        text: 'Draft dapat dilanjutkan kapan saja.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Simpan Draft',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            // Clone all inputs from save form to draft form
            const saveForm = document.getElementById('edpmSaveForm');
            const draftForm = document.getElementById('edpmDraftForm');
            draftForm.querySelectorAll('[data-draft-clone]').forEach(input => input.remove());
            saveForm.querySelectorAll('select, input, textarea').forEach(input => {
                const clone = input.cloneNode(true);
                clone.dataset.draftClone = 'true';
                draftForm.appendChild(clone);
            });
            draftForm.requestSubmit();
        }
    });
});
</script>
@endpush
@endsection
