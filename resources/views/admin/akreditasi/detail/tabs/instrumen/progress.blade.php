<x-ui.section-card title="Progress Penilaian">
<div class="p-5 d-flex flex-column gap-4">
        @foreach($scoringProgressCards as $card)
            <div class="d-flex align-items-center gap-3">
                <div class="fw-semibold text-gray-800 w-125px">{{ $card['nama'] }}</div>
                <div class="flex-grow-1 h-16px bg-light rounded-3 overflow-hidden">
                    <div class="h-100 rounded-3 {{ $card['colorClass'] }}" style="width: {{ $card['percentage'] }}%"></div>
                </div>
                <div class="fw-semibold text-gray-700 ms-2 min-w-50px text-end">{{ $card['percentage'] }}%</div>
            </div>
        @endforeach
    </div>
</x-ui.section-card>
