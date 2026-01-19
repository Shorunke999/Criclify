<?php
namespace Modules\Vault\Enums;

enum VaultStatusEnum: string
{
    case LOCKED = 'locked';
    case COMPLETED = 'completed';
    case UNLOCKED    = 'unlocked';
    case DISBURSED    = 'disbursed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
