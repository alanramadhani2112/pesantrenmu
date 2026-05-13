@props([
    'title',
    'icon' => 'menu',
    'open' => false,
])

<div
    data-kt-menu-trigger="click"
    class="menu-item menu-accordion"
    x-data="{ open: @js((bool) $open) }"
    x-bind:class="{ 'show here': open }"
>
    <x-ui.button
        type="button"
        variant="link"
        class="spm-sidebar-link spm-sidebar-group-toggle menu-link w-100 border-0 bg-transparent"
        x-on:click="open = ! open"
        x-bind:aria-expanded="open.toString()"
    >
        <span class="spm-sidebar-icon spm-sidebar-group-icon menu-icon">
            <x-ui.icon :name="$icon" class="fs-2" />
        </span>
        <span class="spm-sidebar-title menu-title">{{ $title }}</span>
        <span class="menu-arrow"></span>
    </x-ui.button>

    <div
        class="menu-sub menu-sub-accordion"
        x-show="open"
        x-transition.opacity.duration.150ms
        x-cloak
    >
        {{ $slot }}
    </div>
</div>
