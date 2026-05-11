@props([
    'items' => [],
])

@if($items !== [])
    <ul data-ui-breadcrumb="metronic" {{ $attributes->merge(['class' => 'breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 spm-breadcrumb']) }}>
        @foreach($items as $index => $item)
            @php
                $isLast = $loop->last;
                $label = $item['label'] ?? '';
                $url = $item['url'] ?? null;
            @endphp

            <li class="breadcrumb-item">
                @if($url && ! $isLast)
                    <a href="{{ $url }}" wire:navigate class="text-muted text-hover-primary">{{ $label }}</a>
                @else
                    <span class="{{ $isLast ? 'text-gray-700' : 'text-muted' }}">{{ $label }}</span>
                @endif
            </li>

            @unless($isLast)
                <li class="breadcrumb-item">
                    <span class="bullet bg-gray-400 w-5px h-2px"></span>
                </li>
            @endunless
        @endforeach
    </ul>
@endif
