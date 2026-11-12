<?php

namespace App\Support;

class CwisValueFormatter
{
    public static function formatCount($value): string
    {
        if (!is_numeric($value) || !is_finite((float) $value) || (float) $value < 0
            || floor((float) $value) !== (float) $value) {
            return __('No data');
        }

        return number_format((float) $value, 0, '.', ',');
    }

    public static function format($value)
    {
        if ($value === null || !is_numeric($value) || !is_finite((float) $value)) {
            return $value;
        }

        $formatted = number_format((float) $value, 3, '.', '');
        return $formatted === '-0.000' ? '0.000' : $formatted;
    }
}
