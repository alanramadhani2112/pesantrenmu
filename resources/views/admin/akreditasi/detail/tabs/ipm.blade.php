@if ($activeTab === 'ipm')
    <x-ui.section-card title="Indikator Pemenuhan Mutlak" subtitle="Status dokumen IPM dari pesantren.">
<div class="p-5">
            <div class="spm-document-list">
                @foreach ($ipmItems as $field => $label)
                    <x-ui.document-item :label="$label" :href="$ipm && $ipm->$field ? Storage::url($ipm->$field) : null" />
                @endforeach
            </div>
        </div>
    </x-ui.section-card>
@endif
