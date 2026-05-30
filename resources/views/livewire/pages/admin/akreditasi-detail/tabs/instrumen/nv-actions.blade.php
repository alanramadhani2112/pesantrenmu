                    @if ($akreditasi->status == 1)
                        <div class="spm-action-panel d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4">
                            <div>
                                <h3 class="spm-card-title mb-1">Nilai Verifikasi (NV)</h3>
                                <div class="text-muted fw-semibold fs-7">Simpan nilai verifikasi setelah semua butir lengkap.</div>
                            </div>
                            <div class="d-flex gap-3">
                                <x-ui.button type="button" @click="confirmSaveNV($wire)" wire:loading.attr="disabled" variant="primary">
                                    <span wire:loading.remove wire:target="saveAdminNv">Simpan NV (Draft)</span>
                                    <span wire:loading wire:target="saveAdminNv">Menyimpan...</span>
                                </x-ui.button>
                                @if(!$akreditasi->is_nv_final)
                                    <x-ui.button type="button" wire:click="finalizeAllNv" wire:loading.attr="disabled" variant="success">
                                        <span wire:loading.remove wire:target="finalizeAllNv">
                                            <x-ui.icon name="lock" class="fs-5 me-1" />
                                            Finalisasi Semua NV
                                        </span>
                                        <span wire:loading wire:target="finalizeAllNv">Memproses...</span>
                                    </x-ui.button>
                                @else
                                    <x-ui.badge variant="success" class="align-self-center">
                                        <x-ui.icon name="lock" class="fs-7 me-1" />
                                        NV Sudah Final
                                    </x-ui.badge>
                                @endif
                            </div>
                        </div>
                    @endif
