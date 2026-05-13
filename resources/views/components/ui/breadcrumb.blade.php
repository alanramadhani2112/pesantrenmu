@props([
    'items' => [],
])

@if($items !== [])
    <ul data-ui-breadcrumb="metronic" {{ $attributes->merge(['class' => 'breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-0 spm-breadcrumb']) }}>
        @foreach($items as $index => $item)
            @php
                $isLast = $loop->last;
                $label = $item['label'] ?? '';
                $url = $item['url'] ?? null;
            @endphp

            <li class="breadcrumb-item {{ $isLast ? 'text-gray-700 fw-semibold' : 'text-muted' }}">
                @if($url && ! $isLast)
                    <a href="{{ $url }}" class="text-muted text-hover-primary">{{ $label }}</a>
                @else
                    <span>{{ $label }}</span>
                @endif
            </li>

            @unless($isLast)
                <li class="breadcrumb-item">
                    <span class="bullet bg-gray-400"></span>
                </li>
            @endunless
        @endforeach
    </ul>
@endif
