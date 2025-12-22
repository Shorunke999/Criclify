<?php

namespace App\Enums;

enum KycStatus: string
{
    case NONE     = 'none';
    case PENDING  = 'pending';
    case VERIFIED = 'verified';
    case FAILED   = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::NONE     => 'Not Started',
            self::PENDING  => 'Verification Pending',
            self::VERIFIED => 'Verified',
            self::FAILED   => 'Verification Failed',
        };
    }

    public function nextStep(): string
    {
        return match ($this) {
            self::VERIFIED => 'none',
            self::PENDING  => 'kyc_processing',
            self::FAILED   => 'kyc_failed',
            self::NONE     => 'kyc_required',
        };
    }
}
