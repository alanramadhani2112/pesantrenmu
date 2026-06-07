<x-ui.section-card title="" subtitle="">
    <div class="p-6 d-flex justify-content-end gap-3">
        <form method="POST" action="{{ route('admin.akreditasi-detail.save-nv', $akreditasi->uuid) }}" class="d-inline">
            @csrf
            <x-ui.button type="submit" variant="success">
                <x-ui.icon name="save-2" class="fs-4 me-1" />
                Simpan NV
            </x-ui.button>
        </form>
        <form method="POST" action="{{ route('admin.akreditasi-detail.finalize-nv', $akreditasi->uuid) }}" class="d-inline">
            @csrf
            <x-ui.button type="submit" variant="primary">
                <x-ui.icon name="lock" class="fs-4 me-1" />
                Finalisasi Semua NV
            </x-ui.button>
        </form>
    </div>
</x-ui.section-card>
