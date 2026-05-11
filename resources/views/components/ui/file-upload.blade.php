@props([
    'model' => 'file',
    'id' => 'file',
    'accept' => null,
    'file' => null,
    'placeholder' => 'Belum ada file',
    'hint' => null,
])

@php
    $fileName = $file && method_exists($file, 'getClientOriginalName')
        ? $file->getClientOriginalName()
        : $placeholder;
@endphp

<div data-ui-file-upload="metronic" {{ $attributes->merge(['class' => 'spm-file-upload']) }}>
    <input
        type="file"
        wire:model="{{ $model }}"
        id="{{ $id }}"
        @if($accept) accept="{{ $accept }}" @endif
        class="d-none"
    >

    <label for="{{ $id }}" class="d-flex align-items-center gap-3 p-4 rounded border border-dashed border-gray-300 bg-light cursor-pointer">
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
    </label>

    {{ $slot }}
</div>
