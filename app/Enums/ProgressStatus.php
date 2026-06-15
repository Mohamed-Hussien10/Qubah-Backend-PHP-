<?php

namespace App\Enums;

enum ProgressStatus: string
{
    case Started = 'started';
    case Completed = 'completed';

    /**
     * Get all enum values as an array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
