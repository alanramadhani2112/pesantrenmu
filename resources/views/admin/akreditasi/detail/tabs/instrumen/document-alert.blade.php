@if(!$isLocked)
    <div class="notice bg-light-warning rounded-3 p-4 d-flex align-items-center gap-3" role="alert">
        <span class="svg-icon svg-icon-2tx svg-icon-warning">
            <i class="bi bi-exclamation-triangle fs-2x text-warning"></i>
        </span>
        <div>
            <p class="fw-semibold mb-1">Status Review: Belum Dikunci</p>
            <p class="mb-0 fs-7 text-gray-700">Perhatian: Status review belum dikunci oleh admin, data akreditasi belum final dan mungkin berubah.</p>
        </div>
    </div>
@endif
