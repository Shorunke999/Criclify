<?php

namespace App\Enums;

enum AuditAction: string
{
    case NDPR_CONSENT_GIVEN = 'ndpr_consent_given';
    case KYC_SUBMITTED     = 'kyc_submitted';
    case KYC_APPROVED      = 'kyc_approved';
    case KYC_REJECTED      = 'kyc_rejected';
    // case USER_REGISTERED   = 'user_registered';
}
