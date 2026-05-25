@props([
    'variant' => 'primary',
    'title' => null,
    'icon' => null,
    'dismissible' => false,
])

@php
    $allowed = ['primary', 'success', 'warning', 'danger', 'info', 'secondary'];
    $variant = in_array($variant, $allowed, true) ? $variant : 'primary';

    $icon ??= match ($variant) {
        'success' => 'check-circle',
        'warning' => 'warning-2',
        'danger' => 'information-5',
        'info' => 'information-2',
        default => 'information-5',
    };

    $classes = trim(
        'alert bg-light-' . $variant .
        ' border border-' . $variant .
        ' border-dashed d-flex align-items-start gap-4 p-5 spm-alert' .
        ($dismissible ? ' alert-dismissible' : '')
    );
@endphp

<div data-ui-alert="metronic" role="alert" {{ $attributes->merge(['class' => $classes]) }}>
    @if($icon)
        <x-ui.icon :name="$icon" class="fs-2 text-{{ $variant }} flex-shrink-0 mt-1" />
    @endif

    <div class="d-flex flex-column min-w-0 flex-grow-1">
        @if($title)
            <h4 class="spm-alert-title text-{{ $variant }} mb-1">{{ $title }}</h4>
        @endif

        <div class="spm-alert-content text-gray-700 fw-semibold">
            {{ $slot }}
        </div>
    </div>

    @isset($actions)
        <div class="d-flex align-items-start gap-2 ms-auto flex-shrink-0">
            {{ $actions }}
        </div>
    @endisset

    @if($dismissible)
        <x-ui.button
            type="button"
            variant="light"
            size="sm"
            class="btn-icon btn-active-light-{{ $variant }} ms-auto flex-shrink-0"
            data-bs-dismiss="alert"
            aria-label="Tutup"
        >
            <x-ui.icon name="cross" class="fs-5" />
        </x-ui.button>
    @endif
</div>
