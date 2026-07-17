@if(!$isLocked)
    <div class="notice bg-body border border-dashed border-gray-300 rounded p-4 d-flex align-items-center gap-3" role="alert">
        <x-ui.icon name="information-5" class="fs-2x text-warning" />
        <div>
            <p class="fw-semibold mb-1">Status Review: Belum Dikunci</p>
            <p class="mb-0 fs-7 text-gray-700">Perhatian: Status review belum dikunci oleh admin, data akreditasi belum final dan mungkin berubah.</p>
        </div>
    </div>
@endif
