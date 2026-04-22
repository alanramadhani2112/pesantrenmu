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
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login">
        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="form.email" id="email" class="block mt-1 w-full" type="email" name="email" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4" x-data="{ show: false }">
            <x-input-label for="password" :value="__('Password')" />

            <div class="relative">
                <x-text-input wire:model="form.password" id="password" class="block mt-1 w-full pr-10"
                                type="password"
                                x-bind:type="show ? 'text' : 'password'"
                                name="password"
                                required autocomplete="current-password" />
                
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5">
                    <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5 text-gray-500" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" x-show="!show">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5 text-gray-500" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" x-show="show" x-cloak>
                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                        <line x1="1" y1="1" x2="23" y2="23"></line>
                    </svg>
                </button>
            </div>

            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-center mt-4">
            <x-primary-button class="mx-auto">
                {{ __('Log in') }}
            </x-primary-button>
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
