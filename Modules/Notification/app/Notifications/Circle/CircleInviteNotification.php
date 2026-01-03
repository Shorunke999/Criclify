<?php

namespace Modules\Notification\Notifications\Circle;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Modules\Circle\Models\CircleInvite;

class CircleInviteNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public CircleInvite $invite
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

     public function toDatabase($notifiable): array
    {
        return [
            'type' => 'circle_invite',
            'circle_id' => $this->invite->circle_id,
            'invite_id' => $this->invite->id,
            'inviter_id' => $this->invite->inviter_id,
            'token' => $this->invite->token,
            'expires_at' => $this->invite->expires_at,
        ];
    }
    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [];
    }
}
