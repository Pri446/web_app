<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static NotStarted()
 * @method static static Processing()
 * @method static static Completed()
 */
final class ApplicationStatus extends Enum
{
    const NotStarted = 0;
    const Processing = 1;
    const Completed = 2;

    public static function toEnumArray()
    {
        return [
            self::NotStarted => 'Not Started',
            self::Processing => 'Processing',
            self::Completed => 'Completed',
        ];
    }
}
