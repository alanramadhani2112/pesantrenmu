<x-app-layout>
    <x-slot name="header">{{ __('Pengaturan Profil') }}</x-slot>

    <x-ui.page title="Pengaturan Profil" subtitle="Kelola nama, email, password, dan keamanan akun Anda.">
        <div class="row g-6 justify-content-center">
            <div class="col-xl-8">
                <div class="d-flex flex-column gap-6">

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

                    {{-- Delete Account --}}
                    <x-ui.section-card title="Hapus Akun" subtitle="Setelah akun dihapus, semua data akan hilang permanen.">
                        <div class="p-6">
                            <livewire:profile.delete-user-form />
                        </div>
                    </x-ui.section-card>

                </div>
            </div>
        </div>
    </x-ui.page>
</x-app-layout>
