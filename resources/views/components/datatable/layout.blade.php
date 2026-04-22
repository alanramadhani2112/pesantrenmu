@props([
'title' => '',
'records',
])

<div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100">
    <div class="p-6 text-gray-900">
        <!-- Header Actions -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <h2 class="text-xl font-extrabold text-[#111827]">{{ $title }}</h2>

            <div class="flex flex-wrap items-center gap-2">
                {{ $filters }}
            </div>
        </div>

        <!-- Datatable Info & Per Page -->
        <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-4">
            <x-datatable.per-page />
        </div>

        <!-- Table Content -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead>
                    <tr class="bg-gray-50/30">
                        {{ $thead }}
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    {{ $tbody }}
                </tbody>
            </table>
        </div>

        <!-- Footer / Pagination -->
        <div class="mt-8 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="text-xs text-gray-500 font-medium">
                Menampilkan {{ $records->firstItem() ?? 0 }} sampai {{ $records->lastItem() ?? 0 }} dari {{ $records->total() ?? 0 }} data
            </div>
            <div class="pagination-indonesia">
                {{ $records->links('livewire.datatable-pagination') }}
            </div>
        </div>
    </div>
</div>