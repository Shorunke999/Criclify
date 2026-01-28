<?php

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class EmailVerificationOtp extends Model
{
    protected $fillable = [
        'user_id',
        'otp',
        'purpose',
        'expires_at',
        'verified_at',
        'ip_address',
        'attempts',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isVerified(): bool
    {
        return !is_null($this->verified_at);
    }

    public function canRetry(): bool
    {
        return $this->attempts < 5; // Max 5 attempts
    }

    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }
}
