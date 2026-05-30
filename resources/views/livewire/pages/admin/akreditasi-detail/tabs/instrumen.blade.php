            @if ($activeTab === 'instrumen')
                <div class="d-flex flex-column gap-6">
                    @if(! $canShowAdminScoring)
                        @include('livewire.pages.admin.akreditasi-detail.tabs.instrumen.gate-status')
                    @else
                        @include('livewire.pages.admin.akreditasi-detail.tabs.instrumen.document-alert')
                        @include('livewire.pages.admin.akreditasi-detail.tabs.instrumen.progress')
                        @include('livewire.pages.admin.akreditasi-detail.tabs.instrumen.score-table')
                        @include('livewire.pages.admin.akreditasi-detail.tabs.instrumen.nv-actions')
                        @include('livewire.pages.admin.akreditasi-detail.tabs.instrumen.score-summary')
                        @include('livewire.pages.admin.akreditasi-detail.tabs.instrumen.final-decision')
                        @include('livewire.pages.admin.akreditasi-detail.tabs.instrumen.scroll-actions')
                    @endif
                </div>
            @endif