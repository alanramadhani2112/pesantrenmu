    {{-- Reject Documents Modal --}}
    <x-ui.modal name="reject-documents-modal" title="Tolak Dokumen" maxWidth="lg">
        <form method="POST" action="{{ route('asesor.akreditasi.reject-document') }}" id="rejectDocumentsForm">
            @csrf
            <input type="hidden" name="akreditasi_id" value="{{ $akreditasi->id }}">
<div class="p-5">
                <p class="text-muted mb-4">Pilih dokumen yang ditolak dan berikan alasan penolakan.</p>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Dokumen yang Ditolak</label>
                    @if(!empty($selectableItems))
                        @foreach($selectableItems as $section)
                            <div class="mb-3">
                                <div class="fw-semibold fs-7 mb-2">{{ $section['label'] ?? '' }}</div>
                                @if(!empty($section['children']))
                                    @foreach($section['children'] as $child)
                                        <div class="form-check mb-1 ms-3">
                                            <input class="form-check-input" type="checkbox" name="perbaikan[]" value="{{ $child['value'] ?? '' }}" id="reject-{{ $loop->parent->index }}-{{ $loop->index }}">
                                            <label class="form-check-label" for="reject-{{ $loop->parent->index }}-{{ $loop->index }}">{{ $child['label'] ?? '' }}</label>
                                        </div>
                                        @if(!empty($child['subChildren']))
                                            @foreach($child['subChildren'] as $sub)
                                                <div class="form-check mb-1 ms-6">
                                                    <input class="form-check-input" type="checkbox" name="perbaikan[]" value="{{ $sub['value'] ?? '' }}" id="reject-{{ $loop->parent->parent->index }}-{{ $loop->parent->index }}-{{ $loop->index }}">
                                                    <label class="form-check-label" for="reject-{{ $loop->parent->parent->index }}-{{ $loop->parent->index }}-{{ $loop->index }}">{{ $sub['label'] ?? '' }}</label>
                                                </div>
                                            @endforeach
                                        @endif
                                    @endforeach
                                @endif
                            </div>
                        @endforeach
                    @endif
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold" for="rejectionExplanation">Catatan Penolakan</label>
                    <textarea class="form-control" name="catatan" id="rejectionExplanation" rows="4" placeholder="Jelaskan alasan penolakan dokumen..." required></textarea>
                </div>
            </div>
            <x-ui.modal-footer>
                <x-ui.button type="button" variant="light" x-on:click="$dispatch('close-modal', 'reject-documents-modal')">Batal</x-ui.button>
                <x-ui.button type="button" variant="danger" x-on:click="confirmSubmitRejection()">
                    <x-ui.icon name="cross-circle" class="fs-4 me-1" />
                    Kirim Penolakan
                </x-ui.button>
            </x-ui.modal-footer>
        </form>
    </x-ui.modal>

    {{-- Schedule Visitasi Modal --}}
    <x-ui.modal name="schedule-visitasi-modal" title="Jadwalkan Visitasi" maxWidth="md">
        <form method="POST" action="{{ route('asesor.akreditasi.schedule-visitasi') }}" id="scheduleVisitasiForm">
            @csrf
            <input type="hidden" name="akreditasi_id" value="{{ $akreditasi->id }}">
<div class="p-5">
                <div class="mb-4">
                    <label class="form-label fw-semibold" for="tanggalMulai">Tanggal Mulai</label>
                    <input type="date" class="form-control" name="tanggal_mulai" id="tanggalMulai" required>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold" for="tanggalAkhir">Tanggal Selesai</label>
                    <input type="date" class="form-control" name="tanggal_akhir" id="tanggalAkhir" required>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold" for="catatanVisitasi">Catatan</label>
                    <textarea class="form-control" name="catatan" id="catatanVisitasi" rows="3" placeholder="Catatan tambahan untuk visitasi..."></textarea>
                </div>
            </div>
            <x-ui.modal-footer>
                <x-ui.button type="button" variant="light" x-on:click="$dispatch('close-modal', 'schedule-visitasi-modal')">Batal</x-ui.button>
                <x-ui.button type="submit" variant="info">
                    <x-ui.icon name="calendar-add" class="fs-4 me-1" />
                    Jadwalkan
                </x-ui.button>
            </x-ui.modal-footer>
        </form>
    </x-ui.modal>
