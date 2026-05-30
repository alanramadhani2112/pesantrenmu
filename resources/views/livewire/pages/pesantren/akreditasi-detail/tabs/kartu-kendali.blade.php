            @if ($activeTab === 'kartu')
                <x-ui.section-card title="Kartu Kendali" subtitle="Unduh, tinjau, lalu unggah kembali kartu kendali final.">
                    <div class="p-6">
                        <div class="row g-5">
                            <div class="col-lg-4">
                                <div class="spm-soft-panel h-100">
                                    <div class="spm-detail-label">Langkah 1</div>
                                    <div class="spm-detail-value">Unduh template kartu kendali dari menu dokumen.</div>
                                    <x-ui.button :href="route('documents.index', ['doc' => 'kartu_kendali'])" variant="light" size="sm" class="mt-4">Unduh Template</x-ui.button>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="spm-soft-panel h-100">
                                    <div class="spm-detail-label">Langkah 2</div>
                                    <div class="spm-detail-value">Tinjau kelengkapan data dan tanda tangan hasil visitasi.</div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="spm-soft-panel h-100">
                                    <div class="spm-detail-label">Langkah 3</div>
                                    @if($akreditasi->status == 2 && $akreditasi->kartu_kendali && !$errors->has('kartu_kendali_file'))
                                        <x-ui.document-item label="Kartu Kendali" :href="Storage::url($akreditasi->kartu_kendali)" />
                                    @elseif($akreditasi->status == 2)
                                        <x-ui.form-field label="Unggah Kartu Kendali" for="kartu_kendali_file" :error="$errors->get('kartu_kendali_file')">
                                            <x-ui.file-upload
                                                model="kartu_kendali_file"
                                                id="kartu_kendali_file"
                                                accept=".pdf,.docx"
                                                :file="$kartu_kendali_file"
                                                placeholder="Pilih file kartu kendali"
                                                hint="PDF/DOCX maksimal 5MB"
                                            />
                                        </x-ui.form-field>

                                        @if($kartu_kendali_file)
                                            <x-ui.button type="button" @click="confirmUploadKartu($wire)" wire:loading.attr="disabled" class="w-100 justify-content-center">
                                                <span wire:loading.remove wire:target="uploadKartuKendali">Simpan Kartu Kendali</span>
                                                <span wire:loading wire:target="uploadKartuKendali">Mengunggah...</span>
                                            </x-ui.button>
                                        @endif
                                    @else
                                        <div class="text-muted fw-semibold fs-7">Menu unggah muncul saat status pengajuan Validasi.</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </x-ui.section-card>
            @endif
