<?php
namespace Modules\Vault\Enums;

enum VaultScheduleStatusEnum: string
{
    case PENDING = 'pending';
    case PAID    = 'paid';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
