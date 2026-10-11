<?php

namespace App\Support;

class ServiceCodeFormatter
{
    public static function display(?string $code): string
    {
        $code = trim((string) $code);

        if ($code === '') {
            return '';
        }

        return str_starts_with($code, 'CS_') ? substr($code, 3) : $code;
    }

    public static function label(?string $code, ?string $label): string
    {
        return trim(trim(self::display($code)).' - '.trim((string) $label), ' -');
    }
}
