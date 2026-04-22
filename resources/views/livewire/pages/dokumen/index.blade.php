<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {
    public $doc = 'all';

    public function mount($doc = 'all')
    {
        $this->doc = $doc;
    }

    public function getDocumentsProperty()
    {
        $documentService = app(\App\Services\DocumentService::class);
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $role = $user->isAsesor() ? 'asesor' : ($user->isPesantren() ? 'pesantren' : null);
        return $documentService->getActiveDocuments($role, $this->doc);
    }
}; ?>

<div class="py-12 bg-slate-50/50 min-h-screen">
    <x-slot name="header">
        @if($this->doc == 'iapm') IAPM @elseif($this->doc == 'kartu_kendali') Kartu Kendali @elseif($this->doc == 'visitasi') Visitasi @else Daftar Dokumen @endif
    </x-slot>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white rounded-[3rem] shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-8 md:p-12 text-gray-900">
                <div class="mb-10">
                    <h2 class="text-3xl font-black text-slate-800 tracking-tight">@if($this->doc == 'iapm') IAPM @elseif($this->doc == 'kartu_kendali') Kartu Kendali @elseif($this->doc == 'visitasi') Visitasi @else Daftar Dokumen @endif</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-8">
                    @forelse ($this->documents as $doc)
                    <div class="group bg-white border border-slate-100 rounded-[2.5rem] p-8 shadow-sm hover:shadow-xl hover:shadow-blue-500/5 transition-all duration-500 flex flex-col h-full relative overflow-hidden">
                        <!-- File Type Badge -->
                        <div class="absolute top-8 right-8">
                            <span class="text-[10px] font-black uppercase tracking-widest text-blue-600 bg-blue-50/50 px-4 py-2 rounded-full border border-blue-100/50">
                                {{ strtoupper(pathinfo($doc->file_path, PATHINFO_EXTENSION)) }}
                            </span>
                        </div>

                        <!-- Icon -->
                        <div class="mb-8">
                            <div class="h-14 w-14 bg-blue-600 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-blue-200 group-hover:scale-110 transition-transform duration-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                        </div>

                        <!-- Title -->
                        <h3 class="text-xl font-bold text-slate-800 mb-10 leading-[1.4] flex-grow pr-16 group-hover:text-blue-600 transition-colors">
                            {{ $doc->title }}
                        </h3>

                        <!-- Footer -->
                        <div class="mt-auto pt-8 border-t border-slate-50 flex items-center justify-between">
                            <div class="flex flex-col">
                                <span class="text-[9px] font-black text-slate-300 uppercase tracking-[0.2em] mb-1.5 leading-none">DIUNGGAH PADA</span>
                                <span class="text-[13px] font-bold text-slate-500">{{ $doc->created_at->translatedFormat('d M Y') }}</span>
                            </div>

                            <a href="{{ Storage::url($doc->file_path) }}" target="_blank" class="inline-flex items-center gap-3 bg-[#1e293b] hover:bg-black text-white px-6 py-3 rounded-xl text-[11px] font-black uppercase tracking-widest transition-all shadow-md hover:shadow-lg active:scale-95 group/btn">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transform group-hover/btn:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Buka Berkas
                            </a>
                        </div>
                    </div>
                    @empty
                    <div class="col-span-full py-24 text-center bg-slate-50 rounded-[3rem] border border-slate-100">
                        <div class="mx-auto h-20 w-20 bg-white rounded-3xl shadow-sm flex items-center justify-center text-slate-300 mb-6">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800">Belum Ada Dokumen</h3>
                        <p class="text-[15px] font-medium text-slate-400 mt-2">Admin belum membagikan dokumen apa pun kepada Anda.</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>