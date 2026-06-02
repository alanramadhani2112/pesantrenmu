<?php

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    #[Locked]
    public string $token = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Mount the component.
     */
    public function mount(string $token): void
    {
        $this->token = $token;

        $this->email = request()->string('email');
    }

    /**
     * Reset the password for the given user.
     */
    public function resetPassword(): void
    {
        $this->validate([
            'token' => ['required'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $this->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) {
                $user->forceFill([
                    'password' => Hash::make($this->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        if ($status != Password::PASSWORD_RESET) {
            $this->addError('email', __($status));

            return;
        }

        Session::flash('status', __($status));

        $this->redirectRoute('login', navigate: true);
    }
}; ?>

<div>
    <div class="text-center mb-10">
        <x-ui.badge variant="primary" class="mb-4">Reset Password</x-ui.badge>
        <h1 class="text-gray-900 fw-semibold mb-3">Password Baru</h1>
        <div class="text-gray-500 fw-semibold fs-6">Masukkan password baru untuk akun Anda.</div>
    </div>

    <form wire:submit="resetPassword" class="form w-100"
          x-data="formValidation"
          @submit="validateAll()"
          @focusout.debounce.50ms="onBlur($event)"
          @input.debounce.150ms="onInput($event)">
        <x-ui.form-field class="mb-6" label="Email" for="email" :error="$errors->get('email')" data-validate="required|email">
            <x-ui.input model="email" id="email" type="email" name="email"
                   class="form-control-lg"
                   required autofocus autocomplete="username" />
        </x-ui.form-field>

        <x-ui.form-field class="mb-6" label="Password Baru" for="password" :error="$errors->get('password')" x-data="{ show: false }" data-validate="required|min:8">
            <div class="position-relative">
                <x-ui.input model="password" id="password" name="password"
                       class="form-control-lg pe-12"
                       type="password" x-bind:type="show ? 'text' : 'password'"
                       required autocomplete="new-password" />
                <x-ui.button type="button" variant="light" size="sm" @click="show = !show"
                        class="btn-icon btn-active-light-primary position-absolute top-50 end-0 translate-middle-y me-2">
                    <i class="ki-duotone ki-eye fs-2 text-gray-500" x-show="!show"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    <i class="ki-duotone ki-eye-slash fs-2 text-gray-500" x-show="show" x-cloak><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                </x-ui.button>
            </div>
        </x-ui.form-field>

        <x-ui.form-field class="mb-8" label="Konfirmasi Password" for="password_confirmation" :error="$errors->get('password_confirmation')" x-data="{ show: false }" data-validate="required|same:password">
            <div class="position-relative">
                <x-ui.input model="password_confirmation" id="password_confirmation" name="password_confirmation"
                       class="form-control-lg pe-12"
                       type="password" x-bind:type="show ? 'text' : 'password'"
                       required autocomplete="new-password" />
                <x-ui.button type="button" variant="light" size="sm" @click="show = !show"
                        class="btn-icon btn-active-light-primary position-absolute top-50 end-0 translate-middle-y me-2">
                    <i class="ki-duotone ki-eye fs-2 text-gray-500" x-show="!show"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    <i class="ki-duotone ki-eye-slash fs-2 text-gray-500" x-show="show" x-cloak><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                </x-ui.button>
            </div>
        </x-ui.form-field>

        <div class="d-grid">
            <x-ui.button type="submit" variant="primary" size="lg">Reset Password</x-ui.button>
        </div>
    </form>
</div>
