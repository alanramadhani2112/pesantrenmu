<x-app-layout>
    <x-slot name="header">{{ __('Pengaturan Profil') }}</x-slot>

    <x-ui.page title="Pengaturan Profil" subtitle="Kelola nama, email, password, dan keamanan akun Anda.">
        <div class="row g-6 justify-content-center">
            <div class="col-xl-8">
                <div class="d-flex flex-column gap-6">

                    {{-- Profile Photo --}}
                    <x-ui.section-card title="Foto Profil" subtitle="Unggah foto profil Anda. Maksimal 2MB, format JPG/PNG.">
                        <div class="p-6">
                            <livewire:profile.update-profile-photo />
                        </div>
                    </x-ui.section-card>

                    {{-- Update Profile Info --}}
                    <x-ui.section-card title="Informasi Profil" subtitle="Perbarui nama dan alamat email akun Anda.">
                        <div class="p-6">
                            <livewire:profile.update-profile-information-form />
                        </div>
                    </x-ui.section-card>

                    {{-- Update Password --}}
                    <x-ui.section-card title="Ubah Password" subtitle="Gunakan password yang panjang dan acak agar akun tetap aman.">
                        <div class="p-6">
                            <livewire:profile.update-password-form />
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
