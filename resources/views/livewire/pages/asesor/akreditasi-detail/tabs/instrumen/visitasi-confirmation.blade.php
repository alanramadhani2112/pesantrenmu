                    {{-- Konfirmasi Visitasi Selesai (Ketua Kelompok, status Visitasi) --}}
                    @if($this->canConfirmVisitasi())
                        <div class="mt-6">
                            <x-ui.section-card title="Konfirmasi Visitasi">
                                <div class="p-6">
                                    <p class="text-gray-700 mb-4">Konfirmasi bahwa visitasi telah selesai dilaksanakan. Akreditasi akan masuk ke tahap Penilaian Pasca Visitasi.</p>
                                    <x-ui.button type="button" wire:click="confirmVisitasiSelesai" wire:loading.attr="disabled" variant="success">
                                        <span wire:loading.remove wire:target="confirmVisitasiSelesai">Konfirmasi Visitasi Selesai</span>
                                        <span wire:loading wire:target="confirmVisitasiSelesai">Memproses...</span>
                                    </x-ui.button>
                                </div>
                            </x-ui.section-card>
                        </div>
                    @endif
