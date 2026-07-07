<x-ui.section-card title="Tabel Penilaian Admin" subtitle="Nilai Akreditasi (NA), Nilai Koreksi (NK), dan Nilai Validasi (NV) per butir.">
    <div class="table-responsive p-6">
        <table class="table table-row-dashed table-hover align-middle g-0">
            <thead>
                <tr class="fw-semibold text-muted bg-light">
                    <th class="ps-6" width="5%">No</th>
                    <th class="min-w-250px">Butir Penilaian</th>
                    <th class="text-center" width="8%">NA</th>
                    <th class="text-center" width="8%">NK</th>
                    <th class="text-center" width="12%">NV</th>
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
                                    <span class="badge badge-light-success fs-7">
                                        {{ $adminNvs[$butir->id]['nv'] ?? '-' }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
</x-ui.section-card>
