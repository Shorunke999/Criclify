<?php

namespace App\Enums;

enum AuditAction: string
{
    case NDPR_CONSENT_GIVEN = 'ndpr_consent_given';

    case KYC_SUBMITTED     = 'kyc_submitted';
    case KYC_APPROVED      = 'kyc_approved';
    case KYC_REJECTED      = 'kyc_rejected';


    case CIRCLE_CREATED      = 'circle_created';
    case CIRCLE_STARTED      = 'circle_started';
    case CIRCLE_COMPLETED    = 'circle_completed';

    case WALLET_CREDITED   = 'wallet_credited';
    case WALLET_DEBITED    = 'wallet_debited';

    case CONTRIBUTION_PAID = 'contribution_paid';

    case VAULT_CREATED   = 'vault_created';
    case VAULT_DISBUSED = 'vault_disbursed';
}
