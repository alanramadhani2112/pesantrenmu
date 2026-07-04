@extends('layouts.app')

@section('header', 'Data SDM Pesantren')

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
        <x-ui.button :href="route('pesantren.profile')" variant="light">
            <x-ui.icon name="exit-right" class="fs-4 me-1" />
            Kembali
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
                    <div class="table-responsive p-4">
                        <table data-ui-simple-table="metronic" class="table table-bordered table-row-dashed align-middle">
                            <thead>
                                <tr class="bg-light">
                                    <th class="fw-semibold text-gray-800 text-center" style="min-width:120px;">Kategori</th>
                                    @foreach($levels as $level)
                                        <th class="fw-semibold text-gray-800 text-center text-uppercase" style="min-width:90px;">
                                            {{ str_replace(['satuan_pesantren_muadalah_(SPM)'], ['SPM'], $level) }}
                                        </th>
                                    @endforeach
                                    <th class="fw-semibold text-gray-800 text-center bg-light-primary" style="min-width:80px;">Total</th>
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
                                                data-ui-input="metronic"
                                                type="number"
                                                name="data[{{ $level }}][{{ $catKey }}_l]"
                                                value="{{ $val }}"
                                                min="0"
                                                class="form-control form-control-sm text-center @error('data.' . $level . '.' . $catKey . '_l') is-invalid @enderror"
                                                @if($isLocked) disabled @endif
                                                style="width: 90px; margin: 0 auto;"
                                            />
                                        </td>
                                    @endforeach
                                    <td class="text-center fw-semibold bg-light-primary text-primary">{{ $rowTotalL }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold text-gray-800">Perempuan</td>
                                    @php $rowTotalP = 0; @endphp
                                    @foreach($levels as $level)
                                        @php $val = (int) ($data[$level][$catKey . '_p'] ?? 0); $rowTotalP += $val; @endphp
                                        <td class="text-center">
                                            <input
                                                data-ui-input="metronic"
                                                type="number"
                                                name="data[{{ $level }}][{{ $catKey }}_p]"
                                                value="{{ $val }}"
                                                min="0"
                                                class="form-control form-control-sm text-center @error('data.' . $level . '.' . $catKey . '_p') is-invalid @enderror"
                                                @if($isLocked) disabled @endif
                                                style="width: 90px; margin: 0 auto;"
                                            />
                                        </td>
                                    @endforeach
                                    <td class="text-center fw-semibold bg-light-primary text-primary">{{ $rowTotalP }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-light-primary">
                                    <td class="fw-semibold text-primary">Total {{ $category['label'] }}</td>
                                    @foreach($levels as $level)
                                        <td class="text-center fw-semibold text-primary">
                                            {{ ((int) ($data[$level][$catKey . '_l'] ?? 0)) + ((int) ($data[$level][$catKey . '_p'] ?? 0)) }}
                                        </td>
                                    @endforeach
                                    <td class="text-center fw-semibold text-primary">{{ $rowTotalL + $rowTotalP }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </x-ui.section-card>
            @endforeach

            @if(!$isLocked)
                <div class="d-flex justify-content-end mt-6">
                    <x-ui.button type="submit" variant="primary" id="btnSaveSdm">
                        <i class="ki-solid ki-check fs-4 me-1"></i>
                        Simpan Data SDM
                    </x-ui.button>
                </div>
            @endif
        </form>
    @endif
</x-ui.page>

@push('scripts')
<script>
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
