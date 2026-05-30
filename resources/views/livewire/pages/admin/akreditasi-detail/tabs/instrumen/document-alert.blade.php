                    @if ($akreditasi->status == 1 && (empty($akreditasi->kartu_kendali) || empty($akreditasi->laporan_visitasi_asesor1)))
                        <x-ui.alert variant="warning" icon="timer" title="Kelengkapan Dokumen Wajib">
                            Nilai NV hanya dapat disimpan apabila Kartu Kendali dan Laporan Visitasi telah diunggah.
                        </x-ui.alert>
                    @endif
