                    @if ($akreditasi->status == 1)
                        <div class="row g-6">
                            <div class="col-lg-6">
                                <x-ui.section-card title="Setujui Akreditasi" subtitle="Lengkapi SK dan sertifikat final.">
                                    @if(in_array($akreditasi->status, [0, -1]))
                                        <div class="p-6">
                                            <x-ui.alert variant="warning" class="mb-0">
                                                Akreditasi telah diproses oleh admin lain. Muat ulang halaman untuk melihat status terbaru.
                                            </x-ui.alert>
                                        </div>
                                    @else
                                    <form @submit.prevent="confirmApprove($wire)" class="p-6">
                                        <x-ui.form-field label="Nomor SK" for="nomor_sk" :error="$errors->get('nomor_sk')">
                                            <x-ui.input model="nomor_sk" id="nomor_sk" placeholder="Masukkan nomor SK resmi..." required />
                                        </x-ui.form-field>

                                        <x-ui.form-field label="Unggah Sertifikat (PDF)" for="sertifikat_file" :error="$errors->get('sertifikat_file')">
                                            <x-ui.file-upload
                                                model="sertifikat_file"
                                                id="sertifikat_file"
                                                accept="application/pdf"
                                                :file="$sertifikat_file"
                                                placeholder="Pilih file sertifikat"
                                                hint="PDF maksimal 10MB"
                                            />
                                            <div wire:loading wire:target="sertifikat_file" class="text-primary fw-semibold fs-8 mt-2">Mengunggah...</div>
                                        </x-ui.form-field>

                                        <div class="row g-5">
                                            <div class="col-md-6">
                                                <x-ui.form-field label="Mulai Berlaku" for="masa_berlaku" :error="$errors->get('masa_berlaku')">
                                                    <x-ui.input model="masa_berlaku" id="masa_berlaku" type="date" required />
                                                </x-ui.form-field>
                                            </div>
                                            <div class="col-md-6">
                                                <x-ui.form-field label="Akhir Berlaku" for="masa_berlaku_akhir" :error="$errors->get('masa_berlaku_akhir')">
                                                    <x-ui.input model="masa_berlaku_akhir" id="masa_berlaku_akhir" type="date" required />
                                                </x-ui.form-field>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-end">
                                            <x-ui.button type="submit" variant="success" wire:loading.attr="disabled">
                                                <span wire:loading.remove wire:target="approve">Setujui & Simpan</span>
                                                <span wire:loading wire:target="approve">Memproses...</span>
                                            </x-ui.button>
                                        </div>
                                    </form>
                                    @endif
                                </x-ui.section-card>
                            </div>

                            <div class="col-lg-6">
                                <x-ui.section-card title="Tolak Akreditasi" subtitle="Pilih kategori dan berikan penjelasan per kategori.">
                                    @if(in_array($akreditasi->status, [0, -1]))
                                        <div class="p-6">
                                            <x-ui.alert variant="warning" class="mb-0">
                                                Akreditasi telah diproses oleh admin lain. Muat ulang halaman untuk melihat status terbaru.
                                            </x-ui.alert>
                                        </div>
                                    @else
                                    <form x-on:submit.prevent="confirmRejectFinal($wire)" class="p-6">
                                        <div class="mb-4">
                                            <div class="spm-detail-label mb-2">Kategori Penolakan <span class="text-danger">*</span></div>

                                            @foreach($rejectionCategories as $index => $entry)
                                                <div class="spm-soft-panel mb-3">
                                                    <div class="d-flex align-items-start justify-content-between gap-2">
                                                        <div class="flex-grow-1">
                                                            <x-ui.select model="rejectionCategories.{{ $index }}.category" size="sm" class="mb-2">
                                                                <option value="">-- Pilih Kategori --</option>
                                                                @foreach(config('akreditasi.final_rejection_categories', []) as $key => $label)
                                                                    <option value="{{ $key }}">{{ $label }}</option>
                                                                @endforeach
                                                            </x-ui.select>
                                                            @error("rejectionCategories.{$index}.category")
                                                                <div class="text-danger fs-8">{{ $message }}</div>
                                                            @enderror

                                                            <x-ui.textarea
                                                                model="rejectionCategories.{{ $index }}.explanation"
                                                                rows="3"
                                                                placeholder="Penjelasan detail (min 10 karakter)..."
                                                            />
                                                            @error("rejectionCategories.{$index}.explanation")
                                                                <div class="text-danger fs-8">{{ $message }}</div>
                                                            @enderror
                                                        </div>
                                                        <x-ui.button type="button" variant="light-danger" size="sm" wire:click="removeRejectionCategory({{ $index }})">
                                                            <x-ui.icon name="cross" class="fs-6" />
                                                        </x-ui.button>
                                                    </div>
                                                </div>
                                            @endforeach

                                            @error('rejectionCategories')
                                                <div class="text-danger fs-8 mb-2">{{ $message }}</div>
                                            @enderror

                                            <x-ui.button type="button" wire:click="addRejectionCategory" variant="light" size="sm">
                                                + Tambah Kategori
                                            </x-ui.button>
                                        </div>

                                        <div class="d-flex justify-content-end">
                                            <x-ui.button type="submit" variant="danger" wire:loading.attr="disabled">
                                                <span wire:loading.remove wire:target="reject">Tolak Pengajuan</span>
                                                <span wire:loading wire:target="reject">Memproses...</span>
                                            </x-ui.button>
                                        </div>
                                    </form>
                                    @endif
                                </x-ui.section-card>
                            </div>
                        </div>
                    @endif
