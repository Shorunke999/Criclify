<?php
namespace Modules\Vault\Enums;

enum VaultStatusEnum: string
{
    case LOCKED = 'locked';
    case UNLOCKED    = 'unlocked';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
