@props([
    'label',
    'href' => null,
    'description' => null,
])

<div data-ui-document-item="metronic" {{ $attributes->merge(['class' => 'spm-document-item']) }}>
    <div class="d-flex align-items-center gap-3 min-w-0">
        <span class="symbol symbol-40px">
            <span class="symbol-label {{ $href ? 'bg-light-primary text-primary' : 'bg-light text-gray-500' }}">
                <x-ui.icon name="document" class="fs-2" />
            </span>
        </span>

        <span class="d-flex flex-column min-w-0">
            <span class="fw-bold text-gray-800 spm-document-title">{{ $label }}</span>
            @if($description)
                <span class="text-muted fw-semibold fs-8">{{ $description }}</span>
            @else
                <span class="text-muted fw-semibold fs-8">{{ $href ? 'Dokumen tersedia' : 'Belum diunggah' }}</span>
            @endif
        </span>
    </div>

    @if($href)
        <x-ui.button :href="$href" target="_blank" variant="light" size="sm" class="btn-active-light-primary">
            <x-ui.icon name="eye" class="fs-4 me-1" />
            Lihat
        </x-ui.button>
    @else
        <x-ui.status-badge variant="secondary">Belum Ada</x-ui.status-badge>
    @endif
</div>
