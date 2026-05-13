<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        event(new Registered($user = User::create($validated)));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="text-center mb-10">
        <x-ui.badge variant="primary" class="mb-4">Daftar Akun</x-ui.badge>
        <h1 class="text-gray-900 fw-bolder mb-3">Buat Akun Baru</h1>
        <div class="text-gray-500 fw-semibold fs-6">Isi data berikut untuk mendaftar.</div>
    </div>

    <form wire:submit="register" class="form w-100">
        <x-ui.form-field class="mb-6" label="Nama" for="name" :error="$errors->get('name')">
            <x-ui.input model="name" id="name" type="text" name="name" class="form-control-lg" required autofocus autocomplete="name" />
        </x-ui.form-field>

        <x-ui.form-field class="mb-6" label="Email" for="email" :error="$errors->get('email')">
            <x-ui.input model="email" id="email" type="email" name="email" class="form-control-lg" required autocomplete="username" />
        </x-ui.form-field>

        <x-ui.form-field class="mb-6" label="Password" for="password" :error="$errors->get('password')" x-data="{ show: false }">
            <div class="position-relative">
                <x-ui.input model="password" id="password" name="password" class="form-control-lg pe-12" type="password" x-bind:type="show ? 'text' : 'password'" required autocomplete="new-password" />
                <x-ui.button type="button" variant="light" size="sm" @click="show = !show"
                        class="btn-icon btn-active-light-primary position-absolute top-50 end-0 translate-middle-y me-2">
                    <i class="ki-duotone ki-eye fs-2 text-gray-500" x-show="!show"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    <i class="ki-duotone ki-eye-slash fs-2 text-gray-500" x-show="show" x-cloak><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                </x-ui.button>
            </div>
        </x-ui.form-field>

        <x-ui.form-field class="mb-8" label="Konfirmasi Password" for="password_confirmation" :error="$errors->get('password_confirmation')" x-data="{ show: false }">
            <div class="position-relative">
                <x-ui.input model="password_confirmation" id="password_confirmation" name="password_confirmation" class="form-control-lg pe-12" type="password" x-bind:type="show ? 'text' : 'password'" required autocomplete="new-password" />
                <x-ui.button type="button" variant="light" size="sm" @click="show = !show"
                        class="btn-icon btn-active-light-primary position-absolute top-50 end-0 translate-middle-y me-2">
                    <i class="ki-duotone ki-eye fs-2 text-gray-500" x-show="!show"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    <i class="ki-duotone ki-eye-slash fs-2 text-gray-500" x-show="show" x-cloak><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                </x-ui.button>
            </div>
        </x-ui.form-field>

        <div class="d-grid mb-6">
            <x-ui.button type="submit" variant="primary" size="lg">Daftar</x-ui.button>
        </div>

        <div class="text-center">
            <span class="text-gray-500 fw-semibold fs-6">Sudah punya akun?</span>
            <a href="{{ route('login') }}" class="link-primary fw-bold fs-6 ms-1">Masuk</a>
        </div>
    </form>
</div>
