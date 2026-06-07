<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Akreditasi;
use App\Models\Banding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LayoutDataController extends Controller
{
    public function sidebarBadges(): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            if (! $user) {
                return response()->json([
                    'pendingAkreditasiCount' => 0,
                    'pendingBandingCount' => 0,
                    'activeTaskCount' => 0,
                ]);
            }

            $pendingAkreditasiCount = 0;
            $pendingBandingCount = 0;
            $activeTaskCount = 0;

            if ($user->canAccessAdminArea()) {
                $pendingAkreditasiCount = Cache::remember(
                    'badge:admin:pending_akreditasi',
                    30,
                    fn () => Akreditasi::where('status', 6)->count()
                );

                $pendingBandingCount = Cache::remember(
                    'badge:admin:pending_banding',
                    30,
                    fn () => Banding::where('status', 'pending')->count()
                );
            } elseif ($user->isAsesor()) {
                $asesor = $user->asesor;

                if ($asesor) {
                    $activeTaskCount = Cache::remember(
                        'badge:asesor:' . $asesor->id . ':active_tasks',
                        30,
                        fn () => Akreditasi::whereIn('status', [4, 5])
                            ->whereHas('assessments', function ($query) use ($asesor) {
                                $query->where('asesor_id', $asesor->id);
                            })
                            ->count()
                    );
                }
            }

            return response()->json([
                'pendingAkreditasiCount' => $pendingAkreditasiCount,
                'pendingBandingCount' => $pendingBandingCount,
                'activeTaskCount' => $activeTaskCount,
            ]);
        } catch (\Exception $e) {
            Log::error('LayoutData: Failed to load sidebar badges', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'pendingAkreditasiCount' => 0,
                'pendingBandingCount' => 0,
                'activeTaskCount' => 0,
            ]);
        }
    }

    public function notifications(): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            $notifications = $user->notifications()->take(10)->get()->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'data' => $notification->data,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at->diffForHumans(),
                ];
            });

            $unreadCount = $user->unreadNotifications()->count();

            return response()->json([
                'notifications' => $notifications,
                'unreadCount' => $unreadCount,
            ]);
        } catch (\Exception $e) {
            Log::error('LayoutData: Failed to load notifications', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'notifications' => [],
                'unreadCount' => 0,
            ]);
        }
    }

    public function markNotificationRead(string $id): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            $notification = $user->notifications()->find($id);

            if ($notification) {
                $notification->markAsRead();

                return response()->json([
                    'success' => true,
                    'url' => $notification->data['url'] ?? '/dashboard',
                ]);
            }

            return response()->json(['success' => false, 'url' => '/dashboard'], 404);
        } catch (\Exception $e) {
            Log::error('LayoutData: Failed to mark notification as read', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'url' => '/dashboard'], 500);
        }
    }

    public function markAllNotificationsRead(): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            $user->unreadNotifications->markAsRead();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('LayoutData: Failed to mark all notifications as read', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false], 500);
        }
    }
}
