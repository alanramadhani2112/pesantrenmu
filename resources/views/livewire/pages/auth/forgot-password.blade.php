<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $email = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $this->only('email')
        );

        if ($status != Password::RESET_LINK_SENT) {
            $this->addError('email', __($status));

            return;
        }

        $this->reset('email');

        session()->flash('status', __($status));
    }
}; ?>

<div>
    <div class="text-center mb-10">
        <x-ui.badge variant="warning" class="mb-4">Lupa Password</x-ui.badge>
        <h1 class="text-gray-900 fw-semibold mb-3">Reset Password</h1>
        <div class="text-gray-500 fw-semibold fs-6">Masukkan email Anda untuk menerima link reset password.</div>
    </div>

    @if (session('status'))
        <x-ui.alert variant="success" class="mb-6">{{ session('status') }}</x-ui.alert>
    @endif

    <form wire:submit="sendPasswordResetLink" class="form w-100">
        <x-ui.form-field class="mb-8" label="Email" for="email" :error="$errors->get('email')">
            <div class="position-relative">
                <i class="ki-duotone ki-sms fs-2 text-gray-500 position-absolute top-50 translate-middle-y ms-4"><span class="path1"></span><span class="path2"></span></i>
                <x-ui.input model="email" id="email" type="email" name="email"
                       class="form-control-lg ps-12"
                       required autofocus />
            </div>
        </x-ui.form-field>

        <div class="d-grid mb-6">
            <x-ui.button type="submit" variant="primary" size="lg">Kirim Link Reset</x-ui.button>
        </div>

        <div class="text-center">
            <a href="{{ route('login') }}" class="link-primary fw-semibold fs-6">
                <i class="ki-duotone ki-arrow-left fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                Kembali ke Login
            </a>
        </div>
    </form>
</div>
