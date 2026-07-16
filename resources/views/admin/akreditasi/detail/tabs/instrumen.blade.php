@if ($activeTab === 'instrumen')
<div class="d-flex flex-column gap-5">
        @if(! $canShowAdminScoring)
            @include('admin.akreditasi.detail.tabs.instrumen.gate-status')
        @else
            @php
                $canEditNv = (int) $akreditasi->status === \App\StateMachine\AkreditasiStateMachine::STATUS_VALIDASI_ADMIN
                    && ! (bool) $akreditasi->is_nv_final;
            @endphp

            @include('admin.akreditasi.detail.tabs.instrumen.document-alert')
            @include('admin.akreditasi.detail.tabs.instrumen.progress')

            @if($canEditNv)
                <form method="POST" action="{{ route('admin.akreditasi-detail.save-nv', $akreditasi->uuid) }}">
                    @csrf
                    @include('admin.akreditasi.detail.tabs.instrumen.score-table', ['canEditNv' => true])
                    @include('admin.akreditasi.detail.tabs.instrumen.nv-actions')
                </form>
            @else
                @include('admin.akreditasi.detail.tabs.instrumen.score-table', ['canEditNv' => false])
            @endif

            @include('admin.akreditasi.detail.tabs.instrumen.score-summary')
            @include('admin.akreditasi.detail.tabs.instrumen.final-decision')
        @endif
    </div>
@endif
