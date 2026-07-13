@props([
    'label',
    'value',
    'variant' => 'primary',
    'icon' => 'abstract-26',
    'href' => null,
])

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => 'd-flex align-items-center justify-content-between py-4 border-bottom border-gray-200 border-dashed text-decoration-none']) }}>
        <div class="d-flex align-items-center gap-4">
            <div class="symbol symbol-40px">
                <div class="symbol-label bg-light-{{ $variant }} text-{{ $variant }}">
                    <x-ui.icon :name="$icon" class="fs-2" />
                </div>
            </div>
            <span class="fw-semibold text-gray-700">{{ $label }}</span>
        </div>

        <span class="fw-semibold text-gray-900">{{ $value }}</span>
    </a>
@else
    <div {{ $attributes->merge(['class' => 'd-flex align-items-center justify-content-between py-4 border-bottom border-gray-200 border-dashed']) }}>
        <div class="d-flex align-items-center gap-4">
            <div class="symbol symbol-40px">
                <div class="symbol-label bg-light-{{ $variant }} text-{{ $variant }}">
                    <x-ui.icon :name="$icon" class="fs-2" />
                </div>
            </div>
            <span class="fw-semibold text-gray-700">{{ $label }}</span>
        </div>

        <span class="fw-semibold text-gray-900">{{ $value }}</span>
    </div>
@endif