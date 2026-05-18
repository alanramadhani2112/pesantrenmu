@props(['active'])

@php
$classes = ($active ?? false)
            ? 'nav-link active ps-3 pe-4 py-2 fw-semibold text-primary bg-light-primary'
            : 'nav-link ps-3 pe-4 py-2 fw-medium text-gray-600';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
