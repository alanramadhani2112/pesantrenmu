<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public $lastNotificationId;

    public function mount()
    {
        $this->lastNotificationId = Auth::user()->notifications()->first()?->id;
    }

    public function getNotificationsProperty()
    {
        $notifications = Auth::user()->notifications()->take(10)->get();
        
        $latest = $notifications->first();
        if ($latest && $latest->id !== $this->lastNotificationId) {
            $this->lastNotificationId = $latest->id;
            $this->dispatch('notification-received', 
                title: $latest->data['title'],
                message: $latest->data['message']
            );
        }

        return $notifications;
    }

    public function getUnreadCountProperty()
    {
        return Auth::user()->unreadNotifications()->count();
    }

    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->find($id);
        if ($notification) {
            $notification->markAsRead();
            return $this->redirect($notification->data['url'], navigate: true);
        }
    }

    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
    }
}; ?>

<div class="position-relative" x-data="{ open: false }" @click.away="open = false" @close.stop="open = false" data-ui-notification-menu="metronic">
    <div @click="open = ! open">
        <x-ui.button type="button" variant="light" class="btn-icon btn-active-light-primary position-relative">
            <x-ui.icon name="notification-bing" class="fs-2" />
            @if($this->unreadCount > 0)
                <span class="position-absolute top-0 start-100 translate-middle badge badge-circle badge-danger fs-8">
                    {{ $this->unreadCount }}
                </span>
            @endif
        </x-ui.button>
    </div>

    <div x-show="open"
            x-cloak
            x-transition.opacity.duration.80ms
            class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-700 menu-state-bg-light-primary fw-semibold py-3 fs-7 show position-absolute end-0 mt-2"
            style="display: none; z-index: 1080; width: min(360px, calc(100vw - 24px));">
        <div class="px-4 py-2 border-bottom border-gray-200 d-flex justify-content-between align-items-center">
                <h3 class="fs-6 fw-semibold text-gray-800 mb-0">Notifikasi</h3>
                @if($this->unreadCount > 0)
                    <x-ui.button type="button" wire:click="markAllAsRead" variant="link" size="sm" class="p-0 fs-8 fw-semibold">Tandai semua dibaca</x-ui.button>
                @endif
        </div>

        <div class="py-1 spm-notification-scroll">
                @forelse($this->notifications as $notification)
                    <x-ui.button unstyled type="button" wire:click="markAsRead('{{ $notification->id }}')" class="spm-notification-item d-flex align-items-start w-100 text-start border-0 bg-transparent {{ $notification->read_at ? 'is-read' : 'is-unread' }}">
                            <div class="flex-grow-1 min-w-0">
                                <p class="spm-notification-title mb-1">{{ $notification->data['title'] }}</p>
                                <p class="spm-notification-message mb-1">{{ $notification->data['message'] }}</p>
                                <p class="spm-notification-time mb-0">{{ $notification->created_at->diffForHumans() }}</p>
                            </div>
                            @if(!$notification->read_at)
                                <span class="spm-notification-dot mt-1 ms-3"></span>
                            @endif
                    </x-ui.button>
                @empty
                    <div class="px-4 py-8 text-center text-muted fs-7">
                        Tidak ada notifikasi.
                    </div>
                @endforelse
        </div>
    </div>
</div>
