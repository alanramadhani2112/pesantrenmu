@props(['status'])

@if ($status)
    <x-ui.alert variant="success" {{ $attributes->merge(['class' => 'mb-6']) }}>
        {{ $status }}
    </x-ui.alert>
@endif
