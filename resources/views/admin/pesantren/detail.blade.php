@extends('layouts.app')

@section('content')
@php
    $profileName = $pesantren?->nama_pesantren ?? $user->name;
    $latestAkreditasi = $user->akreditasis()->latest()->first();
    $akreditasiLabel = '-';
    $akreditasiVariant = 'secondary';

    if ($latestAkreditasi && (int) $latestAkreditasi->status === 0) {
        $akreditasiLabel = $latestAkreditasi->peringkat ?? 'Selesai';
        $akreditasiVariant = 'primary';
    } elseif ($latestAkreditasi) {
        $akreditasiLabel = 'Proses';
        $akreditasiVariant = 'warning';
    }

    $documents = [
        'status_kepemilikan_tanah' => 'Status Kepemilikan Tanah',
        'sertifikat_nsp' => 'Sertifikat NSP',
        'rk_anggaran' => 'RK Anggaran',
        'silabus_rpp' => 'Silabus dan RPP',
        'peraturan_kepegawaian' => 'Peraturan Kepegawaian',
        'file_lk_iapm' => 'File LK IAPM',
        'laporan_tahunan' => 'Laporan Tahunan',
        'dok_profil' => 'Dokumen Profil',
        'dok_nsp' => 'Dokumen NSP',
        'dok_renstra' => 'Dokumen Renstra',
        'dok_rk_anggaran' => 'Dokumen RK Anggaran',
        'dok_kurikulum' => 'Dokumen Kurikulum',
        'dok_silabus_rpp' => 'Dokumen Silabus dan RPP',
        'dok_kepengasuhan' => 'Dokumen Kepengasuhan',
        'dok_peraturan_kepegawaian' => 'Dokumen Peraturan Kepegawaian',
        'dok_sarpras' => 'Dokumen Sarpras',
    ];
@endphp

<x-slot name="header">{{ __('Detail Pesantren') }}</x-slot>

<x-ui.page title="Detail Pesantren" subtitle="Ringkasan profil, layanan pendidikan, dan kelengkapan dokumen pesantren." class="spm-detail-page" x-data="adminManagement()">
    <x-slot:toolbar>
        <x-ui.button :href="route('admin.pesantren.index')" variant="light">
            <x-ui.icon name="exit-right" class="fs-4 me-1" />
            Kembali
        </x-ui.button>
    </x-slot:toolbar>

    <div class="row g-6">
        <div class="col-xl-4">
            <div class="d-flex flex-column gap-6">
                <x-ui.card>
                    <div class="d-flex flex-column align-items-center text-center">
                        <div class="spm-profile-avatar d-flex align-items-center justify-content-center mb-5">
                            {{ substr($profileName, 0, 1) }}
                        </div>

                        <h2 class="spm-card-title fs-4 mb-2">{{ $profileName }}</h2>
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-4">NSP: {{ $pesantren?->ns_pesantren ?? '-' }}</div>

                        <div class="d-flex flex-wrap justify-content-center gap-2">
                            <x-ui.status-badge :variant="$user->status == 1 ? 'success' : 'danger'">
                                {{ $user->status == 1 ? 'Aktif' : 'Tidak Aktif' }}
                            </x-ui.status-badge>

                            <x-ui.status-badge :variant="$akreditasiVariant">
                                Akreditasi: {{ $akreditasiLabel }}
                            </x-ui.status-badge>
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.section-card title="Informasi Kontak">
                    <div class="p-6">
                        <div class="row g-5">
                            <x-ui.detail-item label="Email Pesantren" value="{{ $pesantren?->email_pesantren ?? '-' }}" span="2" />
                            <x-ui.detail-item label="No. Telp / WA" value="{{ ($pesantren?->telp_pesantren ?? '-') . ' / ' . ($pesantren?->hp_wa ?? '-') }}" span="2" />
                            <x-ui.detail-item label="Lokasi" value="{{ ($pesantren?->kota_kabupaten ?? '-') . ', ' . ($pesantren?->provinsi ?? '-') }}" span="2" />
                        </div>
                    </div>

                    @if($pesantren)
                        <div class="px-6 pb-6">
                            <div class="border-top border-gray-200 pt-5">
                                <form method="POST" action="{{ route('admin.pesantren.toggle-lock') }}">
                                    @csrf
                                    <input type="hidden" name="pesantren_id" value="{{ $pesantren->id }}">
                                    <x-ui.button
                                        type="submit"
                                        :variant="$pesantren->is_locked ? 'warning' : 'primary'"
                                        class="w-100 justify-content-center"
                                        x-on:click.prevent="confirmToggleLock({{ $pesantren->is_locked ? 'true' : 'false' }}, $event)"
                                    >
                                        <x-ui.icon name="shield-tick" class="fs-4 me-2" />
                                        {{ $pesantren->is_locked ? 'Buka Kunci Data' : 'Kunci Data Pesantren' }}
                                    </x-ui.button>
                                </form>

                                <div class="text-muted fw-semibold fs-8 text-center mt-3">
                                    {{ $pesantren->is_locked ? 'Data pesantren saat ini terkunci.' : 'Data pesantren dapat diedit oleh user.' }}
                                </div>
                            </div>
                        </div>
                    @endif
                </x-ui.section-card>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="d-flex flex-column gap-6">
                @if($pesantren)
                    <x-ui.section-card title="Profil Pesantren" subtitle="Identitas utama dan narasi kelembagaan.">
                        <div class="p-6">
                            <div class="row g-5">
                                <x-ui.detail-item label="Tahun Pendirian" value="{{ $pesantren->tahun_pendirian ?: '-' }}" />
                                <x-ui.detail-item label="Nama Mudir" value="{{ $pesantren->nama_mudir ?: '-' }}" />
                                <x-ui.detail-item label="Pendidikan Mudir" value="{{ $pesantren->jenjang_pendidikan_mudir ?: '-' }}" />
                                <x-ui.detail-item label="Persyarikatan" value="{{ $pesantren->persyarikatan ?: '-' }}" />

                                <x-ui.detail-item label="Alamat Lengkap" span="2">
                                    <div class="spm-detail-block spm-detail-value-muted">{{ $pesantren->alamat ?: '-' }}</div>
                                </x-ui.detail-item>

                                <x-ui.detail-item label="Visi" span="2">
                                    <div class="spm-detail-block spm-detail-value-muted whitespace-pre-line">{{ $pesantren->visi ?: '-' }}</div>
                                </x-ui.detail-item>

                                <x-ui.detail-item label="Misi" span="2">
                                    <div class="spm-detail-block spm-detail-value-muted whitespace-pre-line">{{ $pesantren->misi ?: '-' }}</div>
                                </x-ui.detail-item>
                            </div>
                        </div>
                    </x-ui.section-card>

                    <x-ui.section-card title="Data & Fasilitas" subtitle="Layanan pendidikan dan kapasitas sarana.">
                        <div class="p-6">
                            <div class="row g-6">
                                <div class="col-lg-7">
                                    <div class="spm-detail-label mb-3">Layanan Pendidikan</div>

                                    @if($pesantren->units && $pesantren->units->count() > 0)
                                        <x-ui.simple-table dense>
                                            <thead>
                                                <tr>
                                                    <th class="ps-4">Unit</th>
                                                    <th class="text-end pe-4">Jumlah Rombel</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($pesantren->units as $unit)
                                                    <tr>
                                                        <td class="ps-4 text-uppercase fw-semibold">{{ str_replace('_', ' ', $unit->unit) }}</td>
                                                        <td class="text-end pe-4">
                                                            <x-ui.badge variant="success">{{ $unit->jumlah_rombel ?? 0 }} Rombel</x-ui.badge>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </x-ui.simple-table>
                                    @else
                                        <x-ui.empty-state title="Belum Ada Unit" description="Data unit satuan pendidikan belum diisi." />
                                    @endif
                                </div>

                                <div class="col-lg-5">
                                    <div class="d-flex flex-column gap-4">
                                        <x-ui.stat-card label="Luas Tanah" value="{{ $pesantren->luas_tanah ?: '0' }} m2" variant="success" icon="geolocation" />

                                        <x-ui.stat-card label="Luas Bangunan" value="{{ $pesantren->luas_bangunan ?: '0' }} m2" variant="info" icon="category" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </x-ui.section-card>

                    <x-ui.section-card title="Dokumen Pesantren" subtitle="Status unggahan dokumen pendukung.">
                        <div class="p-6">
                            <div class="row g-5">
                                @foreach(array_chunk($documents, 9, true) as $documentGroup)
                                    <div class="col-lg-6">
                                        <div class="spm-document-list">
                                            @foreach($documentGroup as $field => $label)
                                                <x-ui.document-item
                                                    :label="$label"
                                                    :href="$pesantren->$field ? Storage::url($pesantren->$field) : null"
                                                />
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </x-ui.section-card>
                @else
                    <x-ui.card>
                        <x-ui.empty-state title="Profil Kosong" description="Pesantren ini belum melengkapi profil mereka." />
                    </x-ui.card>
                @endif
            </div>
        </div>
    </div>
</x-ui.page>
@endsection
