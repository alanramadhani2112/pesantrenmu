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
        <x-ui.button type="button" variant="light" unstyled wire:click="sortBy('{{ $field }}')" class="btn-flush fw-semibold text-gray-700 text-hover-primary p-0 d-inline-flex align-items-center gap-1">
            <span>{{ $slot }}</span>
            <x-ui.icon :name="$icon" class="fs-7 ms-1 {{ $isActive ? 'text-primary' : 'text-gray-400' }}" />
        </x-ui.button>
    @else
        <span>{{ $slot }}</span>
    @endif
</th>
