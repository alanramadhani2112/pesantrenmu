<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $password = '';

    /**
     * Confirm the current user's password.
     */
    public function confirmPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->validate([
            'email' => Auth::user()->email,
            'password' => $this->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        session(['auth.password_confirmed_at' => time()]);

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="text-center mb-10">
        <x-ui.badge variant="warning" class="mb-4">Area Aman</x-ui.badge>
        <h1 class="text-gray-900 fw-semibold mb-3">Konfirmasi Password</h1>
        <div class="text-gray-500 fw-semibold fs-6">Masukkan password Anda untuk melanjutkan.</div>
    </div>

    <form wire:submit="confirmPassword" class="form w-100">
        <x-ui.form-field class="mb-8" label="Password" for="password" :error="$errors->get('password')">
            <div class="position-relative">
                <i class="ki-solid ki-lock-2 fs-2 text-gray-500 position-absolute top-50 translate-middle-y ms-4"></i>
                <x-ui.input model="password" id="password" type="password" name="password"
                       class="form-control-lg ps-12"
                       required autocomplete="current-password" />
            </div>
        </x-ui.form-field>

        <div class="d-grid">
            <x-ui.button type="submit" variant="primary" size="lg">Konfirmasi</x-ui.button>
        </div>
    </form>
</div>
