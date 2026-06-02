@props([
    'label' => 'Aksi',
    'menuId' => null,
])

@php
    $resolvedMenuId = $menuId ?: 'menu-' . md5((string) $label . spl_object_id($attributes));
@endphp

<div
    data-ui-action-menu="metronic"
    x-data="spmActionMenu(@js($resolvedMenuId))"
    x-on:spm:action-menu-open.window="if ($event.detail?.id !== menuId) close()"
    x-on:keydown.escape.window="close()"
    x-on:resize.window="if (isOpen) updatePosition()"
    x-on:scroll.window="if (isOpen) updatePosition()"
    x-on:livewire:navigating.window="close()"
    x-on:click.outside="close()"
    class="spm-action-menu d-inline-block position-relative"
>
    <x-ui.button
        type="button"
        variant="light"
        size="sm"
        class="btn-active-light-primary spm-action-menu-trigger"
        x-ref="trigger"
        x-on:click.stop="toggle()"
        x-bind:aria-expanded="isOpen.toString()"
        aria-controls="{{ $resolvedMenuId }}"
        aria-haspopup="true"
    >
        <span class="spm-action-menu-trigger-inner">
            <x-ui.icon name="setting-2" class="spm-action-menu-trigger-icon" />
            <span class="spm-action-menu-trigger-label">{{ $label }}</span>
            <x-ui.icon name="down" class="spm-action-menu-trigger-caret" />
        </span>
    </x-ui.button>

    <div
        id="{{ $resolvedMenuId }}"
        x-cloak
        x-ref="menu"
        x-show="isOpen"
        x-bind:style="placementStyle"
        x-transition.opacity.duration.80ms
        x-on:click="if ($event.target.closest('a, button, [role=menuitem]')) close()"
        class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-700 menu-state-bg-light-primary fw-semibold py-3 fs-7 w-200px show spm-action-menu-dropdown"
        data-ui-action-menu-dropdown="metronic"
        role="menu"
    >
        {{ $slot }}
    </div>
</div>
