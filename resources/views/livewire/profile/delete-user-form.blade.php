<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="space-y-6" x-data="deleteConfirmation">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Delete Account') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>
    </header>

    <x-ui.button
        variant="danger"
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >
        {{ __('Delete Account') }}
    </x-ui.button>

    <x-ui.modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable>
        <form x-on:submit.prevent="confirmAction('deleteUser', '{{ __('Delete Account') }}?', '{{ __('This action permanently deletes your account and data.') }}', '{{ __('Delete Account') }}', 'danger')">
            <x-ui.modal-header
                title="{{ __('Are you sure you want to delete your account?') }}"
                subtitle="{{ __('This action permanently deletes your account and data.') }}"
                icon="trash"
                variant="danger"
            />

            <x-ui.modal-body>
                <x-ui.form-field
                    label="{{ __('Password') }}"
                    for="password"
                    :error="$errors->get('password')"
                    hint="{{ __('Please enter your password to confirm this action.') }}"
                    class="mb-0"
                >
                    <x-ui.input
                        model="password"
                        id="password"
                        name="password"
                        type="password"
                        placeholder="{{ __('Password') }}"
                    />
                </x-ui.form-field>
            </x-ui.modal-body>

            <x-ui.modal-footer>
                <x-ui.button type="button" variant="light" x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-ui.button>

                <x-ui.button type="submit" variant="danger">
                    {{ __('Delete Account') }}
                </x-ui.button>
            </x-ui.modal-footer>
        </form>
    </x-ui.modal>
</section>
