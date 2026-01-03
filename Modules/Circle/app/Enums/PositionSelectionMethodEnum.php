<?php

namespace Modules\Circle\Enums;

enum PositionSelectionMethodEnum: string
{
    case Random   = 'random';
    case Sequence = 'sequence';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
