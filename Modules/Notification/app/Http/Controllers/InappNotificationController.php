<?php

namespace Modules\Notification\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InappNotificationController extends Controller
{
    public function __construct(protected \Modules\Notification\Services\InAppNotificationService $inAppNotificationService)
    {}

    public function index()
    {
        return $this->inAppNotificationService->index();
    }
    public function markAsRead(string $id)
    {
        return $this->inAppNotificationService->markAsRead($id);
    }
    public function markAllAsRead()
    {
        return $this->inAppNotificationService->markAllAsRead();
    }
}
