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
    $isChild = $icon === 'none';
    $classes = 'spm-sidebar-link' . ($active ? ' active' : '') . ($isChild ? ' spm-sidebar-link-child' : '') . ' menu-link';
@endphp

<div class="menu-item">
    <a {{ $attributes->merge(['class' => $classes]) }}>
        @if($isChild)
            <span class="menu-bullet">
                <span class="bullet bullet-dot"></span>
            </span>
        @else
        <span class="spm-sidebar-icon menu-icon">
            <x-ui.icon :name="$iconName" class="fs-2" />
        </span>
        @endif

        <span class="spm-sidebar-title menu-title truncate">{{ $slot }}</span>
    </a>
</div>
