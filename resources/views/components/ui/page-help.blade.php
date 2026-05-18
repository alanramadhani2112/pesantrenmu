@props([
    'title',
    'items' => [],   // array of strings (tip bullets)
    'icon' => 'information',
    'variant' => 'primary',  // primary | success | warning | info
    'dismissKey' => null,    // localStorage key for persistent dismiss
])

@php
    $allowed = ['primary', 'success', 'warning', 'info'];
    $variant = in_array($variant, $allowed, true) ? $variant : 'primary';
    $storageKey = $dismissKey ? "spm_help_dismissed_{$dismissKey}" : null;
@endphp

<div
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
    {{ $attributes->merge(['class' => 'alert alert-dismissible bg-light-' . $variant . ' border border-' . $variant . ' border-dashed d-flex align-items-start gap-4 p-5 mb-6']) }}
    role="note"
    aria-label="Panduan halaman"
>
    {{-- Icon --}}
    <div class="flex-shrink-0 mt-1">
        <x-ui.icon :name="$icon" class="fs-2x text-{{ $variant }}" />
    </div>

    {{-- Content --}}
    <div class="flex-grow-1">
        <div class="fw-bold text-gray-900 fs-6 mb-2">{{ $title }}</div>

        @if(!empty($items))
            <ul class="mb-0 ps-4 text-gray-700 fs-7 d-flex flex-column gap-1">
                @foreach($items as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        @endif

        @isset($slot)
            @if(trim($slot))
                <div class="text-gray-700 fs-7 mt-1">{{ $slot }}</div>
            @endif
        @endisset
    </div>

    {{-- Dismiss button --}}
    <button
        type="button"
        class="btn btn-icon btn-sm btn-active-color-{{ $variant }} ms-auto flex-shrink-0"
        x-on:click="dismiss()"
        aria-label="Tutup panduan"
    >
        <x-ui.icon name="cross-circle" class="fs-3" />
    </button>
</div>
