<x-app-layout>
    <x-ui.page title="Pengaturan Profil" subtitle="Kelola nama, email, password, dan keamanan akun Anda.">
        <div class="row g-6 justify-content-center">
            <div class="col-xl-8">
                <div class="d-flex flex-column gap-6">

                    {{-- Profile Photo --}}
                    <x-ui.section-card title="Foto Profil" subtitle="Unggah foto profil Anda. Maksimal 2MB, format JPG/PNG.">
                        <div class="p-6">
                            <div class="d-flex flex-column align-items-center">
                                <form method="POST" action="{{ route('profile.photo') }}" enctype="multipart/form-data"
                                    x-data="{
                                        preview: '{{ $user->profile_photo_path ? asset('storage/' . $user->profile_photo_path) : '' }}',
                                        changed: false,
                                        get isEmpty() { return !this.preview; },
                                    }"
                                    class="d-flex flex-column align-items-center">
                                    @csrf
                                    @method('PUT')

                                    <div data-kt-image-input="true" class="image-input image-input-circle image-input-outline mb-4"
                                        :class="{ 'image-input-empty': isEmpty, 'image-input-changed': changed }"
                                        style="background-color: #f5f8fa;">

                                        <div class="image-input-wrapper w-120px h-120px"
                                            :style="preview ? 'background-image:url(' + preview + ')' : ''">
                                        </div>

                                        <label class="btn btn-icon btn-circle btn-color-muted btn-active-color-primary w-30px h-30px bg-body border border-dashed border-gray-300"
                                            data-kt-image-input-action="change"
                                            title="Ganti foto">
                                            <x-ui.icon name="pencil" class="fs-6" />
                                            <input type="file" name="photo" accept=".png,.jpg,.jpeg"
                                                @change="
                                                    const file = $event.target.files[0];
                                                    if (file) {
                                                        preview = URL.createObjectURL(file);
                                                        changed = true;
                                                    }
                                                " />
                                        </label>

                                        <span class="btn btn-icon btn-circle btn-color-muted btn-active-color-primary w-30px h-30px bg-body border border-dashed border-gray-300"
                                            data-kt-image-input-action="cancel"
                                            title="Batal"
                                            @click="changed = false; preview = '{{ $user->profile_photo_path ? asset('storage/' . $user->profile_photo_path) : '' }}'; $el.closest('form').querySelector('input[type=file]').value = '';">
                                            <x-ui.icon name="cross" class="fs-3" />
                                        </span>
                                    </div>

                                    @error('photo')
                                        <div class="text-danger fs-8 mb-3">{{ $message }}</div>
                                    @enderror

                                    <div x-show="changed" x-cloak>
                                        <button type="submit" class="btn btn-sm btn-primary px-6">Simpan Foto</button>
                                    </div>
                                </form>

                                @if($user->profile_photo_path)
                                <form method="POST" action="{{ route('profile.photo.remove') }}" class="mt-2">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light-danger">Hapus Foto</button>
                                </form>
                                @endif

                                @if(session('status') === 'photo-updated')
                                    <div class="text-success fs-8 fw-semibold mt-3">Foto berhasil diperbarui.</div>
                                @elseif(session('status') === 'photo-removed')
                                    <div class="text-success fs-8 fw-semibold mt-3">Foto berhasil dihapus.</div>
                                @endif
                            </div>
                        </div>
                    </x-ui.section-card>

                    {{-- Update Profile Info --}}
                    <x-ui.section-card title="Informasi Profil" subtitle="Perbarui nama dan alamat email akun Anda.">
                        <div class="p-6">
                            <form method="POST" action="{{ route('profile.info') }}"
                                  x-data="formValidation"
                                  @submit="if(!validateAll()) $event.preventDefault()"
                                  @focusout.debounce.50ms="onBlur($event)"
                                  @input.debounce.150ms="onInput($event)">
                                @csrf
                                @method('PUT')

                                <div class="d-flex flex-column gap-6">
                                    <x-ui.form-field label="{{ __('Nama') }}" for="name">
                                        <input data-ui-input="metronic" type="text" id="name" name="name"
                                            value="{{ old('name', $user->name) }}"
                                            class="form-control form-control-solid @error('name') is-invalid @enderror"
                                            required autofocus autocomplete="name" />
                                        @error('name') <div class="text-danger fs-8 mt-1">{{ $message }}</div> @enderror
                                    </x-ui.form-field>

                                    <div>
                                        <x-ui.form-field label="{{ __('Email') }}" for="email">
                                            <input data-ui-input="metronic" type="email" id="email" name="email"
                                                value="{{ old('email', $user->email) }}"
                                                class="form-control form-control-solid @error('email') is-invalid @enderror"
                                                required autocomplete="username" />
                                            @error('email') <div class="text-danger fs-8 mt-1">{{ $message }}</div> @enderror
                                        </x-ui.form-field>

                                        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                                        <x-ui.alert variant="warning" icon="information" class="mt-4 mb-0">
                                            {{ __('Email Anda belum terverifikasi.') }}
                                        </x-ui.alert>
                                        @endif
                                    </div>

                                    <div class="d-flex align-items-center gap-4">
                                        <button type="submit" class="btn btn-primary">{{ __('Simpan') }}</button>
                                        @if(session('status') === 'profile-updated')
                                            <span class="text-success fs-8 fw-semibold">{{ __('Tersimpan.') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </form>
                        </div>
                    </x-ui.section-card>

                    {{-- Update Password --}}
                    <x-ui.section-card title="Ubah Password" subtitle="Gunakan password yang panjang dan acak agar akun tetap aman.">
                        <div class="p-6">
                            <form method="POST" action="{{ route('profile.password') }}"
                                  x-data="formValidation"
                                  @submit="if(!validateAll()) $event.preventDefault()"
                                  @focusout.debounce.50ms="onBlur($event)"
                                  @input.debounce.150ms="onInput($event)">
                                @csrf
                                @method('PUT')

                                <div class="d-flex flex-column gap-6">
                                    <x-ui.form-field label="{{ __('Password Saat Ini') }}" for="current_password" data-validate="required">
                                        <div class="position-relative" x-data="{ show: false }">
                                            <input data-ui-input="metronic" :type="show ? 'text' : 'password'"
                                                id="current_password" name="current_password"
                                                class="form-control form-control-solid @error('current_password') is-invalid @enderror"
                                                required autocomplete="current-password" />
                                            <button type="button"
                                                class="btn btn-icon btn-sm btn-color-muted position-absolute top-50 end-0 translate-middle-y me-1"
                                                @click="show = !show"
                                                :title="show ? 'Sembunyikan' : 'Tampilkan'">
                                                <x-ui.icon name="eye-slash" class="fs-5" x-show="!show" />
                                                <x-ui.icon name="eye" class="fs-5" x-show="show" x-cloak />
                                            </button>
                                        </div>
                                        @error('current_password') <div class="text-danger fs-8 mt-1">{{ $message }}</div> @enderror
                                    </x-ui.form-field>

                                    <div x-data="{
                                        initPwm() {
                                            this.$nextTick(() => {
                                                const el = this.$el.querySelector('[data-kt-password-meter]');
                                                if (!el) return;
                                                const prev = KTPasswordMeter?.getInstance(el);
                                                if (prev) prev.destroy();
                                                new KTPasswordMeter(el, { minLength: 8 });
                                            });
                                        },
                                        showPw: false,
                                    }" x-init="initPwm()">
                                        <x-ui.form-field label="{{ __('Password Baru') }}" for="password" data-validate="required|min:8">
                                            <div class="fv-row" data-kt-password-meter="true">
                                                <div class="position-relative mb-3">
                                                    <input data-ui-input="metronic" :type="showPw ? 'text' : 'password'"
                                                        id="password" name="password"
                                                        class="form-control form-control-solid @error('password') is-invalid @enderror"
                                                        required autocomplete="new-password" />
                                                    <button type="button"
                                                        class="btn btn-icon btn-sm btn-color-muted position-absolute top-50 end-0 translate-middle-y me-1"
                                                        data-kt-password-meter-control="visibility"
                                                        @click="showPw = !showPw"
                                                        :title="showPw ? 'Sembunyikan' : 'Tampilkan'">
                                                        <x-ui.icon name="eye-slash" class="fs-5" />
                                                        <x-ui.icon name="eye" class="fs-5" />
                                                    </button>
                                                </div>
                                                <div class="d-flex align-items-center mb-3" data-kt-password-meter-control="highlight">
                                                    <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px me-2"></div>
                                                    <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px me-2"></div>
                                                    <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px me-2"></div>
                                                    <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px"></div>
                                                </div>
                                                <div class="text-muted fs-8">
                                                    Gunakan minimal 8 karakter dengan kombinasi huruf besar, huruf kecil, angka, dan simbol.
                                                </div>
                                            </div>
                                            @error('password') <div class="text-danger fs-8 mt-1">{{ $message }}</div> @enderror
                                        </x-ui.form-field>
                                    </div>

                                    <x-ui.form-field label="{{ __('Konfirmasi Password Baru') }}" for="password_confirmation" data-validate="required">
                                        <div class="position-relative" x-data="{ show: false }">
                                            <input data-ui-input="metronic" :type="show ? 'text' : 'password'"
                                                id="password_confirmation" name="password_confirmation"
                                                class="form-control form-control-solid"
                                                required autocomplete="new-password" />
                                            <button type="button"
                                                class="btn btn-icon btn-sm btn-color-muted position-absolute top-50 end-0 translate-middle-y me-1"
                                                @click="show = !show"
                                                :title="show ? 'Sembunyikan' : 'Tampilkan'">
                                                <x-ui.icon name="eye-slash" class="fs-5" x-show="!show" />
                                                <x-ui.icon name="eye" class="fs-5" x-show="show" x-cloak />
                                            </button>
                                        </div>
                                    </x-ui.form-field>

                                    <div class="d-flex align-items-center gap-4">
                                        <button type="submit" class="btn btn-primary">{{ __('Simpan') }}</button>
                                        @if(session('status') === 'password-updated')
                                            <span class="text-success fs-8 fw-semibold">{{ __('Password berhasil diubah.') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </form>
                        </div>
                    </x-ui.section-card>

                    {{-- Account Governance --}}
                    <x-ui.section-card title="Pengelolaan Akun" subtitle="Akun pengguna terhubung dengan proses akreditasi dan audit sistem.">
                        <div class="p-6">
                            <x-ui.alert variant="info" icon="shield-tick" title="Penghapusan akun dilakukan oleh admin" class="mb-0">
                                Untuk menjaga riwayat akreditasi, audit trail, dan keterkaitan data antar role, penghapusan akun tidak tersedia sebagai aksi mandiri di halaman profil. Hubungi admin apabila akun perlu dinonaktifkan atau dikelola.
                            </x-ui.alert>
                        </div>
                    </x-ui.section-card>

                </div>
            </div>
        </div>
    </x-ui.page>
</x-app-layout>
