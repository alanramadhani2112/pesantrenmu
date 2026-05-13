@props([
    'label' => 'Aksi',
    'menuId' => null,
])

@php
    $resolvedMenuId = $menuId ?: 'menu-' . md5((string) $label . spl_object_id($attributes));
@endphp

<div
    data-ui-action-menu="metronic"
    x-data="{ open: false }"
    x-on:keydown.escape.window="open = false"
    x-on:click.outside="open = false"
    class="d-inline-block position-relative"
    data-kt-menu="true"
    data-kt-menu-placement="bottom-end"
    data-kt-menu-attach="parent"
>
    <x-ui.button
        type="button"
        variant="light"
        size="sm"
        class="btn-active-light-primary"
        x-on:click.stop="open = ! open"
        x-bind:aria-expanded="open.toString()"
        aria-controls="{{ $resolvedMenuId }}"
        aria-haspopup="true"
    >
        {{ $label }}
        <x-ui.icon name="down" class="fs-7 ms-1" />
    </x-ui.button>

    <div
        id="{{ $resolvedMenuId }}"
        x-cloak
        x-show="open"
        x-transition.opacity.duration.80ms
        x-on:click="if ($event.target.closest('a, button')) open = false"
        class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-700 menu-state-bg-light-primary fw-semibold py-3 fs-7 w-200px show position-absolute end-0 mt-2"
        data-kt-menu="true"
        role="menu"
        style="z-index: 1080;"
    >
        {{ $slot }}
    </div>
</div>
