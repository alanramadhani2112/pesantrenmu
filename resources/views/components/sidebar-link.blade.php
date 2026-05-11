@props(['active', 'icon' => 'home'])

@php
    $active = (bool) ($active ?? false);

    $iconMap = [
        'home' => 'category',
        'grid' => 'category',
        'hat' => 'teacher',
        'paper' => 'document',
        'document' => 'document',
        'users' => 'people',
        'shield' => 'shield-tick',
        'shield-lock' => 'security-user',
        'document-stack' => 'files-tablet',
        'user-circle' => 'profile-user',
    ];

    $iconName = $iconMap[$icon] ?? 'menu';
    $classes = 'spm-sidebar-link' . ($active ? ' active' : '') . ($icon === 'none' ? ' spm-sidebar-link-child' : '');
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} wire:navigate>
    @if($icon !== 'none')
        <span class="spm-sidebar-icon">
            <x-ui.icon :name="$iconName" class="fs-2" />
        </span>
    @endif

    <span class="spm-sidebar-title truncate">{{ $slot }}</span>
</a>
