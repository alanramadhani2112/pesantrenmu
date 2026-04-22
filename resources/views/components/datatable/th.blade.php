@props(['field', 'sortField', 'sortAsc'])

<th {{ $attributes->merge(['class' => 'py-3 px-4 text-left text-[11px] font-bold text-gray-400 uppercase tracking-widest cursor-pointer group hover:text-gray-900 transition-all']) }}
    wire:click="sortBy('{{ $field }}')">
    <div class="flex items-center gap-2">
        <span>{{ $slot }}</span>
        <div class="flex items-center opacity-0 group-hover:opacity-100 transition-opacity {{ $sortField === $field ? 'opacity-100' : '' }}">
            @if ($sortField === $field)
            @if ($sortAsc)
            <svg class="w-3 h-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7" />
            </svg>
            @else
            <svg class="w-3 h-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
            </svg>
            @endif
            @else
            <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
            </svg>
            @endif
        </div>
    </div>
</th>