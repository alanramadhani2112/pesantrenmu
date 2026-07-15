@extends('layouts.app')

@section('content')
@php
    $isLocked = $pesantren->is_locked ?? false;
    $unitCount = count($levels ?? []);
    $grandTotal = 0;
    foreach ($data as $level => $fields) {
        foreach ($fields as $value) {
            $grandTotal += (int) $value;
        }
    }
@endphp

<x-ui.page
    title="Data SDM Pesantren"
    subtitle="Kelola rekap santri, ustadz, pamong, musyrif, dan tenaga kependidikan."
    data-module-page="pesantren-sdm"
>
    <x-slot:toolbar>
        <x-ui.status-badge :variant="$isLocked ? 'warning' : 'success'">
            {{ $isLocked ? 'Terkunci' : 'Aktif' }}
        </x-ui.status-badge>
        <x-ui.button :href="route('pesantren.ipm')" variant="light">
            <x-ui.icon name="exit-right" class="fs-4 me-1" />
            Kembali IPM
        </x-ui.button>
        <x-ui.button :href="route('pesantren.edpm')" variant="light">
            <x-ui.icon name="arrow-right" class="fs-4 me-1" />
            Lanjut EDPM/IPR
        </x-ui.button>
    </x-slot:toolbar>

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
        <x-ui.alert variant="warning" icon="shield-tick" title="Data Terkunci" class="mb-4">
            Data SDM terkunci karena sedang dalam proses akreditasi.
        </x-ui.alert>
    @endif

    @if(session('success'))
        <x-ui.alert variant="success" title="Berhasil" class="mb-4">{{ session('success') }}</x-ui.alert>
    @endif
    @if(session('error'))
        <x-ui.alert variant="danger" title="Gagal" class="mb-4">{{ session('error') }}</x-ui.alert>
    @endif
    @if(session('warning'))
        <x-ui.alert variant="warning" title="Peringatan" class="mb-4">{{ session('warning') }}</x-ui.alert>
    @endif

    @if(empty($levels))
        <x-ui.alert variant="warning" title="Unit Tidak Tersedia" class="mb-4">
            Pilih layanan satuan pendidikan di <a href="{{ route('pesantren.profile') }}" class="fw-semibold text-decoration-underline">Profil Pesantren</a> sebelum mengisi Data SDM.
        </x-ui.alert>
    @else
        <form action="{{ route('pesantren.sdm.save') }}" method="POST" id="sdmForm">
            @csrf

            @if($errors->any())
                <x-ui.alert variant="danger" title="Data SDM belum valid" class="mb-6">
                    <ul class="mb-0 ps-4">
                        @foreach($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </x-ui.alert>
            @endif

            <div class="spm-section-stack">
            @foreach($categories as $category)
                @php
                    $catKey = $category['key'];
                    $catTotal = 0;
                    foreach ($levels as $level) {
                        $catTotal += (int) ($data[$level][$catKey . '_l'] ?? 0);
                        $catTotal += (int) ($data[$level][$catKey . '_p'] ?? 0);
                    }
                @endphp
                <x-ui.section-card :title="$category['label']" subtitle="Input rekap per unit layanan" class="mb-6">
                    <x-ui.simple-table class="spm-table-compact p-4" table-class="table-bordered">
                        <thead>
                            <tr class="bg-light">
                                <x-ui.table-th align="center" style="min-width:120px;">Kategori</x-ui.table-th>
                                @foreach($levels as $level)
                                    <x-ui.table-th align="center" class="text-uppercase" style="min-width:90px;">
                                        {{ str_replace(['satuan_pesantren_muadalah_(SPM)'], ['SPM'], $level) }}
                                    </x-ui.table-th>
                                @endforeach
                                <x-ui.table-th align="center" class="bg-light-primary" style="min-width:80px;">Total</x-ui.table-th>
                            </tr>
                        </thead>
                            <tbody>
                                <tr>
                                    <td class="fw-semibold text-gray-800">Laki-laki</td>
                                    @php $rowTotalL = 0; @endphp
                                    @foreach($levels as $level)
                                        @php $val = (int) ($data[$level][$catKey . '_l'] ?? 0); $rowTotalL += $val; @endphp
                                        <td class="text-center">
                                            <input
                                                data-ui-input="metronic" data-sdm-input
                                                type="number"
                                                name="data[{{ $level }}][{{ $catKey }}_l]"
                                                value="{{ $val }}"
                                                min="0"
                                                class="form-control form-control-sm text-center spm-sdm-input @error('data.' . $level . '.' . $catKey . '_l') is-invalid @enderror"
                                                @if($isLocked) disabled @endif
                                                style="width: 90px; margin: 0 auto;"
                                            />
                                        </td>
                                    @endforeach
                                    <td class="text-center fw-semibold bg-light-primary text-primary js-row-total">{{ $rowTotalL }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold text-gray-800">Perempuan</td>
                                    @php $rowTotalP = 0; @endphp
                                    @foreach($levels as $level)
                                        @php $val = (int) ($data[$level][$catKey . '_p'] ?? 0); $rowTotalP += $val; @endphp
                                        <td class="text-center">
                                            <input
                                                data-ui-input="metronic" data-sdm-input
                                                type="number"
                                                name="data[{{ $level }}][{{ $catKey }}_p]"
                                                value="{{ $val }}"
                                                min="0"
                                                class="form-control form-control-sm text-center spm-sdm-input @error('data.' . $level . '.' . $catKey . '_p') is-invalid @enderror"
                                                @if($isLocked) disabled @endif
                                                style="width: 90px; margin: 0 auto;"
                                            />
                                        </td>
                                    @endforeach
                                    <td class="text-center fw-semibold bg-light-primary text-primary js-row-total">{{ $rowTotalP }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-light-primary">
                                    <td class="fw-semibold text-primary">Total {{ $category['label'] }}</td>
                                    @foreach($levels as $level)
                                        <td class="text-center fw-semibold text-primary js-col-total" data-level="{{ $level }}">{{ ((int) ($data[$level][$catKey . '_l'] ?? 0)) + ((int) ($data[$level][$catKey . '_p'] ?? 0)) }}</td>
                                    @endforeach
                                    <td class="text-center fw-semibold text-primary js-category-total">{{ $rowTotalL + $rowTotalP }}</td>
                                </tr>
                            </tfoot>
                    </x-ui.simple-table>
                </x-ui.section-card>
            @endforeach
            </div>

            {{-- Grand Total Summary --}}
            <div class="card border border-dashed border-gray-300 bg-body spm-sdm-grand-total mb-0">
                <div class="card-body py-4 px-6">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <x-ui.icon name="people" class="fs-2x text-primary" />
                            <div>
                                <span class="fw-semibold text-gray-900 fs-6">Total Seluruh SDM</span>
                                <span class="text-muted fw-semibold fs-8 d-block">Semua kategori dan unit layanan</span>
                            </div>
                        </div>
                        <x-ui.badge variant="primary" class="fs-4 px-4 py-2"><span id="sdmGrandTotal">{{ $grandTotal }}</span> Data</x-ui.badge>
                    </div>
                </div>
            </div>

            @if(!$isLocked)
                <div class="d-flex justify-content-end gap-3 mt-6">
                    <x-ui.button type="submit" variant="light" id="btnDraftSdm">
                        <x-ui.icon name="document" class="fs-4 me-1" />
                        Simpan Draft
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary" id="btnSaveSdm">
                        <x-ui.icon name="check-circle" class="fs-4 me-1" />
                        Simpan Data SDM
                    </x-ui.button>
                </div>
            @endif
        </form>
    @endif
</x-ui.page>

@push('scripts')
<script>
function updateSdmTotals() {
    let grandTotal = 0;
    document.querySelectorAll('.spm-table-compact table, table.spm-table-compact').forEach((table) => {
        const bodyRows = table.querySelectorAll('tbody tr');
        const levelCount = bodyRows[0]?.querySelectorAll('input').length ?? 0;
        const colTotals = Array(levelCount).fill(0);
        let categoryTotal = 0;

        bodyRows.forEach((row) => {
            let rowTotal = 0;
            row.querySelectorAll('input[data-sdm-input]').forEach((input, index) => {
                const value = Number(input.value) || 0;
                rowTotal += value;
                colTotals[index] += value;
            });
            categoryTotal += rowTotal;
            row.querySelector('.js-row-total').textContent = rowTotal;
        });

        table.querySelectorAll('.js-col-total').forEach((cell, index) => cell.textContent = colTotals[index] ?? 0);
        table.querySelector('.js-category-total').textContent = categoryTotal;
        grandTotal += categoryTotal;
    });
    document.getElementById('sdmGrandTotal').textContent = grandTotal;
}

document.querySelectorAll('input[data-sdm-input]').forEach(input => input.addEventListener('input', updateSdmTotals));
updateSdmTotals();

document.getElementById('btnSaveSdm')?.addEventListener('click', function(e) {
    e.preventDefault();
    window.SpmSwal.confirm({
        title: 'Simpan Data SDM?',
        text: 'Pastikan semua data sudah benar.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Simpan',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('sdmForm').requestSubmit();
        }
    });
});
</script>
@endpush
@endsection
