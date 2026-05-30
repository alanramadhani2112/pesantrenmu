            @if ($activeTab === 'instrumen')
                <div class="d-flex flex-column gap-6">
                    @include('livewire.pages.asesor.akreditasi-detail.tabs.instrumen.status-alert')
                    @include('livewire.pages.asesor.akreditasi-detail.tabs.instrumen.progress')

                    <form x-on:submit.prevent="confirmSaveInstrumen($wire)" class="d-flex flex-column gap-6">
                        @include('livewire.pages.asesor.akreditasi-detail.tabs.instrumen.score-table')
                        @include('livewire.pages.asesor.akreditasi-detail.tabs.instrumen.component-recommendations')
                    </form>

                    @include('livewire.pages.asesor.akreditasi-detail.tabs.instrumen.finalization-alert')
                    @include('livewire.pages.asesor.akreditasi-detail.tabs.instrumen.action-panel')
                    @include('livewire.pages.asesor.akreditasi-detail.tabs.instrumen.scroll-actions')
                    @include('livewire.pages.asesor.akreditasi-detail.tabs.instrumen.visitasi-confirmation')
                    @include('livewire.pages.asesor.akreditasi-detail.tabs.instrumen.finalize-scoring')
                </div>
            @endif