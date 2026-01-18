<?php
namespace Modules\Payment\Enums;

enum TransactionTypeEnum: string
{
    case Contribution = 'contribution';
    case Vault = 'vault';
    case Payout = 'payout';
    case Refund = 'refund';
}
