<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="text-center mb-10">
        <x-ui.badge variant="primary" class="mb-4">Akses Sistem</x-ui.badge>
        <h1 class="text-gray-900 fw-bolder mb-3">Masuk ke SPM</h1>
        <div class="text-gray-500 fw-semibold fs-6">Gunakan akun yang sudah terdaftar.</div>
    </div>

    <x-auth-session-status class="alert alert-success mb-6" :status="session('status')" />

    <form wire:submit="login" class="form w-100">
        <div class="fv-row mb-8">
            <label for="email" class="form-label fw-bold text-gray-900">Email</label>
            <div class="position-relative">
                <i class="ki-duotone ki-sms fs-2 text-gray-500 position-absolute top-50 translate-middle-y ms-4">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
                <x-ui.input
                    model="form.email"
                    id="email"
                    type="email"
                    name="email"
                    class="form-control-lg ps-12"
                    required
                    autofocus
                    autocomplete="username"
                />
            </div>
            <x-input-error :messages="$errors->get('form.email')" class="invalid-feedback d-block mt-2" />
        </div>

        <div class="fv-row mb-8" x-data="{ show: false }">
            <label for="password" class="form-label fw-bold text-gray-900">Password</label>

            <div class="position-relative">
                <i class="ki-duotone ki-lock-2 fs-2 text-gray-500 position-absolute top-50 translate-middle-y ms-4">
                    <span class="path1"></span>
                    <span class="path2"></span>
                    <span class="path3"></span>
                    <span class="path4"></span>
                    <span class="path5"></span>
                </i>
                <x-ui.input
                    model="form.password"
                    id="password"
                    class="form-control-lg ps-12 pe-12"
                    type="password"
                    x-bind:type="show ? 'text' : 'password'"
                    name="password"
                    required
                    autocomplete="current-password"
                />

                <x-ui.button
                    type="button"
                    variant="light"
                    size="sm"
                    class="btn-icon btn-active-light-primary position-absolute top-50 end-0 translate-middle-y me-2"
                    @click="show = !show"
                    aria-label="Tampilkan password"
                >
                    <i class="ki-duotone ki-eye fs-2 text-gray-500" x-show="!show">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                    </i>
                    <i class="ki-duotone ki-eye-slash fs-2 text-gray-500" x-show="show" x-cloak>
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                        <span class="path4"></span>
                    </i>
                </x-ui.button>
            </div>

            <x-input-error :messages="$errors->get('form.password')" class="invalid-feedback d-block mt-2" />
        </div>

        <div class="d-grid">
            <x-ui.button type="submit" variant="primary" size="lg">
                <span class="indicator-label d-flex align-items-center justify-content-center gap-2">
                    Masuk
                    <i class="ki-duotone ki-arrow-right fs-2 text-white">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </span>
            </x-ui.button>
        </div>
    </form>

    <!-- <div class="relative flex items-center py-5">
        <div class="flex-grow border-t border-gray-300"></div>
        <span class="flex-shrink mx-4 text-gray-400 text-sm">Atau</span>
        <div class="flex-grow border-t border-gray-300"></div>
    </div>

    <div class="flex items-center justify-center">
        <a href="{{ route('sso.preflight') }}" class="w-full inline-flex justify-center items-center px-4 py-3 rounded-lg shadow-lg transform transition hover:scale-[1.02] active:scale-[0.98] overflow-hidden" 
           style="background: linear-gradient(to right, #2c506d, #427c95);">
            <img src="https://aqsa-dev.muhammadiyah.or.id/assets/media/logos/logo-white-full.png" alt="Muhammadiyah ID" class="h-8 object-contain">
        </a>
    </div> -->
</div>
