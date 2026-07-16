@extends('layouts.app')

@section('content')
@php
    $isLocked = $pesantren->is_locked ?? false;
    $edpmKomponens = $komponens->filter(fn ($komponen) => is_null($komponen->ipr))->values();
    $iprKomponens = $komponens->filter(fn ($komponen) => ! is_null($komponen->ipr))->values();
    $edpmCount = $edpmKomponens->count();
    $iprCount = $iprKomponens->count();
@endphp

<div id="edpmPage" x-data="pesantrenEdpmPage({
    edpmCount: {{ $edpmCount }},
    iprCount: {{ $iprCount }},
    evaluasis: @js($evaluasis),
    links: @js($links),
    catatans: @js($catatans)
})">
    <x-ui.page
        title="Evaluasi Diri Pesantren/Madrasah (EDPM)"
        subtitle="Isi nilai evaluasi, tautan bukti, dan catatan untuk setiap komponen."
        data-module-page="pesantren-edpm"
    >
        <x-slot:toolbar>
            <x-ui.status-badge :variant="$isLocked ? 'warning' : 'success'">
                {{ $isLocked ? 'Terkunci' : 'Aktif' }}
            </x-ui.status-badge>
            <x-ui.button :href="route('pesantren.sdm')" variant="light">
                <x-ui.icon name="exit-right" class="fs-4 me-1" />
                Kembali SDM
            </x-ui.button>
        </x-slot:toolbar>

        <div class="row g-5 mb-5 spm-edpm-stats">
            <div class="col-lg-4">
                <x-ui.stat-card label="Status EDPM" value="{{ $isLocked ? 'Terkunci' : 'Aktif' }}" variant="{{ $isLocked ? 'warning' : 'success' }}" icon="shield-tick" />
            </div>
            <div class="col-lg-4">
                <x-ui.stat-card label="Komponen EDPM" value="{{ $edpmCount }}" variant="info" icon="document" />
            </div>
            <div class="col-lg-4">
                <x-ui.stat-card label="Komponen IPR" value="{{ $iprCount }}" variant="primary" icon="data" />
            </div>
        </div>

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
            <x-ui.alert variant="danger" title="Data EDPM belum valid" class="mb-5">
                <ul class="mb-0 ps-4">
                    @foreach($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        <x-ui.alert variant="info" icon="information-5" title="Cara Pengisian EDPM/IPR" class="mb-5">
            EDPM dan IPR boleh diisi bebas urutan. Gunakan <strong>Simpan Draft</strong> untuk menyimpan sementara. Gunakan <strong>Submit Final</strong> hanya saat semua nilai dan tautan bukti sudah lengkap.
        </x-ui.alert>

        @if($komponens->isNotEmpty())
            {{-- Group Toggle Tabs --}}
            <x-ui.tabs class="mb-5 spm-edpm-tabs">
                <x-ui.tab
                    x-bind:class="{ 'active': activeGroup === 'edpm' }"
                    x-bind:aria-selected="activeGroup === 'edpm' ? 'true' : 'false'"
                    x-on:click.prevent="setGroup('edpm')">
                        Komponen EDPM
                        <x-ui.badge variant="primary" class="ms-2">{{ $edpmCount }}</x-ui.badge>
                </x-ui.tab>
                @if($iprCount > 0)
                    <x-ui.tab
                        x-bind:class="{ 'active': activeGroup === 'ipr' }"
                        x-bind:aria-selected="activeGroup === 'ipr' ? 'true' : 'false'"
                        x-on:click.prevent="setGroup('ipr')">
                            Komponen IPR
                            <x-ui.badge variant="success" class="ms-2">{{ $iprCount }}</x-ui.badge>
                    </x-ui.tab>
                @endif
            </x-ui.tabs>

            {{-- Component Progress Hint --}}
            <div class="spm-edpm-progress d-flex align-items-center justify-content-between px-1 mb-4">
                <span class="text-muted fw-semibold fs-8">
                    Komponen <span x-text="activeStep + 1" class="text-primary"></span> / <span x-text="componentCount()"></span>
                    <span class="text-gray-400 mx-1">·</span>
                    <span x-text="activeGroupLabel()" class="text-gray-500"></span>
                    <span class="text-gray-400 mx-1">·</span>
                    <span x-text="filledButirs() + '/' + totalButirs() + ' butir terisi lengkap (nilai + tautan bukti)'" class="text-gray-500"></span>
                </span>
                <div class="progress" style="height: 4px; width: 120px;">
                    <div class="progress-bar bg-primary" role="progressbar"
                         :style="'width: ' + (((activeStep + 1) / componentCount()) * 100) + '%'">
                    </div>
                </div>
            </div>

            {{-- Stepper + Content --}}
            <div class="spm-edpm-workspace">
                {{-- Stepper Sidebar --}}
                <aside class="spm-edpm-stepper">
                    <div class="d-flex flex-column gap-2">
                        {{-- EDPM Steps --}}
                        <template x-if="activeGroup === 'edpm'">
                            <template x-for="(komponen, index) in edpmKomponens" :key="index">
                                <button type="button" class="btn w-100 text-start spm-edpm-step-btn"
                                    :class="stepButtonClass(index)"
                                    @click="setStep(index)"
                                    :disabled="{{ $isLocked ? 'true' : 'false' }}">
                                    <span class="badge badge-circle me-3 flex-shrink-0" :class="stepBadgeClass(index)" x-text="index + 1"></span>
                                    <span class="d-flex flex-column min-w-0 text-start">
                                        <span class="fw-semibold text-truncate fs-7" x-text="komponen.nama"></span>
                                        <span class="fs-8 opacity-75" x-text="componentFilledButirs(komponen) + '/' + komponen.butirs.length + ' butir terisi'"></span>
                                    </span>
                                </button>
                            </template>
                        </template>
                        {{-- IPR Steps --}}
                        <template x-if="activeGroup === 'ipr'">
                            <template x-for="(komponen, index) in iprKomponens" :key="index">
                                <button type="button" class="btn w-100 text-start spm-edpm-step-btn"
                                    :class="stepButtonClass(index)"
                                    @click="setStep(index)"
                                    :disabled="{{ $isLocked ? 'true' : 'false' }}">
                                    <span class="badge badge-circle me-3 flex-shrink-0" :class="stepBadgeClass(index)" x-text="index + 1"></span>
                                    <span class="d-flex flex-column min-w-0 text-start">
                                        <span class="fw-semibold text-truncate fs-7" x-text="komponen.nama"></span>
                                        <span class="fs-8 opacity-75" x-text="componentFilledButirs(komponen) + '/' + komponen.butirs.length + ' butir terisi'"></span>
                                    </span>
                                </button>
                            </template>
                        </template>
                    </div>
                </aside>

                {{-- Content Area --}}
                <div class="spm-edpm-panel">
                    <form action="{{ route('pesantren.edpm.save') }}" method="POST" id="edpmSaveForm" x-on:submit="appendStateTo($event.target)">
                        @csrf
                        {{-- EDPM Content --}}
                        <template x-if="activeGroup === 'edpm'">
                            <template x-for="(komponen, kIndex) in edpmKomponens" :key="'edpm-' + kIndex">
                                <div x-show="activeStep === kIndex">
                                    <div class="card spm-section-card" data-ui-section-card="metronic">
                                        <div class="card-header border-0 py-4">
                                            <div class="card-title d-flex align-items-center gap-3 m-0">
                                                <span class="spm-section-card-accent"></span>
                                                <div>
                                                    <h3 class="spm-card-title text-gray-900 mb-1">
                                                        <span class="d-inline-flex align-items-center gap-2">
                                                            <span class="badge badge-primary rounded-circle" x-text="kIndex + 1"></span>
                                                            <span x-text="komponen ? komponen.nama : 'Komponen'"></span>
                                                        </span>
                                                    </h3>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-body p-0">
                                        <template x-if="komponen">
                                            <div class="p-5">
                                                <x-ui.simple-table table-class="spm-table-compact table-bordered">
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
                                                                    <td x-text="butir.butir_pernyataan"></td>
                                                                    <td>
                                                                        <select data-ui-select="metronic" class="form-select form-select-sm"
                                                                            :name="'evaluasis[' + butir.id + ']'"
                                                                            x-model="evaluasis[butir.id]"
                                                                            @if($isLocked) disabled @endif>
                                                                            <option value="">Pilih</option>
                                                                            <option value="1">1</option>
                                                                            <option value="2">2</option>
                                                                            <option value="3">3</option>
                                                                            <option value="4">4</option>
                                                                        </select>
                                                                    </td>
                                                                    <td>
                                                                        <input data-ui-input="metronic" type="url" class="form-control form-control-sm"
                                                                            :name="'links[' + butir.id + ']'"
                                                                            x-model="links[butir.id]"
                                                                            placeholder="https://"
                                                                            @if($isLocked) disabled @endif />
                                                                    </td>
                                                                </tr>
                                                            </template>
                                                        </tbody>
                                                </x-ui.simple-table>
                                                <div class="mt-4">
                                                    <label class="form-label fw-semibold text-gray-700">Catatan</label>
                                                    <textarea class="form-control"
                                                        :name="'catatans[' + komponen.id + ']'"
                                                        x-model="catatans[komponen.id]"
                                                        rows="3"
                                                        placeholder="Catatan untuk komponen ini..."
                                                        @if($isLocked) disabled @endif></textarea>
                                                </div>
                                            </div>
                                        </template>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </template>

                        {{-- IPR Content --}}
                        <template x-if="activeGroup === 'ipr'">
                            <template x-for="(komponen, kIndex) in iprKomponens" :key="'ipr-' + kIndex">
                                <div x-show="activeStep === kIndex">
                                    <div class="card spm-section-card" data-ui-section-card="metronic">
                                        <div class="card-header border-0 py-4">
                                            <div class="card-title d-flex align-items-center gap-3 m-0">
                                                <span class="spm-section-card-accent"></span>
                                                <div>
                                                    <h3 class="spm-card-title text-gray-900 mb-1">
                                                        <span class="d-inline-flex align-items-center gap-2">
                                                            <span class="badge badge-success rounded-circle" x-text="kIndex + 1"></span>
                                                            <span x-text="komponen ? komponen.nama : 'Komponen IPR'"></span>
                                                        </span>
                                                    </h3>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-body p-0">
                                        <template x-if="komponen">
                                            <div class="p-5">
                                                <x-ui.simple-table table-class="spm-table-compact table-bordered">
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
                                                                    <td x-text="butir.butir_pernyataan"></td>
                                                                    <td>
                                                                        <select data-ui-select="metronic" class="form-select form-select-sm"
                                                                            :name="'evaluasis[' + butir.id + ']'"
                                                                            x-model="evaluasis[butir.id]"
                                                                            @if($isLocked) disabled @endif>
                                                                            <option value="">Pilih</option>
                                                                            <option value="1">1</option>
                                                                            <option value="2">2</option>
                                                                            <option value="3">3</option>
                                                                            <option value="4">4</option>
                                                                        </select>
                                                                    </td>
                                                                    <td>
                                                                        <input data-ui-input="metronic" type="url" class="form-control form-control-sm"
                                                                            :name="'links[' + butir.id + ']'"
                                                                            x-model="links[butir.id]"
                                                                            placeholder="https://"
                                                                            @if($isLocked) disabled @endif />
                                                                    </td>
                                                                </tr>
                                                            </template>
                                                        </tbody>
                                                </x-ui.simple-table>
                                                <div class="mt-4">
                                                    <label class="form-label fw-semibold text-gray-700">Catatan</label>
                                                    <textarea class="form-control"
                                                        :name="'catatans[' + komponen.id + ']'"
                                                        x-model="catatans[komponen.id]"
                                                        rows="3"
                                                        placeholder="Catatan untuk komponen ini..."
                                                        @if($isLocked) disabled @endif></textarea>
                                                </div>
                                            </div>
                                        </template>
                                        </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </template>

                        {{-- Navigation --}}
                        <div class="spm-edpm-nav d-flex align-items-center justify-content-between mt-6">
                            <div>
                                <x-ui.button type="button" variant="light" x-show="canGoBack()"
                                    @click="prevStep()">
                                    <i class="ki-solid ki-arrow-left fs-4 me-1"></i> <span x-text="prevLabel()"></span>
                                </x-ui.button>
                            </div>
                            <div class="d-flex gap-3">
                                @if(!$isLocked)
                                    <x-ui.button type="button" variant="light" id="btnSaveDraft">
                                        <i class="ki-solid ki-save-2 fs-4 me-1"></i> Simpan Draft
                                    </x-ui.button>
                                    <x-ui.button type="submit" variant="primary" id="btnSaveEdpm">
                                        <i class="ki-solid ki-check fs-4 me-1"></i> Submit Final
                                    </x-ui.button>
                                @endif
                            </div>
                            <div>
                                <x-ui.button type="button" variant="light"
                                    x-show="canGoNext()"
                                    @click="nextStep()">
                                    <span x-text="nextLabel()"></span> <i class="ki-solid ki-arrow-right fs-4 ms-1"></i>
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
        evaluasis: config.evaluasis,
        links: config.links,
        catatans: config.catatans,
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
                return;
            }
            if (this.activeGroup === 'edpm' && this.iprCount > 0) {
                this.setGroup('ipr');
            }
        },

        prevStep() {
            if (this.activeStep > 0) {
                this.activeStep--;
                return;
            }
            if (this.activeGroup === 'ipr') {
                this.activeGroup = 'edpm';
                this.activeStep = Math.max(this.edpmKomponens.length - 1, 0);
            }
        },

        componentCount() {
            return this.activeGroup === 'edpm' ? this.edpmKomponens.length : this.iprKomponens.length;
        },

        activeGroupLabel() {
            return this.activeGroup === 'edpm' ? 'EDPM' : 'IPR';
        },

        canGoBack() {
            return this.activeStep > 0 || this.activeGroup === 'ipr';
        },

        canGoNext() {
            return this.activeStep < this.componentCount() - 1 || (this.activeGroup === 'edpm' && this.iprCount > 0);
        },

        prevLabel() {
            if (this.activeStep > 0) return 'Komponen sebelumnya';
            return 'Kembali ke EDPM';
        },

        nextLabel() {
            if (this.activeStep < this.componentCount() - 1) return 'Komponen selanjutnya';
            return 'Lanjut ke IPR';
        },

        allKomponens() {
            return [...this.edpmKomponens, ...this.iprKomponens];
        },

        totalButirs() {
            return this.allKomponens().reduce((total, komponen) => total + komponen.butirs.length, 0);
        },

        isButirComplete(butir) {
            return !!this.evaluasis[butir.id] && !!this.links[butir.id];
        },

        filledButirs() {
            return this.allKomponens().reduce((total, komponen) => total + komponen.butirs.filter((butir) => this.isButirComplete(butir)).length, 0);
        },

        componentFilledButirs(komponen) {
            return komponen.butirs.filter((butir) => this.isButirComplete(butir)).length;
        },

        firstIncomplete() {
            for (const group of ['edpm', 'ipr']) {
                const komponens = group === 'edpm' ? this.edpmKomponens : this.iprKomponens;
                for (let index = 0; index < komponens.length; index++) {
                    if (komponens[index].butirs.some((butir) => !this.isButirComplete(butir))) {
                        return { group, index };
                    }
                }
            }
            return null;
        },

        jumpToIncomplete(target) {
            this.activeGroup = target.group;
            this.activeStep = target.index;
        },

        stepButtonClass(index) {
            if (index === this.activeStep) return 'btn-primary';
            return 'btn-light btn-light-hover';
        },

        stepBadgeClass(index) {
            if (index === this.activeStep) return 'badge-primary';
            return 'badge-secondary';
        },

        appendStateTo(form) {
            form.querySelectorAll('[data-state-field]').forEach((input) => input.remove());
            [['evaluasis', this.evaluasis], ['links', this.links], ['catatans', this.catatans]].forEach(([group, values]) => {
                Object.entries(values || {}).forEach(([id, value]) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `${group}[${id}]`;
                    input.value = value ?? '';
                    input.dataset.stateField = 'true';
                    form.appendChild(input);
                });
            });
        }
    }));
});

// SweetAlert confirmations
document.getElementById('btnSaveEdpm')?.addEventListener('click', function(e) {
    e.preventDefault();
    const page = window.Alpine?.$data(document.getElementById('edpmPage'));
    const incomplete = page?.firstIncomplete();
    if (incomplete) {
        page.jumpToIncomplete(incomplete);
        window.SpmSwal.alert({
            title: 'Data belum lengkap',
            text: 'Lengkapi nilai evaluasi dan tautan bukti pada komponen yang dibuka sebelum Submit Final.',
            icon: 'warning',
        });
        return;
    }

    window.SpmSwal.confirm({
        title: 'Submit Final EDPM/IPR?',
        text: 'Final akan divalidasi: semua nilai evaluasi dan tautan bukti EDPM/IPR wajib lengkap. Draft boleh belum lengkap.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Submit Final',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('edpmSaveForm').requestSubmit();
        }
    });
});
document.getElementById('btnSaveDraft')?.addEventListener('click', function() {
    window.SpmSwal.confirm({
        title: 'Simpan Draft EDPM/IPR?',
        text: 'Menyimpan isian sementara. Data boleh belum lengkap dan bisa dilanjutkan nanti.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Simpan Draft',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            const saveForm = document.getElementById('edpmSaveForm');
            const draftForm = document.getElementById('edpmDraftForm');
            draftForm.querySelectorAll('[data-state-field]').forEach((input) => input.remove());
            saveForm.dispatchEvent(new Event('submit', { cancelable: true }));
            saveForm.querySelectorAll('[data-state-field]').forEach((input) => draftForm.appendChild(input.cloneNode(true)));
            draftForm.requestSubmit();
        }
    });
});
</script>
@endpush
@endsection
