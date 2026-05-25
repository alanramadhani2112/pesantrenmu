@props([
    'title',
    'items' => [],
    'icon' => 'information',
    'variant' => 'primary',
    'dismissKey' => null,
])

@php
    $allowed = ['primary', 'success', 'warning', 'info'];
    $variant = in_array($variant, $allowed, true) ? $variant : 'primary';
    $storageKey = $dismissKey ? "spm_help_dismissed_{$dismissKey}" : null;
@endphp

<x-ui.alert
    :variant="$variant"
    :icon="$icon"
    :title="$title"
    data-ui-page-help="metronic"
    x-data="{
        visible: true,
        init() {
            @if($storageKey)
            if (localStorage.getItem('{{ $storageKey }}') === '1') {
                this.visible = false;
            }
            @endif
        },
        dismiss() {
            this.visible = false;
            @if($storageKey)
            localStorage.setItem('{{ $storageKey }}', '1');
            @endif
        }
    }"
    x-show="visible"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    aria-label="Panduan halaman"
    {{ $attributes->merge(['class' => 'mb-6']) }}
>
    @if(!empty($items))
        <ul class="mb-0 ps-4 d-flex flex-column gap-1">
            @foreach($items as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    @endif

    @if(trim($slot))
        <div class="{{ !empty($items) ? 'mt-1' : '' }}">{{ $slot }}</div>
    @endif

    <x-slot:actions>
        <x-ui.button
            type="button"
            variant="light"
            size="sm"
            class="btn-icon btn-active-color-{{ $variant }}"
            x-on:click="dismiss()"
            aria-label="Tutup panduan"
        >
            <x-ui.icon name="cross-circle" class="fs-3" />
        </x-ui.button>
    </x-slot:actions>
</x-ui.alert>
