<?php

namespace App\Enums;

enum ContentType: string
{
    case Video = 'video';
    case Audio = 'audio';
    case Pdf = 'pdf';
    case Interactive = 'interactive';

    /**
     * Get all enum values as an array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
