<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);

            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div>
    <div class="text-center mb-10">
        <x-ui.badge variant="info" class="mb-4">Verifikasi</x-ui.badge>
        <h1 class="text-gray-900 fw-bolder mb-3">Verifikasi Email</h1>
        <div class="text-gray-500 fw-semibold fs-6">
            Terima kasih telah mendaftar! Silakan verifikasi email Anda dengan mengklik link yang kami kirimkan.
            Jika tidak menerima email, kami akan mengirim ulang.
        </div>
    </div>

    @if (session('status') == 'verification-link-sent')
    <div class="alert alert-success d-flex align-items-center gap-3 mb-6">
        <i class="ki-duotone ki-check-circle fs-2x text-success"><span class="path1"></span><span class="path2"></span></i>
        <span class="fw-semibold">Link verifikasi baru telah dikirim ke email Anda.</span>
    </div>
    @endif

    <div class="d-flex flex-column gap-4">
        <x-ui.button type="button" wire:click="sendVerification" variant="primary" size="lg" class="w-100">
            Kirim Ulang Email Verifikasi
        </x-ui.button>

        <div class="text-center">
            <x-ui.button wire:click="logout" type="button" variant="link" class="text-gray-600 fw-semibold fs-6 p-0">
                Keluar
            </x-ui.button>
        </div>
    </div>
</div>
