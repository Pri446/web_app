<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static NotStarted()
 * @method static static Completed()
 */
final class FeedbackStatus extends Enum
{
    const NotStarted = 0;
    const Completed = 1;

    public static function toEnumArray()
    {
        return [
            self::NotStarted => 'Not Started',
            self::Completed => 'Completed',
        ];
    }
}
