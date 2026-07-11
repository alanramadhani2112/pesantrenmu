@props([
    'id' => '',
    'title' => '',
    'subtitle' => '',
    'screenshot' => null,
    'screenshotAlt' => '',
])

<section id="{{ $id }}" class="card card-flush mb-8 panduan-section">
    <div class="card-header border-0 pt-6 pb-0 px-6 px-lg-8">
        <div class="card-title d-flex align-items-start gap-4 m-0">
            <span class="panduan-section-icon">
                <i class="ki-duotone ki-book-open fs-2">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span>
                </i>
            </span>
            <div class="min-w-0">
                <h3 class="fw-semibold fs-3 text-gray-900 mb-1">{{ $title }}</h3>
                @if ($subtitle)
                    <p class="text-gray-600 fs-6 lh-lg mb-0">{{ $subtitle }}</p>
                @endif
            </div>
        </div>
    </div>
    <div class="card-body pt-5 px-6 px-lg-8 pb-6 pb-lg-8">
        @if ($screenshot)
            <div class="panduan-screenshot mb-6 text-center">
                <img src="{{ $screenshot }}"
                     alt="{{ $screenshotAlt }}"
                     class="img-fluid w-100 spm-image-hover"
                     loading="lazy"
                     style="max-height: 500px; object-fit: contain;" />
            </div>
        @endif
        <div class="panduan-section-body fs-6">
            {{ $slot }}
        </div>
    </div>
</section>
