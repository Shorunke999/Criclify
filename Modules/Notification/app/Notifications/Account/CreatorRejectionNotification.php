<?php

namespace Modules\Notification\Notifications\Account;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class CreatorRejectionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected User $user,
        protected string $role,
        protected ?string $reason = null
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
        $mail = (new MailMessage)
            ->subject('Your '.ucfirst($this->role).' Application Update')
            ->greeting("Hello {$this->user->first_name},")
            ->line('Thank you for applying to become a '.$this->role.'.');

        if ($this->reason) {
            $mail->line('Unfortunately, your application was not approved for the following reason:')
                 ->line("“{$this->reason}”");
        } else {
            $mail->line('Unfortunately, your application was not approved at this time.');
        }

        $mail->line('You may update your details and reapply at any time.')
             ->salutation('Regards, ' . config('app.name'));

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     */
     public function toArray($notifiable): array
    {
        return [
            'type' => $this->role.'_rejected',
            'user_id' => $this->user->id,
            'reason' => $this->reason,
        ];
    }
}
