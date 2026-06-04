@props([
    'id' => '',
    'title' => '',
    'subtitle' => '',
    'screenshot' => null,
    'screenshotAlt' => '',
])

<div id="{{ $id }}" class="card card-flush mb-8 panduan-section">
    <div class="card-header border-0 pt-6 pb-0 px-6">
        <h3 class="card-title fw-bold fs-3 text-gray-900">{{ $title }}</h3>
    </div>
    @if ($subtitle)
        <div class="card-header border-0 pt-0 pb-0 px-6">
            <p class="text-muted fs-6 lh-lg">{{ $subtitle }}</p>
        </div>
    @endif
    <div class="card-body pt-4 px-6 pb-6">
        @if ($screenshot)
            <div class="mb-6 text-center rounded border border-gray-300 overflow-hidden bg-gray-100">
                <img src="{{ $screenshot }}"
                     alt="{{ $screenshotAlt }}"
                     class="img-fluid w-100"
                     loading="lazy"
                     style="max-height: 500px; object-fit: contain;" />
            </div>
        @endif
        <div class="panduan-section-body fs-6">
            {{ $slot }}
        </div>
    </div>
</div>
