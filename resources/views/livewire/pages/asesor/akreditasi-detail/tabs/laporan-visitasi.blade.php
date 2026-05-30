            @if ($activeTab === 'laporan_visitasi')
                <div class="d-flex flex-column gap-6">
                    {{-- Laporan Individu --}}
                    <x-ui.section-card
                        title="{{ $asesorTipe === 1 ? 'Laporan Visitasi Individu Ketua Kelompok' : 'Laporan Visitasi Individu Anggota Kelompok' }}"
                        subtitle="Unggah laporan hasil visitasi individu Anda (PDF/DOCX, max 5MB)."
                    >
                        <div class="p-6">
                            @php
                                $laporanIndividuPath = $asesorTipe === 1
                                    ? $akreditasi->laporan_visitasi_asesor1
                                    : $akreditasi->laporan_visitasi_asesor2;
                            @endphp

                            @if($laporanIndividuPath)
                                <div class="d-flex align-items-center gap-4 mb-4">
                                    <x-ui.icon name="document" class="fs-2 text-success" />
                                    <div>
                                        <div class="fw-semibold text-success mb-1">Laporan sudah diunggah</div>
                                        <x-ui.button :href="Storage::url($laporanIndividuPath)" target="_blank" variant="light" size="sm" class="btn-active-light-primary" icon="eye">
                                            Lihat Laporan
                                        </x-ui.button>
                                    </div>
                                </div>
                            @endif

                            @if((int) $akreditasi->status === \App\StateMachine\AkreditasiStateMachine::STATUS_PASCA_VISITASI)
                                <x-ui.form-field
                                    :label="$laporanIndividuPath ? 'Ganti Laporan Individu' : 'Unggah Laporan Individu'"
                                    for="laporan_individu_file"
                                    :error="$errors->get('laporan_individu_file')"
                                    hint="PDF atau DOCX, maksimal 5MB"
                                >
                                    <x-ui.file-upload
                                        model="laporan_individu_file"
                                        id="laporan_individu_file"
                                        accept=".pdf,.docx"
                                        :file="$laporan_individu_file"
                                        placeholder="Pilih file laporan individu"
                                    />
                                </x-ui.form-field>
                                @if($laporan_individu_file)
                                    <x-ui.button type="button" wire:click="uploadLaporanIndividu" wire:loading.attr="disabled" variant="primary" class="mt-3">
                                        <span wire:loading.remove wire:target="uploadLaporanIndividu">Simpan Laporan Individu</span>
                                        <span wire:loading wire:target="uploadLaporanIndividu">Mengunggah...</span>
                                    </x-ui.button>
                                @endif
                            @else
                                @if(!$laporanIndividuPath)
                                    <div class="text-muted fs-7">Laporan dapat diunggah saat status Penilaian Pasca Visitasi.</div>
                                @endif
                            @endif
                        </div>
                    </x-ui.section-card>

                    {{-- Laporan Kelompok (Ketua Kelompok only) --}}
                    @if($asesorTipe === 1)
                        <x-ui.section-card title="Laporan Visitasi Kelompok" subtitle="Unggah laporan kelompok hasil visitasi (PDF/DOCX, max 5MB). Hanya Ketua Kelompok yang dapat mengunggah.">
                            <div class="p-6">
                                @if($akreditasi->laporan_visitasi_kelompok)
                                    <div class="d-flex align-items-center gap-4 mb-4">
                                        <x-ui.icon name="document" class="fs-2 text-success" />
                                        <div>
                                            <div class="fw-semibold text-success mb-1">Laporan kelompok sudah diunggah</div>
                                            <x-ui.button :href="Storage::url($akreditasi->laporan_visitasi_kelompok)" target="_blank" variant="light" size="sm" class="btn-active-light-primary" icon="eye">
                                                Lihat Laporan
                                            </x-ui.button>
                                        </div>
                                    </div>
                                @endif

                                @if((int) $akreditasi->status === \App\StateMachine\AkreditasiStateMachine::STATUS_PASCA_VISITASI)
                                    <x-ui.form-field
                                        :label="$akreditasi->laporan_visitasi_kelompok ? 'Ganti Laporan Kelompok' : 'Unggah Laporan Kelompok'"
                                        for="laporan_kelompok_file"
                                        :error="$errors->get('laporan_kelompok_file')"
                                        hint="PDF atau DOCX, maksimal 5MB"
                                    >
                                        <x-ui.file-upload
                                            model="laporan_kelompok_file"
                                            id="laporan_kelompok_file"
                                            accept=".pdf,.docx"
                                            :file="$laporan_kelompok_file"
                                            placeholder="Pilih file laporan kelompok"
                                        />
                                    </x-ui.form-field>
                                    @if($laporan_kelompok_file)
                                        <x-ui.button type="button" wire:click="uploadLaporanKelompok" wire:loading.attr="disabled" variant="primary" class="mt-3">
                                            <span wire:loading.remove wire:target="uploadLaporanKelompok">Simpan Laporan Kelompok</span>
                                            <span wire:loading wire:target="uploadLaporanKelompok">Mengunggah...</span>
                                        </x-ui.button>
                                    @endif
                                @else
                                    @if(!$akreditasi->laporan_visitasi_kelompok)
                                        <div class="text-muted fs-7">Laporan dapat diunggah saat status Penilaian Pasca Visitasi.</div>
                                    @endif
                                @endif
                            </div>
                        </x-ui.section-card>
                    @endif
                </div>
            @endif
