{{-- Tab: SDM (Sumber Daya Manusia) --}}
<x-ui.section-card title="Rekapitulasi SDM" subtitle="Data sumber daya manusia pesantren" class="spm-asesor-table-panel">
<div class="p-5">
        <x-ui.simple-table class="spm-sdm-table-wrap" table-class="table-row-gray-200 spm-sdm-review-table">
                <thead>
                    <tr class="fw-semibold text-muted">
                        <th>Jenjang</th>
                        <th class="text-center">Santri (L)</th>
                        <th class="text-center">Santri (P)</th>
                        <th class="text-center">Ustadz Dirosah (L)</th>
                        <th class="text-center">Ustadz Dirosah (P)</th>
                        <th class="text-center">Ustadz Non-Dirosah (L)</th>
                        <th class="text-center">Ustadz Non-Dirosah (P)</th>
                        <th class="text-center">Pamong (L)</th>
                        <th class="text-center">Pamong (P)</th>
                        <th class="text-center">Musyrif (L)</th>
                        <th class="text-center">Musyrif (P)</th>
                        <th class="text-center">Tendik (L)</th>
                        <th class="text-center">Tendik (P)</th>
                    </tr>
                </thead>
                <tbody>
                    @if($levels && count($levels) > 0)
                        @foreach($levels as $level)
                            @php
                                $levelName = is_object($level) ? ($level->nama ?? $level->name ?? $level->unit ?? '-') : $level;
                                $row = $sdm[$levelName] ?? null;
                            @endphp
                            <tr>
                                <td class="fw-semibold">{{ $levelName }}</td>
                                @foreach($fields as $field)
                                    <td class="text-center">{{ $row?->{$field} ?? 0 }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="13" class="text-center text-muted py-4">Data SDM belum tersedia.</td>
                        </tr>
                    @endif
                </tbody>
                @if($levels && count($levels) > 0)
                <tfoot>
                    <tr class="fw-semibold bg-light">
                        <td>Total</td>
                        @foreach($fields as $field)
                            @php
                                $total = 0;
                                foreach ($levels as $level) {
                                    $levelName = is_object($level) ? ($level->nama ?? $level->name ?? $level->unit ?? '-') : $level;
                                    $total += (int) (($sdm[$levelName] ?? null)?->{$field} ?? 0);
                                }
                            @endphp
                            <td class="text-center">{{ $total }}</td>
                        @endforeach
                    </tr>
                </tfoot>
                @endif
        </x-ui.simple-table>
    </div>
</x-ui.section-card>
