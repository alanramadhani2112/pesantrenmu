                    @if ((int) $akreditasi->status === \App\StateMachine\AkreditasiStateMachine::STATUS_PASCA_VISITASI && count($this->adminScoringProgressCards()) > 0)
                        <x-ui.section-card title="Progress Penilaian Asesor" subtitle="Kelengkapan pengisian butir oleh masing-masing asesor.">
                            <div class="p-6">
                                <div class="row g-5">
                                    @foreach ($this->adminScoringProgressCards() as $card)
                                        <div class="col-lg-4">
                                            <x-progress-indicator
                                                :filled="$card['progress']['filled']"
                                                :total="$card['progress']['total']"
                                                :percentage="$card['progress']['percentage']"
                                                :label="$card['label']"
                                                :color="$card['color']"
                                            />
                                        </div>
                                    @endforeach
                                </div>

                                @if (count($this->adminScoringBlockers()) > 0)
                                    <div class="d-flex flex-wrap gap-2 mt-4">
                                        @foreach ($this->adminScoringBlockers() as $blocker)
                                            <x-ui.badge variant="warning">
                                                <x-ui.icon name="timer" class="fs-7 me-1" />
                                                {{ $blocker }}
                                            </x-ui.badge>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </x-ui.section-card>
                    @endif
