{{-- Tab: IPM (Instrumen Penilaian Mutu) --}}
<x-ui.section-card title="Instrumen Penilaian Mutu (IPM)" subtitle="Dokumen IPM yang diunggah pesantren" class="spm-asesor-document-panel">
<div class="p-5">
        <div class="row g-3">
            @foreach($ipmItems as $field => $label)
                <div class="col-12">
                    <div class="spm-document-review-item">
                        @if($ipm && !empty($ipm->$field))
<x-ui.icon name="check-circle" class="text-success fs-5" />
                            <a href="{{ Storage::url($ipm->$field) }}" target="_blank" class="text-primary fw-semibold fs-7">{{ $label }}</a>
                        @else
<x-ui.icon name="cross-circle" class="text-muted fs-5" />
                            <span class="text-muted fs-7">{{ $label }}</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-ui.section-card>
