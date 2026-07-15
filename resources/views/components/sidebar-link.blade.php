@props(['active', 'icon' => 'home', 'badgeCount' => null, 'tooltip' => null, 'badgeText' => null, 'disabled' => false])

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
        'notification-bing' => 'notification',
        'clipboard-check' => 'check-circle',
        'document-up' => 'arrow-up',
        'abstract-26' => 'category',
        'file-down' => 'file-down',
        'information' => 'information',
        'eye' => 'eye',
        'pencil' => 'pencil',
    ];

    $iconName = $iconMap[$icon] ?? $icon;
    $isChild = $icon === 'none';
    $classes = 'spm-sidebar-link' . ($active ? ' active' : '') . ($isChild ? ' spm-sidebar-link-child' : '') . ($disabled ? ' spm-sidebar-link-disabled' : '') . ' menu-link';
    $disabledAttrs = $disabled ? 'aria-disabled="true" tabindex="-1" style="opacity:.6;cursor:not-allowed;pointer-events:none;"' : '';
@endphp

@if($tooltip)
<x-sidebar-tooltip :tooltip="$tooltip">
<div class="menu-item">
    <a {{ $attributes->merge(['class' => $classes]) }} {!! $disabledAttrs !!} @if(!$disabled) data-kt-drawer-dismiss="true" @endif>
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

        {{-- Badge count --}}
        @if($badgeCount && $badgeCount > 0)
            <span class="menu-badge">
                <x-ui.badge variant="primary" :light="false" class="badge-sm badge-circle">{{ $badgeCount }}</x-ui.badge>
            </span>
        @endif

        {{-- Static badge text (e.g. "Soon") --}}
        @if($badgeText)
            <span class="menu-badge">
                <x-ui.badge variant="warning" class="badge-sm">{{ $badgeText }}</x-ui.badge>
            </span>
        @endif
    </a>
</div>
</x-sidebar-tooltip>
@else
<div class="menu-item">
    <a {{ $attributes->merge(['class' => $classes]) }} {!! $disabledAttrs !!} @if(!$disabled) data-kt-drawer-dismiss="true" @endif>
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

        {{-- Badge count --}}
        @if($badgeCount && $badgeCount > 0)
            <span class="menu-badge">
                <x-ui.badge variant="primary" :light="false" class="badge-sm badge-circle">{{ $badgeCount }}</x-ui.badge>
            </span>
        @endif

        {{-- Static badge text (e.g. "Soon") --}}
        @if($badgeText)
            <span class="menu-badge">
                <x-ui.badge variant="warning" class="badge-sm">{{ $badgeText }}</x-ui.badge>
            </span>
        @endif
    </a>
</div>
@endif
