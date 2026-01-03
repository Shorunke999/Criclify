<?php
namespace Modules\Payment\Enums;

enum TransactionStatusEnum: string
{
    case Pending = 'pending';
    case Success = 'success';
    case Failed = 'failed';
}
