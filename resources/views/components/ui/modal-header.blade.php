@props([
    'title' => '',
    'subtitle' => null,
    'icon' => null,
    'variant' => 'primary',
    'close' => true,
    'titleId' => null,
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
            <h2 @if($titleId) id="{{ $titleId }}" @endif class="fw-semibold text-gray-900 mb-1">{{ $title }}</h2>

            @if($subtitle)
                <div class="text-muted fw-semibold fs-7">{{ $subtitle }}</div>
            @endif
        </div>
    </div>

    @if($close)
        <x-ui.button
            type="button"
            variant="light"
            size="sm"
            class="btn-icon btn-active-light-primary spm-modal-close"
            x-on:click="$dispatch('close')"
            aria-label="Tutup"
        >
            <x-ui.icon name="cross-circle" class="fs-3" />
        </x-ui.button>
    @endif
</div>
