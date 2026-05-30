                    @if ($akreditasi->status == \App\StateMachine\AkreditasiStateMachine::STATUS_VALIDASI_ADMIN)
                        <x-ui.alert variant="warning" icon="timer" title="Data Sedang Diverifikasi">
                            Penilaian sedang dalam proses verifikasi oleh admin. Nilai Kelompok hanya dapat diisi Ketua Kelompok setelah Nilai Ketua dan Nilai Anggota final seluruhnya.
                        </x-ui.alert>
                    @endif
