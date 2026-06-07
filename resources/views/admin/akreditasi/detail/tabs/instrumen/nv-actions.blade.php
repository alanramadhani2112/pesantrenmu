<x-ui.section-card title="Simpan Nilai Validasi (NV)" subtitle="Simpan seluruh Nilai Validasi yang telah diinput.">
    <div class="p-6">
        <form method="POST" action="{{ route('admin.akreditasi-detail.save-nv', $akreditasi->uuid) }}">
            @csrf
            <x-ui.button type="submit" variant="success" size="lg">
                <x-ui.icon name="save-2" class="fs-4 me-2" />
                Simpan Semua NV
            </x-ui.button>
        </form>
    </div>
</x-ui.section-card>
