                    {{-- Task 6.1 & 6.2: Dismissible finalization error alert --}}
                    <x-ui.alert
                        variant="danger"
                        icon="cross-circle"
                        title="Finalisasi Gagal"
                        x-data="{ show: false, errorType: '', details: null }"
                        x-on:finalization-failed.window="
                            errorType = $event.detail.error;
                            details = $event.detail.details;
                            show = true;
                            $nextTick(() => $el.scrollIntoView({ behavior: 'smooth', block: 'center' }));
                        "
                        x-show="show"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        style="display: none;"
                        class="mb-0"
                    >
                        <template x-if="errorType === 'asesor2_incomplete'">
                            <span>
                                Anggota Kelompok belum menyelesaikan Nilai Anggota
                                <template x-if="details">
                                    (<span x-text="details.filled"></span>/<span x-text="details.total"></span> butir, <span x-text="Math.round(details.percentage)"></span>%)
                                </template>.
                            </span>
                        </template>
                        <template x-if="errorType === 'asesor1_na_incomplete'">
                            <span>
                                Nilai Ketua belum lengkap
                                <template x-if="details">
                                    (<span x-text="details.filled"></span>/<span x-text="details.total"></span> butir)
                                </template>.
                            </span>
                        </template>
                        <template x-if="errorType === 'asesor1_nk_incomplete'">
                            <span>
                                Nilai Kelompok belum lengkap
                                <template x-if="details">
                                    (<span x-text="details.filled"></span>/<span x-text="details.total"></span> butir)
                                </template>.
                            </span>
                        </template>
                        <template x-if="!['asesor2_incomplete','asesor1_na_incomplete','asesor1_nk_incomplete'].includes(errorType)">
                            <span>Terjadi kesalahan saat finalisasi. Silakan coba lagi.</span>
                        </template>

                        <x-slot:actions>
                            <x-ui.button unstyled type="button" class="btn-close" @click="show = false" aria-label="Tutup"></x-ui.button>
                        </x-slot:actions>
                    </x-ui.alert>
