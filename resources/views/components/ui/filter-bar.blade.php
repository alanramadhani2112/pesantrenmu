@props([
    'class' => '',
])

<div data-ui-filter-bar="metronic" {{ $attributes->merge(['class' => trim('card card-flush mb-5 ' . $class)]) }}>
    <div class="card-body py-5">
        <div class="d-flex flex-wrap align-items-center gap-3">
            {{ $slot }}
        </div>
    </div>
</div>
