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
    // Auto-detect file from $model if $file not explicitly provided
    if (!$file && $model && isset($__data) && isset($__data[$model])) {
        $file = $__data[$model];
    }
    $fileName = $file && is_object($file) && method_exists($file, 'getClientOriginalName')
        ? $file->getClientOriginalName()
        : $placeholder;
    $hasFile = $file && is_object($file) && method_exists($file, 'getClientOriginalName');
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
                <span class="symbol-label {{ $hasFile ? 'bg-light-success text-success' : 'bg-white text-primary' }}">
                    <x-ui.icon :name="$hasFile ? 'check-circle' : 'arrow-up'" class="fs-2" />
                </span>
            </span>

            <span class="d-flex flex-column min-w-0 flex-grow-1">
                <span class="fw-semibold text-gray-700 text-truncate">{{ $fileName }}</span>
                @if($hasFile)
                    <span class="text-success fw-semibold fs-8">File siap diunggah</span>
                @elseif($hint)
                    <span class="text-muted fw-semibold fs-8">{{ $hint }}</span>
                @endif
            </span>

            @if($hasFile)
                <span class="fs-3 text-muted ms-2 cursor-pointer"
                    onclick="event.preventDefault(); document.getElementById('{{ $id }}').value = '';{{ $model ? " Livewire.find(this.closest('[wire\\\\:id]').getAttribute('wire:id')).set('" . $model . "', null);" : '' }}">
                    <x-ui.icon name="cross-circle" class="fs-3" />
                </span>
            @endif
        @endif
    </label>
</div>
