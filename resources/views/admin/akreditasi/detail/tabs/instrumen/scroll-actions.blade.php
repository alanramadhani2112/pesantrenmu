<x-ui.section-card title="" subtitle="">
<div class="p-5 d-flex flex-column gap-4 align-items-end">
        <form method="POST" action="{{ route('admin.akreditasi-detail.save-nv', $akreditasi->uuid) }}" class="d-inline">
            @csrf
            @foreach($adminNvs as $butirId => $item)
                <input type="hidden" name="adminNvs[{{ $butirId }}]" value="{{ is_array($item) ? ($item['nv'] ?? '') : $item }}">
            @endforeach
            <x-ui.button type="submit" variant="success">
                <x-ui.icon name="save-2" class="fs-4 me-1" />
                Simpan NV
            </x-ui.button>
        </form>
        <form method="POST" action="{{ route('admin.akreditasi-detail.finalize-nv', $akreditasi->uuid) }}" class="w-100">
            @csrf
            @foreach($adminNvs as $butirId => $item)
                <input type="hidden" name="adminNvs[{{ $butirId }}]" value="{{ is_array($item) ? ($item['nv'] ?? '') : $item }}">
            @endforeach
            <div class="mb-3">
                <label class="form-label fw-semibold mb-2">Alasan perubahan NV (per butir)</label>
                <div class="form-text mb-2">Isi hanya untuk butir yang NV-nya berbeda dari NK. Kosongkan jika sama.</div>
                @foreach($adminNvs as $butirId => $item)
                    @php
                        $nvVal = is_array($item) ? ($item['nv'] ?? '') : $item;
                        $nkVal = is_array($item) ? ($item['nk'] ?? '') : '';
                    @endphp
                    @if($nvVal && $nkVal && (int)$nvVal !== (int)$nkVal)
                        <div class="mb-2 p-2 bg-body border border-dashed border-gray-300 rounded">
                            <label for="nvReasons_{{ $butirId }}" class="form-label fw-semibold small mb-1">
                                Butir #{{ $butirId }} (NK: {{ $nkVal }} → NV: {{ $nvVal }})
                            </label>
                            <textarea id="nvReasons_{{ $butirId }}" name="nvReasons[{{ $butirId }}]" rows="2" class="form-control form-control-sm" placeholder="Wajib diisi karena NV berbeda dari NK." required>{{ old("nvReasons.$butirId") }}</textarea>
                        </div>
                    @endif
                @endforeach
            </div>
            <div class="d-flex justify-content-end">
                <x-ui.button type="submit" variant="primary">
                    <x-ui.icon name="lock" class="fs-4 me-1" />
                    Finalisasi Semua NV
                </x-ui.button>
            </div>
        </form>
    </div>
</x-ui.section-card>
