<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Parent_ = 'parent';
    case Student = 'student';

    /**
     * Get all enum values as an array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
