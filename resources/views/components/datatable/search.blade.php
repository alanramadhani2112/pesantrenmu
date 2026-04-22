@props(['placeholder' => 'Search...'])

<div class="relative">
    <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ $placeholder }}"
        class="pl-9 pr-4 py-2 text-xs border border-gray-100 rounded-lg focus:ring-1 focus:ring-green-500 focus:border-green-500 w-48 sm:w-64 bg-gray-50/50 placeholder-gray-400 transition-all">
    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor"
        viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
    </svg>
</div>