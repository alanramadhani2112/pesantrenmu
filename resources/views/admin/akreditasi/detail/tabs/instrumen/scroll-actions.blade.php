<x-ui.section-card title="" subtitle="">
    <div class="p-6 d-flex flex-column gap-4 align-items-end">
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
                <label for="nvReason" class="form-label fw-semibold">Alasan perubahan NV</label>
                <textarea id="nvReason" name="nvReason" rows="3" class="form-control" placeholder="Wajib diisi jika ada NV final yang berbeda dari NK.">{{ old('nvReason') }}</textarea>
                <div class="form-text">Boleh dikosongkan jika semua NV mengikuti NK.</div>
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
