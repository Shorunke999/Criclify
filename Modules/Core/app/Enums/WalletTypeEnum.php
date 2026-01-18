<?php
namespace Modules\Core\Enums;

enum WalletTypeEnum: string
{
    case User = 'user';
    case Circle    = 'circle';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
