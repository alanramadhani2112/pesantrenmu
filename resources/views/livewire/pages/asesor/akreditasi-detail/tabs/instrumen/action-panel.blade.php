                    <div class="spm-action-panel d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4">
                        <div>
                            <h3 class="spm-card-title mb-1">Evaluasi Visitasi</h3>
                            <div class="text-muted fw-semibold fs-7">Pastikan semua data sudah lengkap sebelum verifikasi final.</div>
                        </div>
                        @if ((int) $akreditasi->status === \App\StateMachine\AkreditasiStateMachine::STATUS_PASCA_VISITASI)
                            <div class="d-flex flex-wrap justify-content-end gap-2">
                                <x-ui.button type="button" @click="confirmSaveInstrumen($wire)" wire:loading.attr="disabled" variant="primary">
                                    {{ $asesorTipe == 1 ? 'Simpan Nilai Ketua/Kelompok' : 'Simpan Nilai Anggota' }}
                                </x-ui.button>
                            </div>
                        @endif
                    </div>
