                    @if (count($this->asesorScoringProgressCards()) > 0)
                        <x-ui.section-card title="Progress Penilaian" subtitle="Kelengkapan pengisian butir oleh masing-masing asesor.">
                            <div class="p-6">
                                <div class="row g-5">
                                    @foreach ($this->asesorScoringProgressCards() as $card)
                                        <div class="{{ $card['column'] }}">
                                            <x-progress-indicator
                                                :filled="$card['progress']['filled']"
                                                :total="$card['progress']['total']"
                                                :percentage="$card['progress']['percentage']"
                                                :label="$card['label']"
                                                :color="$card['color']"
                                            />
                                        </div>
                                    @endforeach

                                    @if ($asesorTipe == 1 && ! $nilaiKelompokUnlocked)
                                        <div class="col-12">
                                            <x-ui.alert variant="warning" icon="information-2" title="Nilai Kelompok Terkunci" class="mb-0">
                                                Nilai Kelompok akan terbuka setelah Nilai Ketua dan Nilai Anggota disubmit final seluruhnya.
                                                @if ($asesor2NaProgress)
                                                    Progress Anggota: {{ $asesor2NaProgress['filled'] }}/{{ $asesor2NaProgress['total'] }} butir ({{ number_format($asesor2NaProgress['percentage'], 0) }}%).
                                                @endif
                                            </x-ui.alert>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </x-ui.section-card>
                    @endif
