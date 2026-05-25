@props(['messages'])

@if ($messages)
    <x-ui.alert variant="danger" icon="information-5" {{ $attributes->merge(['class' => 'mt-2 mb-0 py-3 px-4']) }}>
        <ul class="mb-0 ps-4">
            @foreach ((array) $messages as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </x-ui.alert>
@endif
