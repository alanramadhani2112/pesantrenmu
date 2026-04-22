<div class="flex items-center gap-2">
    <span class="text-xs text-gray-500">Tampilkan</span>
    <select wire:model.live="perPage"
        class="text-xs border border-gray-100 rounded-lg bg-gray-50/50 py-1 pl-2 pr-8 focus:ring-1 focus:ring-green-500 focus:border-green-500 text-gray-600">
        <option value="10">10</option>
        <option value="25">25</option>
        <option value="50">50</option>
        <option value="100">100</option>
    </select>
    <span class="text-xs text-gray-500">data</span>
</div>