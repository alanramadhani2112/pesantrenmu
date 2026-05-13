<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component
{
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
    }
}; ?>

<section>
    <form wire:submit="updatePassword">
        <div class="d-flex flex-column gap-5">
            <div>
                <x-ui.form-field label="{{ __('Password Saat Ini') }}" for="current_password">
                    <x-ui.input wire:model="current_password" id="current_password" type="password" autocomplete="current-password" />
                    @error('current_password') <div class="text-danger fs-8 mt-1">{{ $message }}</div> @enderror
                </x-ui.form-field>
            </div>
            <div>
                <x-ui.form-field label="{{ __('Password Baru') }}" for="password">
                    <x-ui.input wire:model="password" id="password" type="password" autocomplete="new-password" />
                    @error('password') <div class="text-danger fs-8 mt-1">{{ $message }}</div> @enderror
                </x-ui.form-field>
            </div>
            <div>
                <x-ui.form-field label="{{ __('Konfirmasi Password Baru') }}" for="password_confirmation">
                    <x-ui.input wire:model="password_confirmation" id="password_confirmation" type="password" autocomplete="new-password" />
                    @error('password_confirmation') <div class="text-danger fs-8 mt-1">{{ $message }}</div> @enderror
                </x-ui.form-field>
            </div>
            <div class="d-flex align-items-center gap-4">
                <x-ui.button type="submit" variant="primary"
                    wire:loading.attr="disabled" wire:target="updatePassword">
                    <span wire:loading.remove wire:target="updatePassword">{{ __('Simpan') }}</span>
                    <span wire:loading wire:target="updatePassword">{{ __('Menyimpan...') }}</span>
                </x-ui.button>
                <x-action-message on="password-updated" class="text-success fs-8 fw-bold">
                    {{ __('Tersimpan.') }}
                </x-action-message>
            </div>
        </div>
    </form>
</section>
