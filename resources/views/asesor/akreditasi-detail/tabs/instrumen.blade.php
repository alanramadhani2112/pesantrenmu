{{-- Tab: Instrumen Penilaian --}}
@php
    use App\StateMachine\AkreditasiStateMachine;
@endphp

{{-- Scoring Progress --}}
@if(!empty($scoringProgress))
<div class="row g-4 mb-6">
    @foreach($scoringProgress as $card)
        <div class="col-md-3">
            <x-ui.stat-card
                :label="$card['label'] ?? ''"
                :value="($card['current'] ?? 0) . '/' . ($card['total'] ?? 0)"
                :variant="($card['current'] ?? 0) >= ($card['total'] ?? 1) ? 'success' : 'warning'"
                icon="chart-simple"
            />
        </div>
    @endforeach
</div>
@endif

{{-- Kelompok Unlocked Warning --}}
@if(!empty($asesorFinalStatus['kelompok_unlocked']) && $asesorTipe === 1)
    <x-ui.alert variant="info" title="Nilai Kelompok Terbuka" class="mb-4">
        Nilai Ketua dan Anggota sudah lengkap. Anda dapat mengisi Nilai Kelompok (NK).
    </x-ui.alert>
@endif

{{-- Status Alert --}}
@if((int) $akreditasi->status === AkreditasiStateMachine::STATUS_VALIDASI_ADMIN)
    <x-ui.alert variant="info" title="Tahap Validasi Admin" class="mb-4">
        Penilaian sedang dalam tahap validasi oleh admin. Anda tidak dapat mengubah nilai.
    </x-ui.alert>
@endif

{{-- Score Table --}}
<x-ui.section-card title="Tabel Penilaian Instrumen" subtitle="Isi nilai NA dan NK per butir pernyataan">
    <x-ui.simple-table class="p-6" table-class="table-row-gray-200 gy-2 fs-7">
        <thead>
            <tr class="fw-semibold text-muted bg-light">
                <x-ui.table-th class="min-w-80px">Komponen</x-ui.table-th>
                <x-ui.table-th class="min-w-60px">No SK</x-ui.table-th>
                <x-ui.table-th class="min-w-60px">No Butir</x-ui.table-th>
                <x-ui.table-th class="min-w-200px">Butir Pernyataan</x-ui.table-th>
                <x-ui.table-th align="center" class="min-w-60px">EDPM</x-ui.table-th>
                @if($asesorTipe === 1)
                    <x-ui.table-th align="center" class="min-w-120px">Nilai Ketua (NA)</x-ui.table-th>
                    <x-ui.table-th align="center" class="min-w-100px">Nilai Anggota</x-ui.table-th>
                    <x-ui.table-th align="center" class="min-w-60px">Delta</x-ui.table-th>
                    <x-ui.table-th align="center" class="min-w-120px">Nilai Kelompok (NK)</x-ui.table-th>
                    <x-ui.table-th class="min-w-150px">Catatan Butir</x-ui.table-th>
                @else
                    <x-ui.table-th align="center" class="min-w-120px">Nilai Anggota (NA)</x-ui.table-th>
                @endif
            </tr>
        </thead>
        <tbody>
                    @foreach($komponens as $komponen)
                        @foreach($komponen->butirs ?? [] as $butir)
                            @php
                                $butirId = $butir->id;
                                $pesantrenEval = $pesantrenEvaluasis[$butirId] ?? null;
                                $asesorEval = $asesorEvaluasis[$butirId] ?? null;
                                $otherEval = $otherAsesorEvaluasis[$butirId] ?? null;
                                $nkValue = $asesorNks[$butirId] ?? null;
                                $catatanNk = $asesorCatatanNks[$butirId] ?? '';
                                $butirCatatan = $asesorButirCatatans[$butirId] ?? '';
                                $isNaFinal = !empty($asesorEval) && ($asesorEval->is_final ?? false);
                                $edpmValue = $pesantrenEval->value ?? '-';

                                // Delta calculation (Asesor 1 only)
                                $delta = null;
                                $deltaVariant = 'secondary';
                                if ($asesorTipe === 1 && $asesorEval && $otherEval) {
                                    $delta = abs(($asesorEval->value ?? 0) - ($otherEval->value ?? 0));
                                    $deltaVariant = $delta > 1 ? 'danger' : ($delta > 0 ? 'warning' : 'success');
                                }
                            @endphp
                            <tr>
                                @if($loop->first)
                                    <td rowspan="{{ $komponen->butirs->count() }}" class="fw-semibold align-top">
                                        {{ $komponen->nama ?? $komponen->name ?? '' }}
                                    </td>
                                @endif
                                <td class="text-muted">{{ $butir->no_sk ?? '-' }}</td>
                                <td class="text-muted">{{ $butir->no_butir ?? $loop->iteration }}</td>
                                <td>{{ $butir->pernyataan ?? $butir->statement ?? '-' }}</td>
                                <td class="text-center">
                                    <x-ui.badge variant="light">{{ $edpmValue }}</x-ui.badge>
                                </td>

                                @if($asesorTipe === 1)
                                    {{-- Nilai Ketua (NA) --}}
                                    <td class="text-center">
                                        @if($isNaFinal || $isLocked)
                                            <x-ui.badge variant="primary">{{ $asesorEval->value ?? '-' }}</x-ui.badge>
                                            <i class="ki-solid ki-lock fs-7 text-muted ms-1"></i>
                                        @else
                                            <select class="form-select form-select-sm"
                                                    x-on:change="saveNa({{ $butirId }}, $event.target.value, false)">
                                                <option value="">-</option>
                                                @for($i = 1; $i <= 5; $i++)
                                                    <option value="{{ $i }}" {{ ($asesorEval->value ?? '') == $i ? 'selected' : '' }}>{{ $i }}</option>
                                                @endfor
                                            </select>
                                            <x-ui.button type="button" variant="light-primary" size="sm" class="btn-icon mt-1"
                                                    x-on:click="saveNa({{ $butirId }}, '{{ $asesorEval->value ?? '' }}', true)"
                                                    title="Kunci Nilai">
                                                <i class="ki-solid ki-lock fs-7"></i>
                                            </x-ui.button>
                                        @endif
                                    </td>
                                    {{-- Nilai Anggota (read-only) --}}
                                    <td class="text-center">
                                        <x-ui.badge variant="light">{{ $otherEval->value ?? '-' }}</x-ui.badge>
                                    </td>
                                    {{-- Delta --}}
                                    <td class="text-center">
                                        @if($delta !== null)
                                            <x-ui.badge :variant="$deltaVariant">{{ $delta }}</x-ui.badge>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    {{-- Nilai Kelompok (NK) --}}
                                    <td class="text-center">
                                        @if($isLocked || !($asesorFinalStatus['kelompok_unlocked'] ?? false))
                                            <x-ui.badge variant="{{ !empty($nkValue) ? 'success' : 'light' }}">{{ $nkValue ?? '-' }}</x-ui.badge>
                                        @else
                                            <select class="form-select form-select-sm"
                                                    x-on:change="saveNk({{ $butirId }}, $event.target.value, false)">
                                                <option value="">-</option>
                                                @for($i = 1; $i <= 5; $i++)
                                                    <option value="{{ $i }}" {{ ($nkValue ?? '') == $i ? 'selected' : '' }}>{{ $i }}</option>
                                                @endfor
                                            </select>
                                        @endif
                                    </td>
                                    {{-- Catatan Butir --}}
                                    <td>
                                        @if(!$isLocked)
                                            <textarea class="form-control form-control-sm" rows="1" placeholder="Catatan..."
                                                      name="catatan_butir[{{ $butirId }}]"
                                                      >{{ $butirCatatan }}</textarea>
                                        @else
                                            <span class="text-muted fs-8">{{ $butirCatatan ?: '-' }}</span>
                                        @endif
                                    </td>
                                @else
                                    {{-- Asesor 2: Only Nilai Anggota --}}
                                    <td class="text-center">
                                        @if($isNaFinal || $isLocked)
                                            <x-ui.badge variant="primary">{{ $asesorEval->value ?? '-' }}</x-ui.badge>
                                            <i class="ki-solid ki-lock fs-7 text-muted ms-1"></i>
                                        @else
                                            <select class="form-select form-select-sm"
                                                    x-on:change="saveNa({{ $butirId }}, $event.target.value, false)">
                                                <option value="">-</option>
                                                @for($i = 1; $i <= 5; $i++)
                                                    <option value="{{ $i }}" {{ ($asesorEval->value ?? '') == $i ? 'selected' : '' }}>{{ $i }}</option>
                                                @endfor
                                            </select>
                                            <x-ui.button type="button" variant="light-primary" size="sm" class="btn-icon mt-1"
                                                    x-on:click="saveNa({{ $butirId }}, '{{ $asesorEval->value ?? '' }}', true)"
                                                    title="Kunci Nilai">
                                                <i class="ki-solid ki-lock fs-7"></i>
                                            </x-ui.button>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    @endforeach
        </tbody>
    </x-ui.simple-table>
</x-ui.section-card>

{{-- Catatan Rekomendasi per Komponen (Asesor 1 only) --}}
@if($asesorTipe === 1 && !$isLocked)
<x-ui.section-card title="Catatan Rekomendasi" subtitle="Catatan rekomendasi per komponen penilaian" class="mt-4">
    <div class="p-6">
        @foreach($komponens as $komponen)
            <div class="mb-4">
                <label class="form-label fw-semibold">{{ $komponen->nama ?? $komponen->name ?? 'Komponen ' . $loop->iteration }}</label>
                <textarea class="form-control" rows="2" name="catatan_komponen[{{ $komponen->id }}]" placeholder="Catatan rekomendasi...">{{ $asesorCatatans[$komponen->id] ?? '' }}</textarea>
            </div>
        @endforeach
    </div>
</x-ui.section-card>
@endif

{{-- Scroll Actions --}}
<div class="position-fixed bottom-0 end-0 mb-6 me-6 d-flex flex-column gap-2" style="z-index: 100;">
    <x-ui.button type="button" variant="light-primary" size="sm" class="btn-icon shadow" onclick="window.scrollTo({top: 0, behavior: 'smooth'})" title="Ke Atas">
        <i class="ki-solid ki-arrow-up fs-5"></i>
    </x-ui.button>
    <x-ui.button type="button" variant="light-primary" size="sm" class="btn-icon shadow" onclick="window.scrollTo({top: document.body.scrollHeight, behavior: 'smooth'})" title="Ke Bawah">
        <i class="ki-solid ki-arrow-down fs-5"></i>
    </x-ui.button>
</div>
