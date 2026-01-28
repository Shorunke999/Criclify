<?php

namespace Modules\Notification\Emails\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $otp,
        public string $purpose
    ) {}

    public function build()
    {
        $subject = match($this->purpose) {
            'email_verification' => 'Verify Your Email - OTP Code',
            'password_reset' => 'Reset Your Password - OTP Code',
            default => 'Your OTP Code'
        };

        return $this->subject($subject)
            ->markdown('emails.otp', [
                'user' => $this->user,
                'otp' => $this->otp,
                'purpose' => $this->purpose,
                'expiresIn' => '10 minutes'
            ]);
    }
}
