{{-- Tab: IPM (Instrumen Penilaian Mutu) --}}
<x-ui.section-card title="Instrumen Penilaian Mutu (IPM)" subtitle="Dokumen IPM yang diunggah pesantren" class="spm-asesor-document-panel">
    <div class="p-6">
        <div class="row g-3">
            @foreach($ipmItems as $field => $label)
                <div class="col-12">
                    <div class="spm-document-review-item">
                        @if($ipm && !empty($ipm->$field))
                            <i class="ki-solid ki-check-circle text-success fs-5"></i>
                            <a href="{{ Storage::url($ipm->$field) }}" target="_blank" class="text-primary fw-semibold fs-7">{{ $label }}</a>
                        @else
                            <i class="ki-solid ki-cross-circle text-muted fs-5"></i>
                            <span class="text-muted fs-7">{{ $label }}</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-ui.section-card>
