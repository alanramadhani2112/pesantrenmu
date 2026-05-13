<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component {
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

@php
    $currentUser = auth()->user();
    $roleName = $currentUser->role?->name ?? 'user';
@endphp

<x-layout.app-sidebar :current-user="$currentUser" :role-name="$roleName" />
