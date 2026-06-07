{{-- Tab: SDM (Sumber Daya Manusia) --}}
<x-ui.section-card title="Rekapitulasi SDM" subtitle="Data sumber daya manusia pesantren">
    <div class="p-6">
        <div class="table-responsive">
            <table class="table table-row-bordered table-row-gray-200 align-middle gs-0 gy-3">
                <thead>
                    <tr class="fw-bold text-muted">
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
                            <tr>
                                <td class="fw-semibold">{{ $level->nama ?? $level->name ?? '-' }}</td>
                                @foreach($fields as $field)
                                    <td class="text-center">{{ $sdm->{$field . '_' . ($level->id ?? $loop->parent->iteration)} ?? 0 }}</td>
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
                    <tr class="fw-bold bg-light">
                        <td>Total</td>
                        @foreach($fields as $field)
                            @php
                                $total = 0;
                                foreach ($levels as $level) {
                                    $total += $sdm->{$field . '_' . ($level->id ?? 0)} ?? 0;
                                }
                            @endphp
                            <td class="text-center">{{ $total }}</td>
                        @endforeach
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</x-ui.section-card>
