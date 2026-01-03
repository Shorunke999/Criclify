<?php
namespace Modules\Payment\Enums;

enum TransactionTypeEnum: string
{
    case Contribution = 'contribution';
    case Payout = 'payout';
    case Refund = 'refund';
}
