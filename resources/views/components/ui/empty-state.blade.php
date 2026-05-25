@props([
    'title',
    'description' => null,
    'illustration' => null,
    'variant' => 'primary',
])

<div data-ui-empty-state="metronic" {{ $attributes->merge(['class' => 'd-flex flex-column align-items-center justify-content-center text-center py-12']) }}>
    @isset($illustration)
        <div class="mb-5">
            {{ $illustration }}
        </div>
    @else
        <div class="symbol symbol-65px mb-5">
            <div class="symbol-label bg-light-{{ $variant }} text-{{ $variant }}">
                @isset($icon)
                    {{ $icon }}
                @else
                    <i class="ki-duotone ki-information-5 fs-2x">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                    </i>
                @endisset
            </div>
        </div>
    @endisset

    <h3 class="fw-semibold text-gray-900 mb-2">{{ $title }}</h3>

    @if($description)
        <p class="text-muted fw-semibold fs-6 mb-0 mw-450px">{{ $description }}</p>
    @endif

    @isset($action)
        <div class="mt-5">
            {{ $action }}
        </div>
    @endisset
</div>
