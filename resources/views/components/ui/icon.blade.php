@props([
    'name',
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
@endphp

<i {{ $attributes->merge(['class' => "ki-solid ki-{$iconName}"]) }}></i>
