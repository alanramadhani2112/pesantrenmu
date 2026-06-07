{{-- Notification menu - Alpine + fetch replacement for Livewire component --}}
<div class="position-relative"
     x-data="notificationMenu()"
     @click.away="open = false"
     @close.stop="open = false">
    <div @click="toggle()">
        <x-ui.button type="button" variant="light" class="btn-icon btn-active-light-primary position-relative">
            <x-ui.icon name="notification-bing" class="fs-2" />
            <span x-show="unreadCount > 0" x-cloak
                  class="position-absolute top-0 start-100 translate-middle badge badge-circle badge-danger fs-8"
                  x-text="unreadCount"></span>
        </x-ui.button>
    </div>

    <div x-show="open"
         x-cloak
         x-transition.opacity.duration.80ms
         class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-700 menu-state-bg-light-primary fw-semibold py-3 fs-7 show position-absolute end-0 mt-2"
         style="display: none; z-index: 1080; width: min(360px, calc(100vw - 24px));">
        <div class="px-4 py-2 border-bottom border-gray-200 d-flex justify-content-between align-items-center">
            <h3 class="fs-6 fw-semibold text-gray-800 mb-0">Notifikasi</h3>
            <button x-show="unreadCount > 0" @click="markAllAsRead()" type="button"
                    class="btn btn-link btn-sm p-0 fs-8 fw-semibold text-decoration-none">
                Tandai semua dibaca
            </button>
        </div>

        <div class="py-1 spm-notification-scroll">
            <template x-if="notifications.length === 0">
                <div class="px-4 py-8 text-center">
                    <x-ui.icon name="notification-bing" class="fs-3x text-gray-300 mb-3" />
                    <p class="text-muted fs-7 mb-0">Belum ada notifikasi.</p>
                </div>
            </template>

            <template x-for="notification in notifications" :key="notification.id">
                <button @click="markAsRead(notification.id)" type="button"
                        class="spm-notification-item d-flex align-items-start w-100 text-start border-0 bg-transparent"
                        :class="notification.read_at ? 'is-read' : 'is-unread'">
                    <div class="flex-grow-1 min-w-0">
                        <p class="spm-notification-title mb-0 fw-semibold fs-7 text-gray-800 text-truncate"
                           x-text="notification.data.title || 'Notifikasi'"></p>
                        <p class="spm-notification-body mb-0 text-muted fs-8 text-truncate"
                           x-text="notification.data.message || ''"></p>
                        <span class="text-muted fs-9" x-text="notification.created_at"></span>
                    </div>
                    <span x-show="!notification.read_at" class="badge badge-circle badge-primary badge-sm ms-2 flex-shrink-0">&nbsp;</span>
                </button>
            </template>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('notificationMenu', () => ({
        open: false,
        notifications: [],
        unreadCount: 0,

        init() {
            this.fetchNotifications();
        },

        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.fetchNotifications();
            }
        },

        async fetchNotifications() {
            try {
                const res = await fetch('/_api/notifications', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (res.ok) {
                    const data = await res.json();
                    this.notifications = data.notifications;
                    this.unreadCount = data.unreadCount;
                }
            } catch (e) {
                console.error('Failed to fetch notifications', e);
            }
        },

        async markAsRead(id) {
            try {
                const res = await fetch(`/_api/notifications/${id}/read`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (res.ok) {
                    const data = await res.json();
                    if (data.url) {
                        window.location.href = data.url;
                    }
                }
            } catch (e) {
                console.error('Failed to mark notification as read', e);
            }
        },

        async markAllAsRead() {
            try {
                const res = await fetch('/_api/notifications/mark-all-read', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (res.ok) {
                    this.unreadCount = 0;
                    this.notifications = this.notifications.map(n => ({ ...n, read_at: new Date().toISOString() }));
                }
            } catch (e) {
                console.error('Failed to mark all as read', e);
            }
        }
    }));
});
</script>
