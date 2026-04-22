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

<div class="relative" x-data="{ open: false }" @click.away="open = false" @close.stop="open = false" wire:poll.15s>
    <div @click="open = ! open">
        <button class="relative p-2 text-gray-400 hover:text-gray-500 focus:outline-none transition duration-150 ease-in-out">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            @if($this->unreadCount > 0)
                <span class="absolute top-0 left-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100  bg-red-600 rounded-full">
                    {{ $this->unreadCount }}
                </span>
            @endif
        </button>
    </div>

    <div x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="transform opacity-0 scale-95"
            x-transition:enter-end="transform opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="transform opacity-100 scale-100"
            x-transition:leave-end="transform opacity-0 scale-95"
            class="absolute right-0 mt-2 w-80 bg-white rounded-md shadow-lg overflow-hidden z-50 border border-gray-200"
            style="display: none;">
        <div class="py-2">
            <div class="px-4 py-2 border-b border-gray-100 flex justify-between items-center">
                <h3 class="text-sm font-semibold text-gray-700">Notifikasi</h3>
                @if($this->unreadCount > 0)
                    <button wire:click="markAllAsRead" class="text-xs text-indigo-600 hover:text-indigo-800">Tandai semua dibaca</button>
                @endif
            </div>
            <div class="max-h-64 overflow-y-auto">
                @forelse($this->notifications as $notification)
                    <div wire:click="markAsRead('{{ $notification->id }}')" class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-50 {{ $notification->read_at ? 'opacity-60' : 'bg-blue-50/30' }}">
                        <div class="flex items-start">
                            <div class="flex-1">
                                <p class="text-sm font-bold text-gray-800">{{ $notification->data['title'] }}</p>
                                <p class="text-xs text-gray-600 mt-0.5">{{ $notification->data['message'] }}</p>
                                <p class="text-[10px] text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                            </div>
                            @if(!$notification->read_at)
                                <div class="w-2 h-2 bg-blue-500 rounded-full mt-1.5 shadow-sm"></div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="px-4 py-6 text-center text-gray-500 text-sm">
                        Tidak ada notifikasi.
                    </div>
                @endforelse
            </div>
            {{-- <div class="px-4 py-2 border-t border-gray-100 text-center">
                <a href="#" class="text-xs text-gray-500 hover:text-gray-700">Lihat semua (segera hadir)</a>
            </div> --}}
        </div>
    </div>
</div>
