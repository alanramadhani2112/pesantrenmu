@props([
    'items' => [],
])

@if(count($items) >= 1)
<ol class="breadcrumb breadcrumb-dot text-muted fs-7 fw-semibold mb-0 spm-breadcrumb" data-ui-breadcrumb="metronic">
    @foreach($items as $index => $item)
        @php $isLast = $index === count($items) - 1; @endphp
        <li class="breadcrumb-item {{ $isLast ? 'text-gray-700' : '' }}">
            @if(!$isLast && !empty($item['url']))
                <a href="{{ $item['url'] }}" class="text-muted text-hover-primary">{{ $item['label'] }}</a>
            @else
                {{ $item['label'] }}
            @endif
        </li>
    @endforeach
</ol>
@endif
