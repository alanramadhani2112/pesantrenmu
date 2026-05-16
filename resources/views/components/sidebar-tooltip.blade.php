@props(['tooltip'])

<div x-data="{ show: false, timeout: null }"
     @mouseenter="timeout = setTimeout(() => show = true, 500)"
     @mouseleave="clearTimeout(timeout); show = false"
     class="position-relative">
    {{ $slot }}
    <div x-show="show"
         x-transition.opacity.duration.200ms
         x-cloak
         class="spm-sidebar-tooltip position-absolute bg-dark text-white rounded shadow px-3 py-2 fs-7"
         style="left: 100%; top: 50%; transform: translateY(-50%); margin-left: 0.5rem; z-index: 1050; white-space: nowrap;">
        {{ $tooltip }}
    </div>
</div>
