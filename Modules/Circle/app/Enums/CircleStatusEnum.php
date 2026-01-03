<?php
namespace Modules\Circle\Enums;

enum CircleStatusEnum: string
{
    case Pending   = 'pending';
    case Active    = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Dispute = 'dispute';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
