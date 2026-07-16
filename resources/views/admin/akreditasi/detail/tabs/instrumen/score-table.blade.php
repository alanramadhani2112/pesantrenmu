<x-ui.section-card title="Tabel Penilaian Admin" subtitle="Nilai Akreditasi (NA), Nilai Koreksi (NK), dan Nilai Validasi (NV) per butir.">
<x-ui.simple-table class="p-5" table-class="table-hover">
        <thead>
            <tr class="fw-semibold text-muted bg-light">
                <x-ui.table-th :min-width="false" class="ps-6" style="width: 5%;">No</x-ui.table-th>
                <x-ui.table-th class="min-w-250px">Butir Penilaian</x-ui.table-th>
                <x-ui.table-th :min-width="false" align="center" style="width: 8%;">NA</x-ui.table-th>
                <x-ui.table-th :min-width="false" align="center" style="width: 8%;">NK</x-ui.table-th>
                <x-ui.table-th :min-width="false" align="center" style="width: 12%;">NV</x-ui.table-th>
            </tr>
        </thead>
        <tbody>
                @php $no = 0; @endphp
                @foreach($komponens as $komponen)
                    @if($komponen->butirs->isEmpty()) @continue @endif
                    <tr>
                        <td colspan="5" class="fw-semibold fs-4 py-3 ps-4 bg-light-primary">
                            {{ $komponen->nama }} @if($komponen->deskripsi) <span class="text-muted fs-6 ms-1">— {{ $komponen->deskripsi }}</span> @endif
                        </td>
                    </tr>
                    @foreach($komponen->butirs as $butir)
                        @php $no++; @endphp
                        <tr>
                            <td class="ps-6 fw-semibold">{{ $no }}</td>
                            <td>
                                <div class="fw-semibold text-gray-800">{{ $butir->nama ?? $butir->butir_pernyataan }}</div>
                                @if($butir->deskripsi)
                                    <div class="text-muted fs-7 mt-1">{{ $butir->deskripsi }}</div>
                                @endif
                            </td>
                            <td class="text-center">
                                <x-ui.badge variant="light-primary">{{ $asesor1Evaluasis[$butir->id] ?? '-' }}</x-ui.badge>
                            </td>
                            <td class="text-center">
                                <x-ui.badge variant="light-warning">{{ $butir->nk ?? ($adminNvs[$butir->id]['nk'] ?? '-') }}</x-ui.badge>
                            </td>
                            <td class="text-center min-w-200px">
                                @php
                                    $nkValue = $adminNvs[$butir->id]['nk'] ?? null;
                                    $storedNv = $adminNvs[$butir->id]['nv'] ?? null;
                                    $nvValue = old("adminNvs.$butir->id", $storedNv);
                                    $reasonValue = old("nvReasons.$butir->id", '');
                                @endphp

                                @if($canEditNv ?? false)
                                    <select
                                        name="adminNvs[{{ $butir->id }}]"
                                        class="form-select form-select-sm"
                                        aria-label="Nilai Validasi butir {{ $no }}"
                                    >
                                        <option value="" @selected(blank($nvValue))>Pilih NV</option>
                                        @foreach([1, 2, 3, 4] as $option)
                                            <option value="{{ $option }}" @selected((string) $nvValue === (string) $option)>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                    <div class="form-text text-muted text-start mt-1">NK saat ini: {{ $nkValue ?? '-' }}</div>
                                    <textarea
                                        name="nvReasons[{{ $butir->id }}]"
                                        class="form-control form-control-sm mt-2"
                                        rows="2"
                                        maxlength="2000"
                                        placeholder="Alasan jika NV berbeda dari NK"
                                        aria-label="Alasan perubahan NV butir {{ $no }}"
                                    >{{ $reasonValue }}</textarea>
                                @else
                                    <x-ui.badge variant="success" class="fs-7">
                                        {{ $adminNvs[$butir->id]['nv'] ?? '-' }}
                                    </x-ui.badge>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @endforeach
        </tbody>
    </x-ui.simple-table>
</x-ui.section-card>
