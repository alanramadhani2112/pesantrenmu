@props([
    'label',
    'value',
    'variant' => 'primary',
    'icon' => 'abstract-26',
])

<div {{ $attributes->merge(['class' => 'd-flex align-items-center justify-content-between py-4 border-bottom border-gray-200 border-dashed']) }}>
    <div class="d-flex align-items-center gap-4">
        <div class="symbol symbol-40px">
            <div class="symbol-label bg-light-{{ $variant }} text-{{ $variant }}">
                <x-ui.icon :name="$icon" class="fs-2" />
            </div>
        </div>
        <span class="fw-bold text-gray-700">{{ $label }}</span>
    </div>

    <span class="fw-bolder text-gray-900">{{ $value }}</span>
</div>
