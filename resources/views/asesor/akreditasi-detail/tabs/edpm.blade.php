{{-- Tab: EDPM (Evaluasi Diri Pesantren Mu'adalah) --}}
<x-ui.section-card title="EDPM Pesantren" subtitle="Evaluasi Diri Pesantren Mu'adalah yang diisi pesantren">
    <div class="p-6">
        <x-akreditasi.edpm-review
            :komponens="$komponens"
            :evaluasis="$pesantrenEvaluasis"
            :links="$pesantrenLinks"
            :catatans="$pesantrenCatatans"
        />
    </div>
</x-ui.section-card>

@if((int) $akreditasi->status >= \App\StateMachine\AkreditasiStateMachine::STATUS_PASCA_VISITASI)
<x-ui.section-card title="Penilaian EDPM Asesor" subtitle="Penilaian asesor terhadap EDPM pesantren" class="mt-4">
    <div class="p-6">
        <form method="POST" action="{{ route('asesor.akreditasi.save-edpm', $akreditasi->uuid) }}" id="edpmForm">
            @csrf
            <input type="hidden" name="is_final" value="0">

            <x-akreditasi.edpm-review
                :komponens="$komponens"
                :evaluasis="$asesorEvaluasis"
                :links="[]"
                :catatans="$asesorCatatans"
                :editable="!$isLocked"
            />

            @if(!$isLocked)
                <div class="d-flex gap-3 mt-4">
                    <x-ui.button type="button" variant="primary" x-on:click="confirmSaveEdpm(false)">
                        <i class="ki-solid ki-disk fs-4 me-1"></i>
                        Simpan Draft
                    </x-ui.button>
                    <x-ui.button type="button" variant="success" x-on:click="confirmSaveEdpm(true)">
                        <i class="ki-solid ki-check-circle fs-4 me-1"></i>
                        Finalisasi EDPM
                    </x-ui.button>
                </div>
            @endif
        </form>
    </div>
</x-ui.section-card>
@endif
