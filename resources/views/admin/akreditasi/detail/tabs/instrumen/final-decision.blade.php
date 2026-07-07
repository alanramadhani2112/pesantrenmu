@if($showFinalDecision)
    <x-ui.section-card title="Keputusan Akhir" subtitle="Terbitkan SK atau tolak hasil akreditasi setelah NV final.">
        <div class="p-6">
            <div class="d-flex gap-4">
                <x-ui.button type="button" variant="success" size="lg" @click="$dispatch('open-modal', 'approve-final-modal')">
                    <x-ui.icon name="check-circle" class="fs-4 me-2" />
                    Terbitkan SK
                </x-ui.button>

                <x-ui.button type="button" variant="danger" size="lg" @click="$dispatch('open-modal', 'reject-final-modal')">
                    <x-ui.icon name="x-circle" class="fs-4 me-2" />
                    Tolak Akreditasi
                </x-ui.button>
            </div>
        </div>
    </x-ui.section-card>

    <x-ui.modal name="approve-final-modal" focusable>
        <form method="POST" action="{{ route('admin.akreditasi-detail.approve', $akreditasi->uuid) }}" enctype="multipart/form-data">
            @csrf
            <x-ui.modal-header title="Terbitkan SK Akreditasi" subtitle="Lengkapi data SK final." icon="check-circle" variant="success" />
            <x-ui.modal-body>
                <div class="row g-5">
                    <div class="col-md-6">
                        <x-ui.form-field label="Nomor SK" for="nomor_sk" :error="$errors->first('nomor_sk')">
                            <input type="text" name="nomor_sk" id="nomor_sk" class="form-control" value="{{ old('nomor_sk') }}" required maxlength="100" />
                        </x-ui.form-field>
                    </div>
                    <div class="col-md-6">
                        <x-ui.form-field label="Sertifikat PDF" for="sertifikat_file" :error="$errors->first('sertifikat_file')">
                            <input type="file" name="sertifikat_file" id="sertifikat_file" class="form-control" accept="application/pdf" required />
                        </x-ui.form-field>
                    </div>
                    <div class="col-md-6">
                        <x-ui.form-field label="Masa Berlaku Mulai" for="masa_berlaku" :error="$errors->first('masa_berlaku')">
                            <input type="date" name="masa_berlaku" id="masa_berlaku" class="form-control" value="{{ old('masa_berlaku') }}" required />
                        </x-ui.form-field>
                    </div>
                    <div class="col-md-6">
                        <x-ui.form-field label="Masa Berlaku Akhir" for="masa_berlaku_akhir" :error="$errors->first('masa_berlaku_akhir')">
                            <input type="date" name="masa_berlaku_akhir" id="masa_berlaku_akhir" class="form-control" value="{{ old('masa_berlaku_akhir') }}" required />
                        </x-ui.form-field>
                    </div>
                    <div class="col-12">
                        <x-ui.form-field label="Catatan Admin" for="catatan_admin" :error="$errors->first('catatan_admin')">
                            <textarea name="catatan_admin" id="catatan_admin" rows="3" class="form-control">{{ old('catatan_admin') }}</textarea>
                        </x-ui.form-field>
                    </div>
                </div>
            </x-ui.modal-body>
            <x-ui.modal-footer>
                <x-ui.button type="button" variant="light" x-on:click="$dispatch('close-modal', 'approve-final-modal')">Batal</x-ui.button>
                <x-ui.button type="submit" variant="success">Terbitkan SK</x-ui.button>
            </x-ui.modal-footer>
        </form>
    </x-ui.modal>

    <x-ui.modal name="reject-final-modal" focusable>
        <form method="POST" action="{{ route('admin.akreditasi-detail.reject', $akreditasi->uuid) }}">
            @csrf
            <x-ui.modal-header title="Tolak Akreditasi" subtitle="Pilih alasan penolakan final." icon="cross-circle" variant="danger" />
            <x-ui.modal-body>
                <x-ui.form-field label="Kategori Penolakan" for="rejection_category" :error="$errors->first('rejectionCategories')">
                    <select name="rejectionCategories[0][category]" id="rejection_category" class="form-select" required>
                        <option value="">Pilih kategori</option>
                        @foreach(config('akreditasi.final_rejection_categories', []) as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </x-ui.form-field>
                <x-ui.form-field label="Penjelasan" for="rejection_explanation" :error="$errors->first('rejectionCategories.0.explanation')">
                    <textarea name="rejectionCategories[0][explanation]" id="rejection_explanation" rows="4" class="form-control" required minlength="10" maxlength="2000"></textarea>
                </x-ui.form-field>
            </x-ui.modal-body>
            <x-ui.modal-footer>
                <x-ui.button type="button" variant="light" x-on:click="$dispatch('close-modal', 'reject-final-modal')">Batal</x-ui.button>
                <x-ui.button type="submit" variant="danger">Tolak Akreditasi</x-ui.button>
            </x-ui.modal-footer>
        </form>
    </x-ui.modal>
@endif
