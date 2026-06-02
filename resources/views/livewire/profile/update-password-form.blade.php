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
    public int $pwMeterKey = 0;

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
            $this->pwMeterKey++;
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->pwMeterKey++;
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
            <div x-data="{
                initPwm() {
                    this.$nextTick(() => {
                        const el = this.$el.querySelector('[data-kt-password-meter]');
                        if (!el) return;
                        const prev = KTPasswordMeter?.getInstance(el);
                        if (prev) prev.destroy();
                        const meter = new KTPasswordMeter(el, { minLength: 8 });
                        // Force re-check if input has pre-filled value (e.g., after Livewire DOM morph)
                        meter.check();
                    });
                }
            }" x-init="initPwm()" wire:key="pw-meter-{{ $pwMeterKey }}">
                <x-ui.form-field label="{{ __('Password Baru') }}" for="password">
                    <div class="fv-row" data-kt-password-meter="true">
                        <div class="position-relative mb-3">
                            <x-ui.input wire:model="password" id="password" type="password" autocomplete="new-password" />
                            <span class="btn btn-sm btn-icon position-absolute translate-middle top-50 end-0 me-n2"
                                data-kt-password-meter-control="visibility">
                                <i class="bi bi-eye-slash fs-2"></i>
                                <i class="bi bi-eye fs-2 d-none"></i>
                            </span>
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
                <x-action-message on="password-updated" class="text-success fs-8 fw-semibold">
                    {{ __('Tersimpan.') }}
                </x-action-message>
            </div>
        </div>
    </form>
</section>
