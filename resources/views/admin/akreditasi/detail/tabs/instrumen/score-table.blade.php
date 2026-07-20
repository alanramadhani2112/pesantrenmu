<x-ui.section-card title="Tabel Instrumen Butir Penilaian" subtitle="NA1 dan NA2 tampil sebagai referensi asesor. NK adalah nilai kelompok dari ketua asesor. NV otomatis mengikuti NK saat belum tersimpan, namun tetap dapat diedit admin.">
<x-ui.simple-table class="p-5" table-class="table-hover align-middle">
        <thead>
            <tr class="fw-semibold text-muted bg-light">
                <x-ui.table-th :min-width="false" class="ps-6" style="width: 5%;">No</x-ui.table-th>
                <x-ui.table-th class="min-w-300px">Butir Penilaian</x-ui.table-th>
                <x-ui.table-th :min-width="false" align="center" style="width: 8%;">NA1</x-ui.table-th>
                <x-ui.table-th :min-width="false" align="center" style="width: 8%;">NA2</x-ui.table-th>
                <x-ui.table-th :min-width="false" align="center" style="width: 8%;">NK</x-ui.table-th>
                <x-ui.table-th :min-width="false" align="center" style="width: 18%;">NV</x-ui.table-th>
            </tr>
        </thead>
        <tbody>
                @php $no = 0; @endphp
                @foreach($komponens as $komponen)
                    @if($komponen->butirs->isEmpty()) @continue @endif
                    <tr>
                        <td colspan="6" class="fw-semibold fs-4 py-3 ps-4 bg-body border-top border-bottom border-dashed border-gray-300">
                            {{ $komponen->nama }} @if($komponen->deskripsi) <span class="text-muted fs-6 ms-1">— {{ $komponen->deskripsi }}</span> @endif
                        </td>
                    </tr>
                    @foreach($komponen->butirs as $butir)
                        @php
                            $no++;
                            $na1Value = $asesor1Evaluasis[$butir->id] ?? null;
                            $na2Value = $asesor2Evaluasis[$butir->id] ?? null;
                            $nkValue = $adminNvs[$butir->id]['nk'] ?? null;
                            $storedNv = $adminNvs[$butir->id]['nv'] ?? null;
                            $defaultNvValue = blank($storedNv) ? $nkValue : $storedNv;
                            $nvValue = old("adminNvs.$butir->id", $defaultNvValue);
                            $reasonValue = old("nvReasons.$butir->id", '');
                            $nvMirrorsNk = ! blank($nvValue) && ! blank($nkValue) && (string) $nvValue === (string) $nkValue;
                        @endphp
                        <tr>
                            <td class="ps-6 fw-semibold">{{ $no }}</td>
                            <td>
                                <div class="fw-semibold text-gray-800">{{ $butir->nama ?? $butir->butir_pernyataan }}</div>
                                @if($butir->deskripsi)
                                    <div class="text-muted fs-7 mt-1">{{ $butir->deskripsi }}</div>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex flex-column align-items-center gap-1">
                                    <x-ui.badge variant="light-primary">{{ $na1Value ?? '-' }}</x-ui.badge>
                                    <span class="text-muted fs-8">Asesor 1</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="d-flex flex-column align-items-center gap-1">
                                    <x-ui.badge variant="light-info">{{ $na2Value ?? '-' }}</x-ui.badge>
                                    <span class="text-muted fs-8">Asesor 2</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="d-flex flex-column align-items-center gap-1">
                                    <x-ui.badge variant="light-warning">{{ $nkValue ?? '-' }}</x-ui.badge>
                                    <span class="text-muted fs-8">Ketua</span>
                                </div>
                            </td>
                            <td class="text-center min-w-200px">
                                @if($canEditNv ?? false)
                                    <div class="d-flex flex-column gap-2 text-start">
                                        <select
                                            name="adminNvs[{{ $butir->id }}]"
                                            class="form-select form-select-sm"
                                            aria-label="Nilai Verifikasi butir {{ $no }}"
                                        >
                                            <option value="" @selected(blank($nvValue))>Pilih NV</option>
                                            @foreach([1, 2, 3, 4] as $option)
                                                <option value="{{ $option }}" @selected((string) $nvValue === (string) $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                        <div class="d-flex flex-wrap gap-2 align-items-center">
                                            <x-ui.badge variant="{{ $nvMirrorsNk ? 'light-success' : 'light-warning' }}">
                                                {{ $nvMirrorsNk ? 'Mirror NK' : 'Berbeda dari NK' }}
                                            </x-ui.badge>
                                            <span class="text-muted fs-8">Default NV mengikuti NK: {{ $nkValue ?? '-' }}</span>
                                        </div>
                                    </div>
                                    <textarea
                                        name="nvReasons[{{ $butir->id }}]"
                                        class="form-control form-control-sm mt-2"
                                        rows="2"
                                        maxlength="2000"
                                        placeholder="Alasan jika NV berbeda dari NK"
                                        aria-label="Alasan perubahan NV butir {{ $no }}"
                                    >{{ $reasonValue }}</textarea>
                                @else
                                    <div class="d-flex flex-column align-items-center gap-1">
                                        <x-ui.badge variant="success" class="fs-7">
                                            {{ $nvValue ?? '-' }}
                                        </x-ui.badge>
                                        <span class="text-muted fs-8">{{ $nvMirrorsNk ? 'Mirror NK' : 'Verifikasi admin' }}</span>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @endforeach
        </tbody>
    </x-ui.simple-table>
</x-ui.section-card>
