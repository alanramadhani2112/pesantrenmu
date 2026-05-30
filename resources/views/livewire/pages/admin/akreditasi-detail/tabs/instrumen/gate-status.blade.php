                        <x-ui.alert variant="info" icon="information-2" title="Penilaian Belum Dibuka">
                            Alur yang benar: visitasi dilakukan lebih dulu, Ketua Kelompok mengonfirmasi visitasi selesai, lalu Nilai Ketua dan Nilai Anggota diisi pada tahap Penilaian Pasca Visitasi. Nilai Verifikasi Admin baru terbuka setelah Nilai Kelompok final.
                        </x-ui.alert>

                        <x-ui.section-card title="Posisi Proses Saat Ini" subtitle="Admin dapat memantau berkas, tim asesor, dan jadwal visitasi pada tahap ini.">
                            <div class="p-6">
                                <div class="row g-5">
                                    <x-ui.detail-item label="Status Saat Ini" value="{{ Akreditasi::getStatusLabel($akreditasi->status) }}" />
                                    <x-ui.detail-item label="Jadwal Visitasi" value="{{ $akreditasi->tgl_visitasi ? \Carbon\Carbon::parse($akreditasi->tgl_visitasi)->format('d M Y') : 'Belum dijadwalkan' }}" />
                                    <x-ui.detail-item label="Tahap Nilai Berikutnya" value="Penilaian Pasca Visitasi" />
                                    <x-ui.detail-item label="Nilai Verifikasi" value="Terbuka setelah Nilai Kelompok final" />
                                </div>
                            </div>
                        </x-ui.section-card>
