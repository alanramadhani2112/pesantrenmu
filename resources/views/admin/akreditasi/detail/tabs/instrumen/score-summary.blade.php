<x-ui.section-card title="Ringkasan Skor">
    <div class="p-6">
        <div class="row g-5">
            @if($scoreSummary['isIpr'])
                <div class="col-md-6">
                    <div class="bg-light-info rounded-3 p-4 text-center">
                        <div class="text-muted fs-7 mb-2">IPR</div>
                        <div class="fw-semibold fs-3x text-info">{{ $scoreSummary['iprScore'] }}</div>
                        <div class="text-muted fs-7 mt-1">dari 400</div>
                    </div>
                </div>
            @endif

            @foreach($scoreSummary['komponenDetails'] as $detail)
                <div class="col-md-6">
                    <div class="bg-light-primary rounded-3 p-4 text-center">
                        <div class="text-muted fs-7 mb-2">{{ $detail['nama'] }}</div>
                        <div class="fw-semibold fs-3x text-primary">{{ $detail['score'] }}</div>
                        <div class="text-muted fs-7 mt-1">
                            ({{ $detail['ci'] }} / {{ $detail['cmaks'] }}) x {{ $detail['factor'] }}
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="col-12">
                <div class="bg-light-success rounded-3 p-5 text-center">
                    <div class="text-muted fs-7 mb-2">Skor Akhir</div>
                    <div class="fw-semibold fs-3x text-success">{{ $scoreSummary['finalScore'] }}</div>
                    <div class="mt-3">
                        <x-ui.badge variant="success" size="lg">
                            Predikat: {{ $scoreSummary['predicate'] }}
                        </x-ui.badge>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-ui.section-card>
