@props(['active', 'icon' => 'home', 'progressStatus' => null, 'badgeCount' => null, 'tooltip' => null, 'badgeText' => null, 'disabled' => false])

@php
    $active = (bool) ($active ?? false);
    $disabled = (bool) ($disabled ?? false);

    $iconMap = [
        'home' => 'category',
        'grid' => 'category',
        'hat' => 'teacher',
        'paper' => 'document',
        'document' => 'document',
        'document-stack' => 'files-tablet',
        'users' => 'people',
        'user-circle' => 'profile-user',
        'profile-user' => 'profile-user',
        'shield' => 'shield-tick',
        'shield-lock' => 'security-user',
        'shield-tick' => 'shield-tick',
        'trash' => 'trash',
        'data' => 'data',
        'award' => 'medal-star',
        'messages' => 'messages',
        'check-circle' => 'check-circle',
        'chart-line-up' => 'chart-line-up',
        'calendar-tick' => 'calendar-tick',
        'phone' => 'phone',
        'medal-star' => 'medal-star',
        'chart-pie-simple' => 'chart-pie-simple',
        'notification' => 'notification',
        'file-down' => 'file-down',
        'information' => 'information',
    ];

    $iconName = $iconMap[$icon] ?? 'menu';
    $isChild = $icon === 'none';
    $classes = 'spm-sidebar-link' . ($active ? ' active' : '') . ($isChild ? ' spm-sidebar-link-child' : '') . ($disabled ? ' spm-sidebar-link-disabled' : '') . ' menu-link';
    $disabledAttrs = $disabled ? 'aria-disabled="true" tabindex="-1" style="opacity:.6;cursor:not-allowed;pointer-events:none;"' : '';
@endphp

@if($tooltip)
<x-sidebar-tooltip :tooltip="$tooltip">
<div class="menu-item">
    <a {{ $attributes->merge(['class' => $classes]) }} {!! $disabledAttrs !!} @if(!$disabled) x-on:click="$store.sidebar.open = false" @endif>
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

        {{-- Progress status indicator --}}
        @if($progressStatus === 'complete')
            <span class="menu-badge">
                <x-ui.icon name="check-circle" class="text-success fs-5" />
            </span>
        @elseif($progressStatus === 'incomplete')
            <span class="menu-badge">
                <x-ui.icon name="loading" class="text-warning fs-5" />
            </span>
        @elseif($progressStatus === 'not_started')
            <span class="menu-badge">
                <x-ui.icon name="minus-circle" class="text-gray-400 fs-5" />
            </span>
        @endif

        {{-- Badge count --}}
        @if($badgeCount && $badgeCount > 0)
            <span class="menu-badge">
                <span class="badge badge-sm badge-circle badge-primary">{{ $badgeCount }}</span>
            </span>
        @endif

        {{-- Static badge text (e.g. "Soon") --}}
        @if($badgeText)
            <span class="menu-badge">
                <span class="badge badge-sm badge-light-warning fw-semibold">{{ $badgeText }}</span>
            </span>
        @endif
    </a>
</div>
</x-sidebar-tooltip>
@else
<div class="menu-item">
    <a {{ $attributes->merge(['class' => $classes]) }} {!! $disabledAttrs !!} @if(!$disabled) x-on:click="$store.sidebar.open = false" @endif>
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

        {{-- Progress status indicator --}}
        @if($progressStatus === 'complete')
            <span class="menu-badge">
                <x-ui.icon name="check-circle" class="text-success fs-5" />
            </span>
        @elseif($progressStatus === 'incomplete')
            <span class="menu-badge">
                <x-ui.icon name="loading" class="text-warning fs-5" />
            </span>
        @elseif($progressStatus === 'not_started')
            <span class="menu-badge">
                <x-ui.icon name="minus-circle" class="text-gray-400 fs-5" />
            </span>
        @endif

        {{-- Badge count --}}
        @if($badgeCount && $badgeCount > 0)
            <span class="menu-badge">
                <span class="badge badge-sm badge-circle badge-primary">{{ $badgeCount }}</span>
            </span>
        @endif

        {{-- Static badge text (e.g. "Soon") --}}
        @if($badgeText)
            <span class="menu-badge">
                <span class="badge badge-sm badge-light-warning fw-semibold">{{ $badgeText }}</span>
            </span>
        @endif
    </a>
</div>
@endif
