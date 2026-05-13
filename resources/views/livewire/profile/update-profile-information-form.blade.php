<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component
{
    public string $name = '';
    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section>
    <form wire:submit="updateProfileInformation">
        <div class="d-flex flex-column gap-5">
            <div>
                <x-ui.form-field label="{{ __('Nama') }}" for="name">
                    <x-ui.input wire:model="name" id="name" type="text" required autofocus autocomplete="name" />
                    @error('name') <div class="text-danger fs-8 mt-1">{{ $message }}</div> @enderror
                </x-ui.form-field>
            </div>
            <div>
                <x-ui.form-field label="{{ __('Email') }}" for="email">
                    <x-ui.input wire:model="email" id="email" type="email" required autocomplete="username" />
                    @error('email') <div class="text-danger fs-8 mt-1">{{ $message }}</div> @enderror
                </x-ui.form-field>

                @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                <div class="alert alert-warning d-flex align-items-center gap-3 mt-3">
                    <x-ui.icon name="information" class="fs-4 text-warning" />
                    <div class="fs-7">
                        {{ __('Email Anda belum terverifikasi.') }}
                        <x-ui.button type="button" wire:click.prevent="sendVerification" variant="link" size="sm" class="p-0 ms-1">
                            {{ __('Kirim ulang email verifikasi.') }}
                        </x-ui.button>
                    </div>
                </div>
                @if (session('status') === 'verification-link-sent')
                <div class="alert alert-success fs-8 mt-2">
                    {{ __('Link verifikasi baru telah dikirim ke email Anda.') }}
                </div>
                @endif
                @endif
            </div>
            <div class="d-flex align-items-center gap-4">
                <x-ui.button type="submit" variant="primary"
                    wire:loading.attr="disabled" wire:target="updateProfileInformation">
                    <span wire:loading.remove wire:target="updateProfileInformation">{{ __('Simpan') }}</span>
                    <span wire:loading wire:target="updateProfileInformation">{{ __('Menyimpan...') }}</span>
                </x-ui.button>
                <x-action-message on="profile-updated" class="text-success fs-8 fw-bold">
                    {{ __('Tersimpan.') }}
                </x-action-message>
            </div>
        </div>
    </form>
</section>
