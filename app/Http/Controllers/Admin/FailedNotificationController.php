<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FailedNotification;
use Illuminate\Http\Request;

class FailedNotificationController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        $search = $request->input('search', '');
        $statusFilter = $request->input('status', 'pending');
        $perPage = min(max($request->integer('perPage', 15), 5), 50);

        $query = FailedNotification::with('notifiable')
            ->orderBy('failed_at', 'desc');

        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('notification_type', 'like', '%'.$search.'%')
                    ->orWhere('failure_reason', 'like', '%'.$search.'%')
                    ->orWhereHas('notifiable', function ($uq) use ($search) {
                        $uq->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%');
                    });
            });
        }

        $failedNotifications = $query->paginate($perPage)->withQueryString();
        $pendingCount = FailedNotification::where('status', 'pending')->count();

        return view('admin.failed-notifications.index', compact(
            'failedNotifications', 'pendingCount', 'search', 'statusFilter', 'perPage'
        ));
    }

    public function retry(Request $request, int $id)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        $record = FailedNotification::findOrFail($id);
        $record->retry();

        return back()->with('success', 'Notifikasi berhasil dikirim ulang.');
    }

    public function dismiss(Request $request, int $id)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        $record = FailedNotification::findOrFail($id);
        $record->dismiss();

        return back()->with('success', 'Notifikasi berhasil diabaikan.');
    }
}
