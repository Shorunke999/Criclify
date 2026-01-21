<?php
namespace Modules\Payment\Enums;

enum TransactionTypeEnum: string
{
    case Contribution = 'contribution';
    case CircleDisbursement = 'circle_disbursement';
    case VaultDeposit = 'vault_deposit';
    case VaultDisbursement = 'vault_disbursement';
    case Transfer = 'transfer';
    case Withdrawal = 'withdrawal';
}
