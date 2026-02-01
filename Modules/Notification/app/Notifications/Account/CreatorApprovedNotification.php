<?php

namespace Modules\Notification\Notifications\Account;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class CreatorApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
     public function __construct(
        protected User $user,
        protected string $role,
        protected string $password
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $frontendUrl = config('app.frontend_url');

        return (new MailMessage)
            ->subject('Your '.ucfirst($this->role).' Account Has Been Approved 🎉')
            ->greeting("Hello {$this->user->first_name},")
            ->line('Great news! Your '.$this->role.' account has been approved.')
            ->line('To get started, Yout  password is {$this->password}')
            ->line('If you did not request this, you can safely ignore this email.')
            ->salutation('Welcome onboard, ' . config('app.name'));
    }

    /**
     * Get the array representation of the notification.
     */
     public function toArray($notifiable): array
    {
        return [
            'type' => $this->role.'_approved',
            'user_id' => $this->user->id,
        ];
    }
}
