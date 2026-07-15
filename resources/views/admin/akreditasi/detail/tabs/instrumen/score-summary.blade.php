<x-ui.section-card title="Ringkasan Skor">
    <div class="p-5">
        @if($scoreSummary['isPending'])
            <div class="notice bg-body border border-dashed border-gray-300 rounded p-4 mb-5" role="status">
                <div class="fw-semibold text-warning mb-1">Menunggu NV lengkap</div>
                <div class="text-muted fs-7">{{ $scoreSummary['pendingCount'] }} butir masih belum memiliki NV tersimpan.</div>
            </div>
        @endif

        <div class="row g-4">
            @if($scoreSummary['isIpr'])
                <div class="col-md-6">
                    <div class="bg-body border border-dashed border-gray-300 rounded p-4 text-center">
                        <div class="text-muted fs-7 mb-2">IPR</div>
                        <div class="fw-semibold fs-3x text-info">{{ $scoreSummary['iprScore'] }}</div>
                        <div class="text-muted fs-7 mt-1">dari 100</div>
                    </div>
                </div>
            @endif

            @foreach($scoreSummary['komponenDetails'] as $detail)
                <div class="col-md-6">
                    <div class="bg-body border border-dashed border-gray-300 rounded p-4 text-center">
                        <div class="text-muted fs-7 mb-2">{{ $detail['nama'] }}</div>
                        <div class="fw-semibold fs-3x text-primary">{{ $detail['score'] }}</div>
                        <div class="text-muted fs-7 mt-1">
                            {{ $detail['complete'] ? '(' . $detail['ci'] . ' / ' . $detail['cmaks'] . ') x ' . $detail['factor'] : 'Belum lengkap' }}
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="col-12">
                <div class="bg-body border border-dashed border-gray-300 rounded p-4 text-center">
                    <div class="text-muted fs-7 mb-2">Skor Akhir</div>
                    <div class="fw-semibold fs-3x text-success">{{ $scoreSummary['isPending'] ? '-' : $scoreSummary['finalScore'] }}</div>
                    <div class="mt-3">
                        <x-ui.badge variant="{{ $scoreSummary['isPending'] ? 'warning' : 'success' }}" size="lg">
                            Predikat: {{ $scoreSummary['isPending'] ? 'Pending' : $scoreSummary['predicate'] }}
                        </x-ui.badge>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-ui.section-card>
