<?php
namespace Modules\Circle\Enums;

enum CircleIntervalEnum: string
{
    case Daily    = 'daily';
    case Weekly   = 'weekly';
    case BiWeekly = 'biweekly';
    case Monthly  = 'monthly';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
