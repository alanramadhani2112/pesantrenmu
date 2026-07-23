<x-ui.section-card title="Progress Penilaian" subtitle="Ringkasan kesiapan nilai sebelum admin mengisi atau memfinalkan NV.">
    <div class="p-5 d-flex flex-column gap-5">
        <div class="row g-4">
            @foreach($scoringProgressCards as $card)
                @php $progress = $card['progress']; @endphp
                <div class="col-md-4">
                    <div class="border border-dashed border-gray-300 rounded p-4 h-100 bg-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <div class="fw-semibold text-gray-900">{{ $card['label'] }}</div>
                                <div class="text-muted fs-8">{{ $card['description'] }}</div>
                            </div>
                            <x-ui.badge variant="{{ ((float) $progress['percentage']) >= 100.0 ? 'success' : 'warning' }}">
                                {{ $progress['filled'] }}/{{ $progress['total'] }}
                            </x-ui.badge>
                        </div>
                        <div class="h-8px bg-light rounded-3 overflow-hidden mb-2">
                            <div class="h-100 rounded-3 {{ $card['colorClass'] }}" style="width: {{ $progress['percentage'] }}%"></div>
                        </div>
                        <div class="d-flex justify-content-between text-muted fs-8">
                            <span>{{ $progress['percentage'] }}% lengkap</span>
                            <span>{{ max((int) $progress['total'] - (int) $progress['filled'], 0) }} belum diisi</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="notice bg-light-primary border border-primary border-dashed rounded p-4" role="status">
            <div class="fw-semibold text-gray-900 mb-1">Alur baca nilai</div>
            <div class="text-muted fs-7">
                NA1 dan NA2 adalah referensi asesor. NK adalah nilai kelompok dari ketua asesor. NV wajib dipilih admin sebelum tersimpan, lalu dapat diverifikasi atau diubah admin.
                @if(($scoreSummary['pendingCount'] ?? 0) > 0)
                    <span class="fw-semibold text-warning ms-1">{{ $scoreSummary['pendingCount'] }} butir masih menunggu NV tersimpan.</span>
                @else
                    <span class="fw-semibold text-success ms-1">Semua NV wajib sudah terisi.</span>
                @endif
            </div>
        </div>
    </div>
</x-ui.section-card>
