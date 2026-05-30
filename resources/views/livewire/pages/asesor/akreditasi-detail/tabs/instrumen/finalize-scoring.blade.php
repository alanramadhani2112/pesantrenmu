                    {{-- Finalisasi Penilaian (Ketua Kelompok, status Penilaian Pasca Visitasi) --}}
                    @if((int)$akreditasi->status === \App\StateMachine\AkreditasiStateMachine::STATUS_PASCA_VISITASI && $asesorTipe == 1)
                        <div class="mt-6">
                            <x-ui.section-card title="Finalisasi Penilaian">
                                <div class="p-6">
                                    <p class="text-gray-700 mb-4">Finalisasi Nilai Ketua, Nilai Anggota, Nilai Kelompok, dan catatan. Setelah difinalisasi, akreditasi akan masuk ke tahap Validasi Admin.</p>
                                    <x-ui.button type="button" wire:click="finalizeScoring" wire:loading.attr="disabled" variant="primary">
                                        <span wire:loading.remove wire:target="finalizeScoring">Finalisasi Penilaian</span>
                                        <span wire:loading wire:target="finalizeScoring">Memproses...</span>
                                    </x-ui.button>
                                </div>
                            </x-ui.section-card>
                        </div>
                    @endif
