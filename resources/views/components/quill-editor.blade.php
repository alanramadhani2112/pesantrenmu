@props(['value' => '', 'placeholder' => '', 'disabled' => false])

<div
    x-data="quillEditor()"
    x-modelable="content"
    data-placeholder="{{ $placeholder }}"
    data-read-only="{{ $disabled ? 'true' : 'false' }}"
    x-on:quill-input.window="if($event.detail.id === '{{ $attributes->get('id') }}') quill.root.innerHTML = $event.detail.value"
    {{ $attributes->merge(['class' => 'quill-editor-container']) }}
>
    <div x-ref="quillEditor" class="bg-white rounded-b-md min-h-[150px]"></div>
    <style>
        .ql-toolbar.ql-snow {
            border-top-left-radius: 0.375rem;
            border-top-right-radius: 0.375rem;
            border-color: #d1d5db;
            background-color: #f9fafb;
        }
        .ql-container.ql-snow {
            border-bottom-left-radius: 0.375rem;
            border-bottom-right-radius: 0.375rem;
            border-color: #d1d5db;
            font-family: inherit;
        }
        .ql-editor {
            min-height: 150px;
            font-size: 0.875rem;
        }
        .ql-editor.ql-blank::before {
            color: #9ca3af;
            font-style: normal;
        }
    </style>
</div>
