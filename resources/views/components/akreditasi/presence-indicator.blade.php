@props([
    'akreditasiId',
])

@if (config('akreditasi.presence_enabled'))
    <div
        x-data="{
            others: [],
            init() {
                if (typeof window.Echo === 'undefined') {
                    return;
                }

                const channel = window.Echo.join('akreditasi.{{ $akreditasiId }}');
                const currentUserId = {{ (int) (auth()->id() ?? 0) }};

                channel
                    .here((users) => {
                        this.others = (users ?? []).filter((u) => u.id !== currentUserId);
                    })
                    .joining((user) => {
                        if (user.id === currentUserId) {
                            return;
                        }
                        if (! this.others.some((u) => u.id === user.id)) {
                            this.others.push(user);
                        }
                    })
                    .leaving((user) => {
                        this.others = this.others.filter((u) => u.id !== user.id);
                    });
            },
        }"
        class="d-flex align-items-center gap-2 mb-3"
        x-cloak
        x-show="others.length > 0"
        role="status"
        aria-live="polite"
    >
        <x-ui.icon name="people" class="fs-5 text-primary" />

        <span class="text-muted small">
            Sedang dilihat oleh:
        </span>

        <template x-for="user in others" :key="user.id">
            <span class="badge bg-light-primary text-primary" x-text="user.name"></span>
        </template>
    </div>
@endif
