<?php
namespace Modules\Circle\Enums;

enum InviteStatusEnum: string
{
    case Pending  = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Expired  = 'expired';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
