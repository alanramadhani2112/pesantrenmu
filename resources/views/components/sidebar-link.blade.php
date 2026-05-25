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
        'notification-bing' => 'notification',
        'clipboard-check' => 'check-circle',
        'document-up' => 'arrow-up',
        'abstract-26' => 'category',
        'file-down' => 'file-down',
        'information' => 'information',
        'eye' => 'eye',
        'pencil' => 'pencil',
    ];

    $iconName = $iconMap[$icon] ?? 'menu';
    $isChild = $icon === 'none';
    $classes = 'spm-sidebar-link' . ($active ? ' active' : '') . ($isChild ? ' spm-sidebar-link-child' : '') . ($disabled ? ' spm-sidebar-link-disabled' : '') . ' menu-link';
    $disabledAttrs = $disabled ? 'aria-disabled="true" tabindex="-1" style="opacity:.6;cursor:not-allowed;pointer-events:none;"' : '';
    $progressMeta = match ($progressStatus) {
        'complete' => ['label' => 'Lengkap', 'class' => 'spm-sidebar-progress-badge--complete', 'aria' => 'Kesiapan data lengkap'],
        'incomplete' => ['label' => 'Draft', 'class' => 'spm-sidebar-progress-badge--incomplete', 'aria' => 'Kesiapan data masih draft'],
        'not_started' => ['label' => 'Belum', 'class' => 'spm-sidebar-progress-badge--empty', 'aria' => 'Data belum diisi'],
        default => null,
    };
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
        @if($progressMeta)
            <span class="menu-badge">
                <span
                    class="spm-sidebar-progress-badge {{ $progressMeta['class'] }}"
                    aria-label="{{ $progressMeta['aria'] }}"
                    title="{{ $progressMeta['aria'] }}"
                >
                    <span class="spm-sidebar-progress-dot" aria-hidden="true"></span>
                    <span class="visually-hidden">{{ $progressMeta['aria'] }}</span>
                </span>
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
        @if($progressMeta)
            <span class="menu-badge">
                <span
                    class="spm-sidebar-progress-badge {{ $progressMeta['class'] }}"
                    aria-label="{{ $progressMeta['aria'] }}"
                    title="{{ $progressMeta['aria'] }}"
                >
                    <span class="spm-sidebar-progress-dot" aria-hidden="true"></span>
                    <span class="visually-hidden">{{ $progressMeta['aria'] }}</span>
                </span>
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
