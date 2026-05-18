@props([
    'name',
    'paths' => null,
])

@php
    $paths ??= [
        'timer' => 3,
        'people' => 5,
        'profile-user' => 4,
        'chart-line' => 2,
        'chart-line-up' => 2,
        'chart-pie-simple' => 2,
        'document' => 2,
        'geolocation' => 2,
        'check-circle' => 2,
        'cross-circle' => 2,
        'information' => 3,
        'information-2' => 3,
        'information-5' => 3,
        'magnifier' => 2,
        'arrow-up' => 2,
        'arrow-down' => 2,
        'arrow-up-down' => 2,
        'arrow-left' => 2,
        'down' => 0,
        'eye' => 3,
        'trash' => 5,
        'category' => 4,
        'teacher' => 2,
        'shield-tick' => 2,
        'security-user' => 2,
        'files-tablet' => 2,
        'menu' => 4,
        'setting-2' => 2,
        'data' => 5,
        'plus' => 0,
        'pencil' => 2,
        'burger-menu' => 4,
        'exit-right' => 2,
        'messages' => 5,
        'calendar-tick' => 6,
        'phone' => 2,
        'medal-star' => 4,
        'notification' => 3,
        'file-down' => 2,
        'loading' => 2,
        'minus-circle' => 2,
        'rocket' => 2,
    ][$name] ?? 2;
@endphp

<i {{ $attributes->merge(['class' => "ki-duotone ki-{$name}"]) }}>
    @for($index = 1; $index <= $paths; $index++)
        <span class="path{{ $index }}"></span>
    @endfor
</i>
