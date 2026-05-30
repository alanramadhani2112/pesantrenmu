@props([
    'name',
    'paths' => null,
])

@php
    $aliases = [
        'building' => 'category',
        'camera' => 'file-up',
        'cloud-upload' => 'file-up',
        'layers' => 'data',
        'warning-2' => 'information-5',
        'home-2' => 'home',
        'verify' => 'shield-tick',
        'award' => 'medal-star',
        'profile-circle' => 'profile-user',
    ];

    $iconName = $aliases[$name] ?? $name;

    // Icons with 0 duotone paths use ki-solid (single-glyph icons)
    $solidIcons = ['down', 'left', 'right', 'up', 'home', 'plus', 'minus', 'check'];
    $useSolid = in_array($iconName, $solidIcons);

    $paths ??= [
        'abstract-26' => 2,
        'arrow-right' => 2,
        'arrows-circle' => 2,
        'timer' => 3,
        'people' => 5,
        'profile-user' => 4,
        'profile-circle' => 3,
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
        'eye' => 3,
        'eye-slash' => 4,
        'trash' => 5,
        'category' => 4,
        'teacher' => 2,
        'shield' => 2,
        'shield-tick' => 2,
        'shield-cross' => 3,
        'security-user' => 2,
        'files-tablet' => 2,
        'menu' => 4,
        'setting-2' => 2,
        'data' => 5,
        'home-2' => 2,
        'pencil' => 2,
        'burger-menu' => 4,
        'exit-right' => 2,
        'entrance-right' => 2,
        'messages' => 5,
        'calendar' => 2,
        'calendar-tick' => 6,
        'phone' => 2,
        'medal-star' => 4,
        'award' => 3,
        'notification' => 3,
        'notification-bing' => 3,
        'file-down' => 2,
        'file-up' => 2,
        'file' => 2,
        'loading' => 2,
        'minus-circle' => 2,
        'rocket' => 2,
        'cross' => 2,
        'disconnect' => 5,
        'lock' => 3,
        'lock-2' => 5,
        'sms' => 2,
        'time' => 2,
    ][$iconName] ?? ($useSolid ? 0 : 6);
@endphp

@if($useSolid)
<i {{ $attributes->merge(['class' => "ki-solid ki-{$iconName}"]) }}></i>
@else
<i {{ $attributes->merge(['class' => "ki-duotone ki-{$iconName}"]) }}>
    @for($index = 1; $index <= $paths; $index++)
        <span class="path{{ $index }}"></span>
    @endfor
</i>
@endif
