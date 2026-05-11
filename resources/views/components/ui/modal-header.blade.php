@props([
    'title',
    'subtitle' => null,
    'icon' => null,
    'variant' => 'primary',
    'close' => true,
])

@php
    $variant = in_array($variant, ['primary', 'success', 'warning', 'danger', 'info', 'secondary'], true)
        ? $variant
        : 'primary';
@endphp

<div data-ui-modal-header="metronic" {{ $attributes->merge(['class' => 'modal-header spm-modal-header']) }}>
    <div class="d-flex align-items-start gap-3 min-w-0">
        @if($icon)
            <div class="symbol symbol-40px">
                <div class="symbol-label bg-light-{{ $variant }} text-{{ $variant }}">
                    <x-ui.icon :name="$icon" class="fs-2" />
                </div>
            </div>
        @endif

        <div class="min-w-0">
            <h2 class="fw-bold text-gray-900 mb-1">{{ $title }}</h2>

            @if($subtitle)
                <div class="text-muted fw-semibold fs-7">{{ $subtitle }}</div>
            @endif
        </div>
    </div>

    @if($close)
        <button
            type="button"
            class="btn btn-sm btn-icon btn-light btn-active-light-primary spm-modal-close"
            x-on:click="$dispatch('close')"
            aria-label="Tutup"
        >
            <x-ui.icon name="cross-circle" class="fs-3" />
        </button>
    @endif
</div>
