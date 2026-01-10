<?php

namespace Modules\Notification\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InappNotificationController extends Controller
{
    public function __construct(protected \Modules\Notification\Services\InAppNotificationService $inAppNotificationService)
    {}
    /**
     * List in-app notifications for the authenticated user
     */
    public function index()
    {
        return $this->inAppNotificationService->index();
    }
    /**
     * Mark a specific notification as read
     */
    public function markAsRead(string $id)
    {
        return $this->inAppNotificationService->markAsRead($id);
    }
    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        return $this->inAppNotificationService->markAllAsRead();
    }
}
