@props([
    'field' => null,
    'sortField' => null,
    'sortAsc' => false,
    'align' => 'start',
    'minWidth' => true,
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
        <button type="button" wire:click="sortBy('{{ $field }}')" class="btn btn-sm btn-flush fw-bold text-gray-500 text-hover-primary text-uppercase p-0">
            <span>{{ $slot }}</span>
            <x-ui.icon :name="$icon" class="fs-7 ms-1 {{ $isActive ? 'text-primary' : 'text-gray-400' }}" />
        </button>
    @else
        <span>{{ $slot }}</span>
    @endif
</th>
