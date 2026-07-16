{{-- Tab: IPM (Instrumen Penilaian Mutu) --}}
<x-ui.section-card title="Instrumen Penilaian Mutu (IPM)" subtitle="Dokumen IPM yang diunggah pesantren" class="spm-asesor-document-panel">
<div class="p-5">
        <div class="spm-document-grid">
            @foreach($ipmItems as $field => $label)
                <x-ui.document-item :label="$label" :href="$ipm && !empty($ipm->$field) ? Storage::url($ipm->$field) : null" />
            @endforeach
        </div>
    </div>
</x-ui.section-card>
