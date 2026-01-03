<?php

namespace Modules\Notification\Services;

use App\Traits\PosthogTrait;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\Auth;

class InAppNotificationService
{
    use ResponseTrait;

    public function index()
    {
        try{
            $user = Auth::user();
            return $this->success_response([
                'unread_count' => $user->unreadNotifications()->count(),
                'notifications' => $user
                    ->notifications()
                    ->latest()
                    ->paginate(20),
            ], 'Notifications fetched successfully');
        }catch(\Exception $e){
            $this->reportError($e, "InAppNotificationService", [
                'action' => 'fetch_notifications',
                'user_id' => Auth::id(),
            ]);
            return $this->error_response('Failed to fetch notifications: ' . $e->getMessage(), $e->getCode() ?: 400);
        }
    }

    public function markAsRead(string $id)
    {
        try {
            $user = Auth::user();
            $notification = $user
                ->notifications()
                ->where('id', $id)
                ->firstOrFail();

            $notification->markAsRead();

            return $this->success_response(['status' => 'read'], 'Notification marked as read successfully',200);
        } catch(\Exception $e){
            $this->reportError($e, "InAppNotificationService", [
                'action' => 'mark_as_read_notification',
                'user_id' => Auth::id(),
            ]);
            return $this->error_response('Failed to mark notification as read: ' . $e->getMessage(), $e->getCode() ?: 400);
        }
    }

    public function markAllAsRead()
    {
        try {
            $user = Auth::user();
            $user->unreadNotifications->markAsRead();

            return $this->success_response(['status' => 'all_read'], 'All notifications marked as read successfully',200);
        } catch(\Exception $e){
            $this->reportError($e, "InAppNotificationService", [
                'action' => 'mark_all_as_read_notifications',
                'user_id' => Auth::id(),
            ]);
            return $this->error_response('Failed to mark all notifications as read: ' . $e->getMessage(), $e->getCode() ?: 400);
        }
    }

}
