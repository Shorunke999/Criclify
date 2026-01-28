<?php

namespace Modules\Auth\Services;

use Modules\Auth\Models\EmailVerificationOtp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Modules\Notification\Emails\Auth\OtpMail;

class OtpService
{
    /**
     * Generate and send OTP
     */
    public function generate(User $user, string $purpose = 'email_verification'): EmailVerificationOtp
    {
        // Delete any existing OTPs for this user and purpose
        EmailVerificationOtp::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->delete();

        // Generate 6-digit OTP
        $otp = $this->generateOtp();

        // Create OTP record (expires in 10 minutes)
        $otpRecord = EmailVerificationOtp::create([
            'user_id' => $user->id,
            'otp' => $otp,
            'purpose' => $purpose,
            'expires_at' => Carbon::now()->addMinutes(10),
            'ip_address' => request()->ip(),
        ]);

        // Send OTP via email
        Mail::to($user->email)->send(new OtpMail($user, $otp, $purpose));

        return $otpRecord;
    }

    /**
     * Verify OTP
     */
    public function verify(User $user, string $otp, string $purpose = 'email_verification'): bool
    {
        $otpRecord = EmailVerificationOtp::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (!$otpRecord) {
            throw new \Exception('No OTP found. Please request a new one.');
        }

        if ($otpRecord->isExpired()) {
            throw new \Exception('OTP has expired. Please request a new one.');
        }

        if (!$otpRecord->canRetry()) {
            throw new \Exception('Too many failed attempts. Please request a new OTP.');
        }

        if ($otpRecord->otp !== $otp) {
            $otpRecord->incrementAttempts();
            throw new \Exception('Invalid OTP. Please try again.');
        }

        // Mark as verified
        $otpRecord->update(['verified_at' => now()]);

        return true;
    }

    /**
     * Resend OTP
     */
    public function resend(User $user, string $purpose = 'email_verification'): EmailVerificationOtp
    {
        // Check rate limiting (max 3 resends per hour)
        $recentOtps = EmailVerificationOtp::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->where('created_at', '>=', Carbon::now()->subHour())
            ->count();

        if ($recentOtps >= 3) {
            throw new \Exception('Too many OTP requests. Please try again later.');
        }

        return $this->generate($user, $purpose);
    }

    /**
     * Generate random 6-digit OTP
     */
    protected function generateOtp(): string
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
