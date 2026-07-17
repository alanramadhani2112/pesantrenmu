@props([
    'field' => null,
    'sortField' => null,
    'sortAsc' => false,
    'align' => 'start',
    'minWidth' => true,
    'form' => null,
])

@php
    $alignClass = [
        'center' => 'text-center',
        'end' => 'text-end',
        'right' => 'text-end',
    ][$align] ?? 'text-start';

    $isActive = $field && $sortField === $field;
    $icon = $isActive ? ($sortAsc ? 'arrow-up' : 'arrow-down') : 'arrow-up-down';
    $classes = trim($alignClass . ($minWidth ? ' min-w-125px' : ''));
@endphp

<th {{ $attributes->merge(['class' => $classes]) }}>
    @if($field)
        @php
            $newAsc = ($isActive && $sortAsc) ? '0' : '1';
            $sortParams = array_merge(request()->query(), ['sortField' => $field, 'sortAsc' => $newAsc, 'page' => 1]);
            $sortUrl = $form ? '#' : ('?' . http_build_query($sortParams));
        @endphp
        <a href="{{ $sortUrl }}"
           @if($form) onclick="event.preventDefault(); var f=document.getElementById('{{ $form }}'); var si=f.querySelector('[name=sortField]'); var sa=f.querySelector('[name=sortAsc]'); var p=f.querySelector('[name=page]'); if(si) si.value='{{ $field }}'; if(sa) sa.value='{{ $newAsc }}'; if(!p){ p=document.createElement('input'); p.type='hidden'; p.name='page'; f.appendChild(p); } p.value='1'; f.submit();" @endif
           class="fw-semibold text-gray-700 text-hover-primary d-inline-flex align-items-center gap-1 text-decoration-none">
            <span>{{ $slot }}</span>
            <x-ui.icon :name="$icon" class="fs-7 ms-1 {{ $isActive ? 'text-primary' : 'text-gray-400' }}" />
        </a>
    @else
        <span>{{ $slot }}</span>
    @endif
</th>
