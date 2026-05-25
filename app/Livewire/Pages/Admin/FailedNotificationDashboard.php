<?php

namespace App\Livewire\Pages\Admin;

use App\Models\FailedNotification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class FailedNotificationDashboard extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'pending';
    public int $perPage = 15;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function mount(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (! $user || ! $user->canAccessAdminArea()) {
            abort(403);
        }
    }

    /**
     * Paginated list of failed notifications with optional search and status filter.
     */
    public function getFailedNotificationsProperty(): LengthAwarePaginator
    {
        $query = FailedNotification::with('notifiable')
            ->orderBy('failed_at', 'desc');

        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('notification_type', 'like', '%' . $this->search . '%')
                  ->orWhere('failure_reason', 'like', '%' . $this->search . '%')
                  ->orWhereHas('notifiable', function ($uq) {
                      $uq->where('name', 'like', '%' . $this->search . '%')
                         ->orWhere('email', 'like', '%' . $this->search . '%');
                  });
            });
        }

        return $query->paginate($this->perPage);
    }

    /**
     * Count of pending failed notifications for the navigation badge.
     */
    public function getPendingCountProperty(): int
    {
        return FailedNotification::where('status', 'pending')->count();
    }

    /**
     * Re-queue the notification and mark as resolved.
     */
    public function retry(int $id): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($user && $user->canAccessAdminArea(), 403);

        $record = FailedNotification::findOrFail($id);
        $record->retry();

        $this->dispatch('notification-received', type: 'success', title: 'Berhasil!', message: 'Notifikasi berhasil dikirim ulang.');
    }

    /**
     * Mark the notification as dismissed.
     */
    public function dismiss(int $id): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($user && $user->canAccessAdminArea(), 403);

        $record = FailedNotification::findOrFail($id);
        $record->dismiss();

        $this->dispatch('notification-received', type: 'success', title: 'Berhasil!', message: 'Notifikasi berhasil diabaikan.');
    }

    public function render()
    {
        return view('livewire.pages.admin.failed-notification-dashboard', [
            'failedNotifications' => $this->failedNotifications,
            'pendingCount'        => $this->pendingCount,
        ]);
    }
}
