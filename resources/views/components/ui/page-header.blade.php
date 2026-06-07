@props([
    'title',
    'subtitle' => null,
    'compact' => false,
])

<div {{ $attributes->merge(['class' => 'd-flex flex-column flex-md-row align-items-md-center justify-content-between ' . ($compact ? 'gap-3 mb-4' : 'gap-4 mb-6')]) }}>
    <div>
        <h1 class="spm-page-title {{ $compact ? 'spm-page-title-compact mb-1' : 'mb-2' }} text-gray-900">{{ $title }}</h1>

        @if($subtitle)
            <p class="spm-page-subtitle text-muted mb-0">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($toolbar)
        <div class="d-flex align-items-center gap-2">
            {{ $toolbar }}
        </div>
    @endisset
</div>
