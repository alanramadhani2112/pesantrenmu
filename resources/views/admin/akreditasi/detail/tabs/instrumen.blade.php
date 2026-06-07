@if ($activeTab === 'instrumen')
    <div class="d-flex flex-column gap-6">
        @if(! $canShowAdminScoring)
            @include('admin.akreditasi.detail.tabs.instrumen.gate-status')
        @else
            @include('admin.akreditasi.detail.tabs.instrumen.document-alert')
            @include('admin.akreditasi.detail.tabs.instrumen.progress')
            @include('admin.akreditasi.detail.tabs.instrumen.score-table')
            @include('admin.akreditasi.detail.tabs.instrumen.nv-actions')
            @include('admin.akreditasi.detail.tabs.instrumen.score-summary')
            @include('admin.akreditasi.detail.tabs.instrumen.final-decision')
            @include('admin.akreditasi.detail.tabs.instrumen.scroll-actions')
        @endif
    </div>
@endif
