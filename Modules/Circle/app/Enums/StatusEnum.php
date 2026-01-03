<?php
namespace Modules\Circle\Enums;

enum StatusEnum: string
{
    case Pending = 'pending';
    case Paid    = 'paid';
    case Overdue = 'overdue';
    case Partpayment = 'part_payment';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
