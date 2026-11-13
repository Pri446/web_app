<?php

namespace App\Enums;

final class EmptyingReason
{
    public const ROUTINE_CLEANING = 'Routine Cleaning';
    public const OVERFLOW_OR_FULL = 'Overflow or Full';

    public static function values(): array
    {
        return [
            self::ROUTINE_CLEANING,
            self::OVERFLOW_OR_FULL,
        ];
    }

    public static function options(): array
    {
        return array_combine(self::values(), self::values());
    }
}
