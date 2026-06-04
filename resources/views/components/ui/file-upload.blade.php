@props([
    'model' => null,
    'id' => 'file',
    'accept' => null,
    'file' => null,
    'placeholder' => 'Drag & drop file di sini, atau klik untuk memilih',
    'hint' => null,
    'disabled' => false,
    'labelClass' => '',
    'labelStyle' => null,
    'changeAction' => null,
])

@php
    if (!$file && $model && isset($__data) && isset($__data[$model])) {
        $file = $__data[$model];
    }
    $hasFile = $file && is_object($file) && method_exists($file, 'getClientOriginalName');
    $hasCustomSlot = trim((string) $slot) !== '';
@endphp

<div
    data-ui-file-upload="metronic"
    {{ $attributes->merge(['class' => 'spm-file-upload' . ($disabled ? ' opacity-50 pe-none' : '')]) }}
>
    {{-- Hidden Livewire file input — Dropzone bridges files here via DataTransfer --}}
    <input
        type="file"
        id="{{ $id }}"
        @if($accept) accept="{{ $accept }}" @endif
        @if($model) wire:model="{{ $model }}" @endif
        @if($changeAction) x-on:change="{{ $changeAction }}" @endif
        @disabled($disabled)
        class="d-none"
    >

    {{-- Dropzone visual wrapper --}}
    <div
        x-data="fileDropzone({
            inputId: '{{ $id }}',
            maxMb: 5,
            allowedTypes: '{{ $accept ?: '.pdf,.jpg,.jpeg,.png,.docx,.doc' }}',
        })"
        x-init="$nextTick(() => $data.init())"
        class="dropzone dz-clickable {{ $labelClass }}"
        @if($labelStyle) style="{{ $labelStyle }}" @endif
    >
        <div class="dz-message" data-dz-message>
            <div class="d-flex flex-column align-items-center justify-content-center text-center px-4 py-8">
                {{-- Upload icon — always visible --}}
                <span class="symbol symbol-50px mb-4">
                    <span class="symbol-label {{ $hasFile ? 'bg-light-success text-success' : 'bg-light-primary text-primary' }}">
                        <x-ui.icon :name="$hasFile ? 'check-circle' : 'file-up'" class="fs-2x" />
                    </span>
                </span>

                {{-- Placeholder / file name --}}
                <span class="fw-semibold text-gray-800 fs-6">
                    {{ $hasFile ? $file->getClientOriginalName() : $placeholder }}
                </span>

                {{-- File status or hint --}}
                @if($hasFile)
                    <span class="text-success fw-semibold fs-8 mt-1">File siap diunggah</span>
                @elseif($hint)
                    <span class="text-muted fw-semibold fs-8 mt-1">{{ $hint }}</span>
                @endif

                {{-- Remove button when file selected --}}
                @if($hasFile)
                    <span class="btn btn-sm btn-light-danger mt-3 fw-semibold"
                        onclick="event.preventDefault(); event.stopPropagation(); document.getElementById('{{ $id }}').value = '';{{ $model ? " Livewire.find(document.getElementById('" . $id . "').closest('[wire\\\\:id]').getAttribute('wire:id')).set('" . $model . "', null);" : '' }}">
                        <x-ui.icon name="cross-circle" class="fs-5 me-1" />
                        Hapus file
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- Slot content (progress bar, etc.) rendered below Dropzone area --}}
    @if($hasCustomSlot)
        {{ $slot }}
    @endif
</div>
