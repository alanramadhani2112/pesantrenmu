@if($showFinalDecision || $akreditasi->status == AkreditasiStateMachine::STATUS_PASCA_VISITASI)
    <x-ui.section-card title="Keputusan Akhir" subtitle="Approve atau reject hasil akreditasi.">
        <div class="p-6">
            <div class="d-flex gap-4">
                {{-- Approve --}}
                @if($akreditasi->status == AkreditasiStateMachine::STATUS_PASCA_VISITASI)
                    <x-ui.button type="button" variant="success" size="lg"
                        @click="$dispatch('open-modal', 'approve-berkas-modal')">
                        <x-ui.icon name="check-circle" class="fs-4 me-2" />
                        Approve Akreditasi
                    </x-ui.button>

                    <x-ui.button type="button" variant="danger" size="lg"
                        @click="$dispatch('open-modal', 'reject-berkas-modal')">
                        <x-ui.icon name="x-circle" class="fs-4 me-2" />
                        Reject Akreditasi
                    </x-ui.button>
                @endif
            </div>
        </div>
    </x-ui.section-card>
@endif
