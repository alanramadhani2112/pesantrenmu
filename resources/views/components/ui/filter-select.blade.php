@props([
    'name' => null,
    'value' => null,
    'placeholder' => null,
    'options' => [],
    'size' => 'md',
    'form' => null,
])

@php
    $value ??= $name ? request($name, '') : '';
    $sizes = [
        'sm' => 'form-select-sm',
        'md' => '',
        'lg' => 'form-select-lg',
    ];

    $classes = trim('form-select form-select-solid spm-filter-select ' . ($sizes[$size] ?? ''));
@endphp

<select
    data-ui-filter-select="metronic"
    @if($name) name="{{ $name }}" @endif
    @if($form) form="{{ $form }}" @endif
    onchange="this.form ? this.form.submit() : this.closest('form')?.submit()"
    {{ $attributes->merge(['class' => $classes]) }}
>
    @if($placeholder)
        <option value="">{{ $placeholder }}</option>
    @endif

    @foreach($options as $optValue => $label)
        <option value="{{ $optValue }}" @selected((string) $value === (string) $optValue)>{{ $label }}</option>
    @endforeach

    {{ $slot }}
</select>
