<x-ui.section-card title="Simpan Nilai Validasi (NV)" subtitle="Simpan draft atau finalisasi seluruh Nilai Validasi yang telah diinput.">
<div class="p-5 d-flex flex-wrap gap-3">
        <x-ui.button
            type="submit"
            variant="light-primary"
            size="lg"
            formaction="{{ route('admin.akreditasi-detail.save-nv', $akreditasi->uuid) }}"
        >
            <x-ui.icon name="save-2" class="fs-4 me-2" />
            Simpan Draft NV
        </x-ui.button>

        <x-ui.button
            type="submit"
            variant="success"
            size="lg"
            formaction="{{ route('admin.akreditasi-detail.finalize-nv', $akreditasi->uuid) }}"
            onclick="return confirm('Finalisasi semua NV? NV yang sudah final tidak dapat diubah.')"
        >
            <x-ui.icon name="lock-2" class="fs-4 me-2" />
            Finalisasi Semua NV
        </x-ui.button>
    </div>
</x-ui.section-card>
