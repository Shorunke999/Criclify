<?php
namespace Modules\Cooperative\Enums;

enum CoopMemberStatuEnum: string
{
    case Pending   = 'pending';
    case Approved  = 'approved';
    case Suspended = 'suspended';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}


