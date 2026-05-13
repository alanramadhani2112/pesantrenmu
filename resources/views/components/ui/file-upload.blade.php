@props([
    'model' => null,
    'id' => 'file',
    'accept' => null,
    'file' => null,
    'placeholder' => 'Belum ada file',
    'hint' => null,
    'disabled' => false,
    'labelClass' => 'd-flex align-items-center gap-3 p-4 rounded border border-dashed border-gray-300 bg-light cursor-pointer',
    'labelStyle' => null,
    'changeAction' => null,
])

@php
    $fileName = $file && method_exists($file, 'getClientOriginalName')
        ? $file->getClientOriginalName()
        : $placeholder;
    $hasCustomTrigger = trim((string) $slot) !== '';
@endphp

<div data-ui-file-upload="metronic" {{ $attributes->merge(['class' => 'spm-file-upload' . ($disabled ? ' opacity-50 pe-none' : '')]) }}>
    <input
        type="file"
        id="{{ $id }}"
        @if($accept) accept="{{ $accept }}" @endif
        @if($model) wire:model="{{ $model }}" @endif
        @if($changeAction) x-on:change="{{ $changeAction }}" @endif
        @disabled($disabled)
        class="d-none"
    >

    <label for="{{ $id }}" class="{{ $labelClass }}" @if($labelStyle) style="{{ $labelStyle }}" @endif>
        @if($hasCustomTrigger)
            {{ $slot }}
        @else
            <span class="symbol symbol-40px">
                <span class="symbol-label bg-white text-primary">
                    <x-ui.icon name="arrow-up" class="fs-2" />
                </span>
            </span>

            <span class="d-flex flex-column min-w-0">
                <span class="fw-bold text-gray-700">{{ $fileName }}</span>
                @if($hint)
                    <span class="text-muted fw-semibold fs-8">{{ $hint }}</span>
                @endif
            </span>
        @endif
    </label>
</div>
