@props([
    'label' => 'Aksi',
])

<div data-ui-action-menu="metronic" class="d-inline-block">
    <button
        type="button"
        class="btn btn-sm btn-light btn-active-light-primary"
        data-kt-menu-trigger="click"
        data-kt-menu-placement="bottom-end"
    >
        {{ $label }}
        <x-ui.icon name="down" class="fs-7 ms-1" />
    </button>

    <div
        class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-700 menu-state-bg-light-primary fw-semibold py-3 fs-7 w-175px"
        data-kt-menu="true"
    >
        {{ $slot }}
    </div>
</div>
